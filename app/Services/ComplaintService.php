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
protected  $maxAttachments = 5;
    public function __construct(
        ComplaintRepository $repo,
        ComplaintHistoryRepository $historyRepo,
        AttachmentRepository $attachRepo
    ){
        $this->repo = $repo;
        $this->historyRepo = $historyRepo;
        $this->attachRepo = $attachRepo;
    }

    // public function createComplaint(array $data)
    // {
    //     return DB::transaction(function() use ($data) {
    //         $data['reference_number'] = 'CMP-' . Str::uuid();
    //         $complaint = $this->repo->create($data);

    //         $this->historyRepo->create([
    //             'complaint_id' => $complaint->id,
    //             'performed_by' => $data['user_id'] ?? null,
    //             'action' => 'created',
    //             'data' => ['initial' => $complaint->toArray()]
    //         ]);

    //         Log::info("Complaint created: id={$complaint->id}, ref={$complaint->reference_number}");

    //         return $complaint;
    //     });
    // }
      public function createComplaint(array $data, array $files, int $citizenId)
    {
        $stored = [];
       
        try {

            $complaint = DB::transaction(function () use ($data, $files, $citizenId, &$stored) {

                $data['reference_number'] = 'CMP-' . Str::uuid();
                $data['citizen_id'] = $citizenId;

                // إنشاء الشكوى
                $complaint = $this->repo->create($data);

                // تسجيل إنشائها في التاريخ
                $this->historyRepo->create([
                    'complaint_id' => $complaint->id,
                    'performed_by' => $citizenId,
                    'action' => 'created',
                    'data' => ['initial' => $complaint->toArray()]
                ]);

                // رفع المرفقات
                if (!empty($files)) {

                    if (count($files) > $this->maxAttachments) {
                        throw new \Exception("لا يمكن رفع أكثر من {$this->maxAttachments} مرفقات.");
                    }

                    foreach ($files as $file) {
                        $path = $file->store('complaint_attachments', 'public');
                        $url = asset('storage/' . $path);
                        $stored[] = $path;

                        $attachment = $this->attachRepo->create([
                            'complaint_id' => $complaint->id,
                            'path' =>  $url,
                            'original_name' => $file->getClientOriginalName(),
                            'uploaded_by' => $citizenId
                        ]);

                        // سجل محفوظ في التاريخ
                        $this->historyRepo->create([
                            'complaint_id' => $complaint->id,
                            'performed_by' => $citizenId,
                            'action' => 'attachment_added',
                            'data' => [
                                'attachment_id' => $attachment->id,
                                'path' => $attachment->path
                            ]
                        ]);
                    }
                }

                Log::info("Complaint created with {$complaint->attachments()->count()} attachments");

                return $complaint;
            });

            return $complaint;

        } catch (\Throwable $e) {

            // حذف الملفات التي رفعت قبل فشل العملية
            foreach ($stored as $file) {
                Storage::disk('public')->delete($file);
            }

            throw $e;
        }
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

    public function changeStatus(int $complaintId, string $newStatus, string $department, int $byUser)
    {
        return DB::transaction(function() use ($complaintId, $newStatus, $department, $byUser) {
            $complaint = $this->repo->find($complaintId);
            $old = $complaint->status;

            $this->repo->update($complaint, $newStatus);
            // ['status' => $newStatus]);

            $this->historyRepo->create([
                'complaint_id' => $complaintId,
                'performed_by' => $byUser,
                'action' => 'status_changed',
                'data' => ['old' => $old, 'new' => $newStatus]
            ]);
             // منع الوصول لشكوى جهة أخرى
        if ($complaint->responsible_party !== $department) {
            throw new \Exception("Unauthorized");
        }

            Log::info("Complaint status changed id={$complaintId} from={$old} to={$newStatus} by={$byUser}");

            // $owner = $complaint->citizen;
            // if ($owner) {
            //     Notification::route('mail', $owner->email)
            //         ->notify(new ComplaintStatusChanged($complaint, $old, $newStatus));
            // }

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
public function trackComplaint(string $reference)
{
    return $this->repo->findByReferenceWithAttachments($reference);
}
//عرض كل شكاوي جهة معينة
public function getDepartmentComplaints(string $department)
{
    return $this->repo->listForDepartment($department);
}

//  public function changeStatus(int $id, string $status, string $employeeDepartment, int $employeeId)
//     {
//         $complaint = $this->complaints->find($id);

//         if (!$complaint) {
//             throw new \Exception("Complaint not found");
//         }

//         // 🔹 التحكم بالوصول
//         if ($complaint->responsible_party !== $employeeDepartment) {
//             throw new \Exception("Unauthorized");
//         }

//         $old = $complaint->status;

//         // 🔹 تحديث الحالة
//         $updated = $this->complaints->updateStatus($complaint, $status);

//         // 🔹 حفظ في السجل
//         $this->history->create([
//             'complaint_id' => $id,
//             'performed_by' => $employeeId,
//             'action' => 'status_changed',
//             'data' => [
//                 'old' => $old,
//                 'new' => $status
//             ]
//         ]);

//         return $updated;
//     }

// 🔹 إضافة ملاحظة
    public function addNote(int $id, string $note, string $department, int $employeeId)
    {
        $complaint = $this->repo->find($id);

        if (!$complaint) {
            throw new \Exception("Complaint not found");
        }

        // منع الوصول لشكوى جهة أخرى
        if ($complaint->responsible_party !== $department) {
            throw new \Exception("Unauthorized");
        }

        // حفظ الملاحظة في history
        return $this->historyRepo->create([
            'complaint_id' => $id,
            'performed_by' => $employeeId,
            'action' => 'note_added',
            'data' => ['note' => $note]
        ]);
    }

    // 🔹 طلب معلومات إضافية من المواطن
    // public function requestInfo(int $id, string $message, string $department, int $employeeId)
    // {
    //     $complaint = $this->repo->find($id);

    //     if (!$complaint) {
    //         throw new \Exception("Complaint not found");
    //     }

    //     if ($complaint->responsible_party !== $department) {
    //         throw new \Exception("Unauthorized");
    //     }

    //     // حفظ الطلب في history
    //     return $this->historyRepo->create([
    //         'complaint_id' => $id,
    //         'performed_by' => $employeeId,
    //         'action' => 'info_requested',
    //         'data' => ['message' => $message]
    //     ]);
    // }

}
