<?php
// app/Http/Controllers/CitizenNotificationController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenNotificationController extends Controller
{
    /**
     * جلب جميع إشعارات المواطن
     */
    public function index(Request $request)
    {
        $citizen = Auth::user(); // المواطن المصادق
        
        $notifications = $citizen->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'human_time' => $notification->created_at->diffForHumans()
                ];
            }),
            'unread_count' => $citizen->unreadNotifications()->count(),
            'total' => $notifications->total()
        ]);
    }
    
    /**
     * تعيين إشعار كمقروء
     */
    public function markAsRead($id)
    {
        $citizen = Auth::user();
        
        $notification = $citizen->notifications()->findOrFail($id);

         if (!$notification) {
        return response()->json([
            'message' => 'Notification not found'
        ], 404);
    }

        $notification->markAsRead();
        
        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => $citizen->unreadNotifications()->count()
        ]);
    }
    
    
    /**
     * تحديث FCM token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);
        
        $citizen = Auth::user();
        $citizen->updateFcmToken($request->fcm_token);
        
        return response()->json([
            'message' => 'FCM token updated successfully'
        ]);
    }
}