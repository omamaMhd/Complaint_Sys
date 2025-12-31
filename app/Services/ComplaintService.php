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
//use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Exceptions\HttpResponseException;



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

                 // بعد إنشاء الشكوى
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendComplaintCreatedNotification($complaint); 

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

    public function changeStatus(int $complaintId, string $newStatus, int $byUser)
    {
        return DB::transaction(function() use ($complaintId, $newStatus, $byUser) {

        $user = auth()->user();
        // 🔐 تأكد أن التوكن لمستخدم (admin أو employee)
        if (!$user || !$user->hasAnyRole(['admin', 'employee'])) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Unauthorized token'
                ], 401)
            );  }

            $complaint = $this->repo->find($complaintId);

            if (!$complaint) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Complaint not found'
                ], 404)
            );   }
        $old = $complaint->status;
          // 🔐 Authorization
        if ($user->hasRole('employee')) {
            if ($complaint->responsible_party !== $user->responsible_party) {
            throw new HttpResponseException(
            response()->json([
            'message' => 'You are not allowed to change this complaint'
            ], 403)
        );
            }}

        // ✅ التحقق أولاً ثم إرسال الإشعار
        $this->repo->update($complaint, $newStatus);

            $this->historyRepo->create([
                'complaint_id' => $complaintId,
                'performed_by' => $byUser,
                'action' => 'status_changed',
                'data' => ['old' => $old, 'new' => $newStatus]
            ]);

        // ✅ هنا أرسل الإشعار - بعد التأكد من التغيير
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendStatusChangeNotification($complaint, $old, $newStatus);

            Log::info("Complaint status changed id={$complaintId} from={$old} to={$newStatus} by={$byUser}");

            return $this->repo->find($complaintId);
        });
    }

    public function getHistory(int $complaintId)
    {
        return $this->historyRepo->forComplaint($complaintId);
    }





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


// 🔹 إضافة ملاحظة
    public function addNote(int $id, string $note)
    {
        $user = auth()->user();
        // 🔐 التحقق من التوكن (admin أو employee فقط)
        if (!$user || !$user->hasAnyRole(['admin', 'employee'])) {
            throw new HttpResponseException(
               response()->json([
                     'message' => 'Unauthorized token'
               ], 401)
         );   }
        $complaint = $this->repo->find($id);

        if (!$complaint) {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Complaint not found'
            ], 404)
        );
    }
    // 🔒 في حال موظف → لازم نفس الجهة
    if ($user->hasRole('employee')) {
        if ($complaint->responsible_party !== $user->responsible_party) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'You are not allowed to add a note to this complaint'
                ], 403)
            );
        } }    
        // حفظ الملاحظة في history
        $noteRecord = $this->historyRepo->create([
            'complaint_id' => $id,
            'performed_by' => auth()->id(),
            'action' => 'note_added',
            'data' => ['note' => $note]
        ]);

        // 3. ✅ إرسال إشعار للمواطن
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendNoteAddedNotification($complaint, $note);

        return $noteRecord;
    }


}
