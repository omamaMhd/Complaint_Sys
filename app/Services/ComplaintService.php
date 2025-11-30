<?php

namespace App\Services;

use App\Repositories\ComplaintRepository;
use App\Repositories\ComplaintHistoryRepository;
use App\Repositories\AttachmentRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Notifications\ComplaintStatusChanged;
use Illuminate\Support\Facades\Notification;

class ComplaintService
{
    protected $repo;
    protected $historyRepo;
    protected $attachRepo;

    public function __construct(
        ComplaintRepository $repo,
        ComplaintHistoryRepository $historyRepo,
        AttachmentRepository $attachRepo
    ){
        $this->repo = $repo;
        $this->historyRepo = $historyRepo;
        $this->attachRepo = $attachRepo;
    }

    public function createComplaint(array $data)
    {
        return DB::transaction(function() use ($data) {
            $data['reference_number'] = 'CMP-' . Str::uuid();
            $complaint = $this->repo->create($data);

            $this->historyRepo->create([
                'complaint_id' => $complaint->id,
                'performed_by' => $data['user_id'] ?? null,
                'action' => 'created',
                'data' => ['initial' => $complaint->toArray()]
            ]);

            Log::info("Complaint created: id={$complaint->id}, ref={$complaint->reference_number}");

            return $complaint;
        });
    }

    public function listUserComplaints(int $citizenId)
    {
        return $this->repo->listByCitizen($citizenId);
    }

    public function lockForProcessing(int $complaintId, int $adminId, $ttl = 600): bool
    {
        $lockKey = "complaint_lock_{$complaintId}";
        $lock = Cache::lock($lockKey, $ttl);

        if ($lock->get()) {
            $this->repo->update($this->repo->find($complaintId), [
                'locked_by' => $adminId,
                'locked_until' => Carbon::now()->addSeconds($ttl)
            ]);

            $this->historyRepo->create([
                'complaint_id' => $complaintId,
                'performed_by' => $adminId,
                'action' => 'locked',
                'data' => ['ttl' => $ttl]
            ]);

            return true;
        }
        return false;
    }

    public function unlock(int $complaintId, int $adminId): bool
    {
        $lockKey = "complaint_lock_{$complaintId}";
        Cache::forget($lockKey);

        $this->repo->update($this->repo->find($complaintId), [
            'locked_by' => null,
            'locked_until' => null
        ]);

        $this->historyRepo->create([
            'complaint_id' => $complaintId,
            'performed_by' => $adminId,
            'action' => 'unlocked',
            'data' => []
        ]);

        return true;
    }

    public function changeStatus(int $complaintId, string $newStatus, int $byUser)
    {
        return DB::transaction(function() use ($complaintId, $newStatus, $byUser) {
            $complaint = $this->repo->find($complaintId);
            $old = $complaint->status;

            $this->repo->update($complaint, ['status' => $newStatus]);

            $this->historyRepo->create([
                'complaint_id' => $complaintId,
                'performed_by' => $byUser,
                'action' => 'status_changed',
                'data' => ['old' => $old, 'new' => $newStatus]
            ]);

            Log::info("Complaint status changed id={$complaintId} from={$old} to={$newStatus} by={$byUser}");

            $owner = $complaint->citizen;
            if ($owner) {
                Notification::route('mail', $owner->email)
                    ->notify(new ComplaintStatusChanged($complaint, $old, $newStatus));
            }

            return $this->repo->find($complaintId);
        });
    }

    public function getHistory(int $complaintId)
    {
        return $this->historyRepo->forComplaint($complaintId);
    }


// public function addAttachment(int $complaintId, $file, int $uploadedBy)
//     {
//         $path = $file->store('complaint_attachments','public');
//         $attachment = $this->attachRepo->create([
//             'complaint_id' => $complaintId,
//             'path' => $path,
//             'original_name' => $file->getClientOriginalName(),
//             'uploaded_by' => $uploadedBy
//         ]);
//          // 1) منع المواطن من رفع مرفقات ليست لشكواه
//     if (auth('sanctum')->id() && $attachment->citizen_id !== $uploadedBy) {
//         throw new \Exception("ليس لديك صلاحية لرفع مرفقات لهذه الشكوى.");
//     }

//     // 2) منع رفع مرفقات بعد الإغلاق
//     if ($attachment->status === 'completed' || $attachment->status === 'rejected') {
//         throw new \Exception("لا يمكن رفع مرفقات على شكوى مغلقة.");
//     }

//     // 3) منع تجاوز حد المرفقات
//     if ($attachment->attachments()->count() >= 5) {
//         throw new \Exception("تم الوصول للحد الأقصى للمرفقات (5).");
//     }

   

//         $this->historyRepo->create([
//             'complaint_id' => $complaintId,
//             'performed_by' => $uploadedBy,
//             'action' => 'attachment_added',
//             'data' => ['attachment_id' => $attachment->id, 'path' => $path]
//         ]);

//         return $attachment;
//     }

public function addAttachment(int $complaintId, $file, int $uploadedBy)
{
    $complaint = $this->repo->find($complaintId);

    if (!$complaint) {
        throw new \Exception("Complaint not found");
    }

    // 1) منع المواطن من رفع مرفقات ليست لشكواه
    if ($complaint->citizen_id != $uploadedBy) {
        throw new \Exception("ليس لديك صلاحية لرفع مرفقات لهذه الشكوى.");
    }

    // 2) منع رفع مرفقات بعد الإغلاق
    if (in_array($complaint->status, ['completed', 'rejected'])) {
        throw new \Exception("لا يمكن رفع مرفقات على شكوى مغلقة.");
    }

    // 3) منع تجاوز حد المرفقات (مثلاً: 5)
    if ($complaint->attachments()->count() >= 5) {
        throw new \Exception("تم الوصول للحد الأقصى للمرفقات (5).");
    }

    // 4) رفع الملف
    $path = $file->store('complaint_attachments', 'public');

    $attachment = $this->attachRepo->create([
        'complaint_id' => $complaintId,
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'uploaded_by' => $uploadedBy
    ]);

    return $attachment;
}

}
