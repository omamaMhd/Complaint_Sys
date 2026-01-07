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
use Illuminate\Support\Facades\Storage;
use App\Aspects\TraceAspect;
use App\Aspects\TraceContext;
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

           $citizenName = $this->repo->getCitizenNameById($citizenId);

            $data['reference_number'] = 'CMP-' . Str::uuid();
            $data['citizen_id'] = $citizenId;

            // إنشاء الشكوى
            $complaint = $this->repo->create($data);

            // رفع المرفقات
            $attachmentsData = [];

            if (!empty($files)) {

                if (count($files) > $this->maxAttachments) {
                    throw new \Exception("لا يمكن رفع أكثر من {$this->maxAttachments} مرفقات.");
                }

                foreach ($files as $file) {
                    $path = $file->store('complaint_attachments', 'public');
                    $url  = asset('storage/' . $path);
                    $stored[] = $path;

                    $attachment = $this->attachRepo->create([
                        'complaint_id'  => $complaint->id,
                        'path'          => $url,
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by'   => $citizenId
                    ]);

                    $attachmentsData[] = [
                        'id'            => $attachment->id,
                        'path'          => $attachment->path,
                        'original_name' => $attachment->original_name
                    ];
                }
            }

            // تسجيل حدث واحد فقط في التاريخ
            $this->historyRepo->create([
                'complaint_id' => $complaint->id,
                'performed_by' => $citizenId,
                'performed_by_name' => $citizenName,
                    'performed_by_type' => 'user',
                'action'       => 'created',
                'data'         => [
                    'performed_by' => [
                        'id'   => $citizenId,
                        'name' => $citizenName
                    ],
                    //'complaint'  => $complaint->toArray(),
                    'attachments'=> $attachmentsData
                ]
            ]);
            // // تسجيل التتبع
            // app(\App\Aspects\TraceAspect::class)->record(
            //     'create_complaint',
            //     'complaint',
            //     $complaint->id
            // );
TraceContext::setEntity('complaint', $complaint->id);

           // Log::info("Complaint created with " . count($attachmentsData) . " attachments");

            // إرسال إشعار
            app(\App\Services\NotificationService::class)
                ->sendComplaintCreatedNotification($complaint);

            // كسر الكاش
            // Cache::forget("my_complaints_user_{$citizenId}");
            // Cache::forget('admin_complaints');
            // Cache::forget("department_complaints_{$complaint->responsible_party}");
event(new \App\Events\ComplaintStatusChanged($complaint));
            return $complaint;
        });

        return $complaint;

    } catch (\Throwable $e) {

        // حذف الملفات في حال الفشل
        foreach ($stored as $file) {
            Storage::disk('public')->delete($file);
        }

        throw $e;
    }
}
public function getCitizenComplaintNotes(int $complaintId, int $citizenId)
{
    // التأكد أن الشكوى للمواطن نفسه
    $complaint = $this->repo->find($complaintId);

    if (!$complaint || $complaint->citizen_id !== $citizenId) {
        throw new \Exception('Unauthorized');
    }

    return Cache::remember(
        "complaint_notes_{$complaintId}",
        60,
        fn () => $this->historyRepo->getNotesForCitizenComplaint($complaintId)
    );
    TraceContext::setEntity('complaint', $complaint->id);
}


    // public function listUserComplaints(int $citizenId)
    // {
    //     return $this->repo->listByCitizen($citizenId);
    // }
    // عرض كل شكاوي مواطن معين مع التخزين المؤقت
    public function listUserComplaints(int $citizenId)
{
    return Cache::remember(
        "my_complaints_user_{$citizenId}",
        30,
        fn () => $this->repo->listByCitizen($citizenId)
    );
    TraceContext::setEntity('complaint', $complaint->id);
}

    // public function lockForProcessing(int $complaintId, int $adminId, $ttl = 600): bool
    // {
    //     $lockKey = "complaint_lock_{$complaintId}";
    //     $lock = Cache::lock($lockKey, $ttl);

    //     if ($lock->get()) {
    //         $this->repo->update($this->repo->find($complaintId), [
    //             'locked_by' => $adminId,
    //             'locked_until' => Carbon::now()->addSeconds($ttl)
    //         ]);

    //         $this->historyRepo->create([
    //             'complaint_id' => $complaintId,
    //             'performed_by' => $adminId,
    //             'action' => 'locked',
    //             'data' => ['ttl' => $ttl]
    //         ]);

    //         return true;
    //     }
    //     return false;
    // }

    // public function unlock(int $complaintId, int $adminId): bool
    // {
    //     $lockKey = "complaint_lock_{$complaintId}";
    //     Cache::forget($lockKey);

    //     $this->repo->update($this->repo->find($complaintId), [
    //         'locked_by' => null,
    //         'locked_until' => null
    //     ]);

    //     $this->historyRepo->create([
    //         'complaint_id' => $complaintId,
    //         'performed_by' => $adminId,
    //         'action' => 'unlocked',
    //         'data' => []
    //     ]);

    //     return true;
    // }

