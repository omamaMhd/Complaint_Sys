<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\Citizen;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $fcmService;
    
    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    
    /**
     * 1. تغيير حالة الشكوى
     */
    public function sendStatusChangeNotification($complaint, $oldStatus, $newStatus)
    {
        $citizen = $complaint->citizen;
        
        if (!$citizen) {
            Log::warning('No citizen found for complaint', ['complaint_id' => $complaint->id]);
            return false;
        }
        
        // أ. حفظ في جدول notifications
        $this->saveToDatabase($citizen, 'status_changed', [
            'title' => 'تحديث حالة الشكوى',
            'message' => "تم تغيير حالة شكواك رقم {$complaint->reference_number} من {$oldStatus} إلى {$newStatus}",
            'complaint_id' => $complaint->id,
            'reference_number' => $complaint->reference_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
        
        // ب. إرسال FCM (إذا موجود)
        if ($citizen->fcm_token) {
            return $this->fcmService->sendToCitizen($citizen->id,
                '📋 تحديث حالة الشكوى',
                "تم تغيير حالة شكواك رقم {$complaint->reference_number} إلى {$newStatus}",
                [
                    'type' => 'status_changed',
                    'complaint_id' => $complaint->id,
                    'reference_number' => $complaint->reference_number
                ]
            );
        }
        
        return true;
    }
    
    /**
     * 2. إضافة ملاحظة على الشكوى
     */
    public function sendNoteAddedNotification($complaint, $note)
    {
        $citizen = $complaint->citizen;
        
        if (!$citizen) return false;
        
        $notePreview = strlen($note) > 50 ? substr($note, 0, 50) . '...' : $note;
        
        // أ. حفظ في جدول notifications
        $this->saveToDatabase($citizen, 'note_added', [
            'title' => 'ملاحظة جديدة',
            'message' => "تم إضافة ملاحظة جديدة على شكواك رقم {$complaint->reference_number}",
            'complaint_id' => $complaint->id,
            'reference_number' => $complaint->reference_number,
            'note_preview' => $notePreview
        ]);
        
        // ب. إرسال FCM
        if ($citizen->fcm_token) {
            return $this->fcmService->sendToCitizen($citizen->id,
                '📝 ملاحظة جديدة',
                "تم إضافة ملاحظة جديدة على شكواك رقم {$complaint->reference_number}",
                [
                    'type' => 'note_added',
                    'complaint_id' => $complaint->id,
                    'reference_number' => $complaint->reference_number
                ]
            );
        }
        
        return true;
    }
    
    /**
     * 3. استلام الشكوى (عند إنشاء شكوى جديدة)
     */
    public function sendComplaintCreatedNotification($complaint)
    {
        $citizen = $complaint->citizen;
        
        if (!$citizen) return false;
        
        // أ. حفظ في جدول notifications
        $this->saveToDatabase($citizen, 'complaint_created', [
            'title' => 'تم استلام شكواك',
            'message' => "تم استلام شكواك رقم {$complaint->reference_number} بنجاح",
            'complaint_id' => $complaint->id,
            'reference_number' => $complaint->reference_number
        ]);
        
        // ب. إرسال FCM
        if ($citizen->fcm_token) {
            return $this->fcmService->sendToCitizen($citizen->id,
                '✅ تم استلام شكواك',
                "تم استلام شكواك رقم {$complaint->reference_number} بنجاح",
                [
                    'type' => 'complaint_created',
                    'complaint_id' => $complaint->id,
                    'reference_number' => $complaint->reference_number
                ]
            );
        }
        
        return true;
    }
    
    /**
     * دالة مساعدة: حفظ في قاعدة البيانات
     */
    private function saveToDatabase(Citizen $citizen, $type, $data)
    {
        $citizen->notifications()->create([
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);
        
        Log::info('Notification saved to database', [
            'citizen_id' => $citizen->id,
            'type' => $type,
            'data' => $data
        ]);
    }
}