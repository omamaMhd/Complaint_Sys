<?php
 
// namespace App\Listeners;

// use App\Events\ComplaintStatusChanged;
// use Illuminate\Support\Facades\Cache;

// class ClearComplaintCache
// {
//     public function handle(ComplaintStatusChanged $event): void
//     {
//         $complaintId = $event->complaint;

//         //Cache::forget("complaint_{$complaintId}");
//         Cache::forget("complaint_history_{$complaintId}");
//         Cache::forget("department_complaints_{$event->complaint->responsible_party}");
//         Cache::forget("admin_complaints");
//          Cache::forget("my_complaints_user_{$event->complaint->citizen_id}");
//           Cache::forget("complaint_history_{$complaintId}");
//  Cache::forget("track_complaint_{$complaint->reference_number}");
//  Cache::forget("complaint_notes_{$complaintId}");

//     }
// } 


namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearComplaintCache
{
    public function handle($event)
    {
        // // هنا نستخدم الـ property من الحدث مباشرة
        // $complaint = $event->complaint;

        // // مسح كاش المواطن
        // if (isset($complaint->citizen_id)) {
        //     Cache::forget("citizen_{$complaint->citizen_id}_complaints");
        // }

        // // مسح كاش الجهة المسؤولة
        // if (isset($complaint->responsible_party)) {
        //     Cache::forget("department_{$complaint->responsible_party}_complaints");
        // }

        // // مسح كاش الشكوى نفسها
        // Cache::forget("complaint_{$complaint->id}");

        $complaint = $event->complaint;

        // كاش الشكاوى للمواطن
        Cache::forget("my_complaints_user_{$complaint->citizen_id}_complaints");

        // كاش الشكاوى للجهة
        Cache::forget("department_complaints_{$complaint->responsible_party}_complaints");

        // كاش الشكوى نفسها
        Cache::forget("complaint_notes_{$complaint->id}");

        // 🔥 كاش تاريخ الشكوى (الجديد)
        Cache::forget("complaint_history_{$complaint->id}");
    }
}