//     public function changeStatus(int $complaintId, string $newStatus, string $department, int $byUser)
//     {
//         return DB::transaction(function() use ($complaintId, $newStatus, $department, $byUser)
//          {
//              // 🔐 محاولة حجز الشكوى
//         $locked = $this->repo->tryLock($complaintId, $byUser);

//         if (!$locked) {
//             throw new \Exception("Complaint is being processed by another employee");
//         }
// $employeeName = $this->repo->findNameById($byUser) ?? 'Unknown';
//             $complaint = $this->repo->find($complaintId);
//             $old = $complaint->status;


//         // منع الوصول لشكوى جهة أخرى
//         if ($complaint->responsible_party !== $department) {
//             throw new \Exception("Unauthorized");
//         }

//         // ✅ التحقق أولاً ثم إرسال الإشعار
//         $this->repo->update($complaint, $newStatus);

//             // ['status' => $newStatus]);

//             $this->historyRepo->create([
//                 'complaint_id' => $complaintId,
//                 'performed_by' => $byUser,
//                 'performed_by_name' => $employeeName,
//                 'action' => 'status_changed',
//                 'data' => ['old' => $old, 'new' => $newStatus]
//             ]);

//         // ✅ هنا أرسل الإشعار - بعد التأكد من التغيير
//         $notificationService = app(\App\Services\NotificationService::class);
//         $notificationService->sendStatusChangeNotification($complaint, $old, $newStatus);
//          // 🔓 فك القفل
//         DB::update(
//             "UPDATE complaints
//              SET locked_by = NULL, locked_at = NULL
//              WHERE id = ? AND locked_by = ?",
//             [$complaintId, $byUser]
//         );

//             Log::info("Complaint status changed id={$complaintId} from={$old} to={$newStatus} by={$byUser}");
// // 🧹 كسر الكاش بعد تغيير الحالة
// Cache::forget("complaint_history_{$complaintId}");
// Cache::forget("my_complaints_user_{$complaint->citizen_id}");
// Cache::forget("department_complaints_{$complaint->responsible_party}");
// Cache::forget('admin_complaints');
// Cache::forget("track_complaint_{$complaint->reference_number}");

//             return $this->repo->find($complaintId);
//         });
//     }



public function changeStatus(
    int $complaintId,
    string $newStatus,
    int $byUser
) {
    return DB::transaction(function () use ($complaintId, $newStatus, $byUser) {
       // 🔐 محاولة حجز الشكوى
        $locked = $this->repo->tryLock($complaintId, $byUser);

        if (!$locked) {
            throw new \Exception("Complaint is being processed by another employee");
        }
        $complaint = $this->repo->find($complaintId);
  $old = $complaint->status;
  $employeeName = $this->repo->findNameById($byUser) ?? 'Unknown';
        if (!$complaint) {
            throw new \Exception('Complaint not found');
        }

        // 🔐 قسم الموظف الحقيقي
        $employeeDepartment = $this->repo->getDepartmentByUserId($byUser);

        if ($complaint->responsible_party !== $employeeDepartment) {
            throw new \Exception("Unauthorized");
        }

              // ✅ التحقق أولاً ثم إرسال الإشعار
        $this->repo->update($complaint, $newStatus);

            $this->historyRepo->create([
                'complaint_id' => $complaintId,
                'performed_by' => $byUser,
                'performed_by_name' => $employeeName,
                'performed_by_type' => 'employee',
                'action' => 'status_changed',
                'data' => ['old' => $old, 'new' => $newStatus]
            ]);
            // // تسجيل التتبع
            //     TraceAspect::record(
            //     'change_status',
            //     'complaint',
            //     $complaintId,
            //     ['old' => $old, 'new' => $newStatus]
            // );
            TraceContext::setEntity('complaint', $complaint->id);
        // ✅ هنا أرسل الإشعار - بعد التأكد من التغيير
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendStatusChangeNotification($complaint, $old, $newStatus);
         // 🔓 فك القفل
        // DB::update(
        //     "UPDATE complaints
        //      SET locked_by = NULL, locked_at = NULL
        //      WHERE id = ? AND locked_by = ?",
        //     [$complaintId, $byUser]
        // );
        $this->repo->unlock($complaintId, $byUser);
            Log::info("Complaint status changed id={$complaintId} from={$old} to={$newStatus} by={$byUser}");
// 🧹 كسر الكاش بعد تغيير الحالة
// Cache::forget("complaint_history_{$complaintId}");
// Cache::forget("my_complaints_user_{$complaint->citizen_id}");
// Cache::forget("department_complaints_{$complaint->responsible_party}");
// Cache::forget('admin_complaints');
//Cache::forget("track_complaint_{$complaint->reference_number}");
event(new \App\Events\ComplaintStatusChanged($complaint));

        return $this->repo->find($complaintId);
    });
}


// 🔹 إضافة ملاحظة
public function addNote(
    int $complaintId,
    string $note,
    string $department,
    int $employeeId
) {
    return DB::transaction(function () use (
        $complaintId,
        $note,
        $department,
        $employeeId
    ) {

        // 🔐 محاولة حجز الشكوى
        $locked = $this->repo->tryLock($complaintId, $employeeId);

        if (!$locked) {
            throw new \Exception("Complaint is being processed by another employee");
        }
$employeeName = $this->repo->findNameById($employeeId) ?? 'Unknown';
        $complaint = $this->repo->find($complaintId);

        if (!$complaint) {
            throw new \Exception("Complaint not found");
        }

        // منع الوصول لشكوى جهة أخرى
        if ($complaint->responsible_party !== $department) {
            throw new \Exception("Unauthorized");
        }

        // ✍️ إضافة الملاحظة
        $noteRecord = $this->historyRepo->create([
            'complaint_id' => $complaintId,
            'performed_by' => $employeeId,
            'performed_by_name' => $employeeName,
            'performed_by_type' => 'employee',
            'action' => 'note_added',
            'data' => ['note' => $note]
        ]);
        // تسجيل التتبع
        //  TraceAspect::record(
        //         'add_note',
        //         'complaint',
        //         $complaintId
        //     );
         TraceContext::setEntity('complaint', $complaint->id);
        // 🔔 إشعار المواطن
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendNoteAddedNotification($complaint, $note);

      //  🔓 فك القفل
        // DB::update(
        //     "UPDATE complaints
        //      SET locked_by = NULL, locked_at = NULL
        //      WHERE id = ? AND locked_by = ?",
        //     [$complaintId, $employeeId]
        // );
        $this->repo->unlock($complaintId, $employeeId);
       // 🧹 كسر كاش التاريخ وتفاصيل الشكوى
// Cache::forget("complaint_history_{$complaintId}");
// Cache::forget("track_complaint_{$complaint->reference_number}");
// Cache::forget("complaint_notes_{$complaintId}");
//  Cache::forget('admin_complaints');
//  Cache::forget("my_complaints_user_{$complaint->citizen_id}");
//  Cache::forget("department_complaints_{$complaint->responsible_party}");
event(new \App\Events\ComplaintStatusChanged($complaint));

        return $noteRecord;
    });
    
}

public function getById(int $id)
{
    return $this->repo->find($id);
}

    // public function getHistory(int $complaintId)
    // {
    //     return $this->historyRepo->forComplaint($complaintId);
    // }
    // عرض تاريخ الشكوى مع التخزين المؤقت
    public function getHistory(int $complaintId)
{
    return Cache::remember(
        "complaint_history_{$complaintId}", // مفتاح الكاش
        60, // مدة الكاش (ثانية)
        fn () => $this->historyRepo->forComplaint($complaintId)
    );

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
    $this->historyRepo->create([
        'complaint_id' => $complaintId,
        'performed_by' => $uploadedBy,
        'performed_by_name' => $complaint->citizen->username ?? 'Unknown',
        'performed_by_type' => 'user',
        'action' => 'attachment_added',
        'data' => [
            'id' => $attachment->id,
            'path' => $attachment->path,
            'original_name' => $attachment->original_name
        ]
    ]);
// 🧹 كسر كاش التتبع والتاريخ بعد إضافة مرفق
Cache::forget("complaint_history_{$complaintId}");
Cache::forget("track_complaint_{$complaint->reference_number}");

TraceContext::setEntity('attachments', $complaint->id);
    return $attachment;
}


// public function trackComplaint(string $reference)
// {
//     return $this->repo->findByReferenceWithAttachments($reference);
// }
// تتبع الشكوى حسب الرقم المرجعي مع التخزين المؤقت
public function trackComplaint(string $reference)
{
    return Cache::remember(
        "track_complaint_{$reference}", // مفتاح الكاش
        60, // مدة الكاش بالثواني
        fn () => $this->repo->findByReferenceWithAttachments($reference)
    );
}
//عرض كل شكاوي جهة معينة
// public function getDepartmentComplaints(string $department)
// {
//     return $this->repo->listForDepartment($department);
// }
//عرض كل شكاوي جهة معينة مع التخزين المؤقت
public function getDepartmentComplaints(string $department)
{
    return Cache::remember(
        "department_complaints_{$department}",
        30,
        fn () => $this->repo->listForDepartment($department)
    );
    TraceContext::setEntity('complaint', $complaint->id);
    
}


    // public function addNote(int $id, string $note, string $department, int $employeeId)
    // {
    //     $complaint = $this->repo->find($id);

    //     if (!$complaint) {
    //         throw new \Exception("Complaint not found");
    //     }

    //     // منع الوصول لشكوى جهة أخرى
    //  /*   if ($complaint->responsible_party !== $department) {
    //         throw new \Exception("Unauthorized");
    //     }*/

    //     // حفظ الملاحظة في history
    //     $noteRecord = $this->historyRepo->create([
    //         'complaint_id' => $id,
    //         'performed_by' => $employeeId,
    //         'action' => 'note_added',
    //         'data' => ['note' => $note]
    //     ]);

    //     // 3. ✅ إرسال إشعار للمواطن
    //     $notificationService = app(\App\Services\NotificationService::class);
    //     $notificationService->sendNoteAddedNotification($complaint, $note);

    //     return $noteRecord;
    // }

    public function getStatistics()
    {
    if (!auth()->user()->hasRole('admin')) {
        throw new HttpResponseException(
            response()->json(['message' => 'Unauthorized'], 403)
        );
    }

    return $this->repo->statistics();
    }




    

}