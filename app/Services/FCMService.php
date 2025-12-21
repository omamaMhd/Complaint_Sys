<?php
// app/Services/FCMService.php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $messaging;
    protected $enabled;
    
    public function __construct()
    {
      
        $credentialsPath = storage_path('app/firebase-credentials.json');
                
        if (file_exists($credentialsPath)) {
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
            $this->enabled = true;
        } else {
            $this->enabled = false;
            Log::warning('FCM credentials not found, FCM disabled');
        }
    }
    
    public function sendToCitizen($citizenId, $title, $body, $data = [])
    {
        if (!$this->enabled) {
            Log::info('FCM disabled, notification not sent', ['citizen_id' => $citizenId]);
            return false;
        }
        
        $citizen = \App\Models\Citizen::find($citizenId);
        
        if (!$citizen || !$citizen->fcm_token) {
            return false;
        }
        
        return $this->sendNotification($citizen->fcm_token, $title, $body, $data);
    }
    
    private function sendNotification($token, $title, $body, $data = [])
    {
        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData(array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default'
                ]));
            
            $this->messaging->send($message);
            
            Log::info('FCM notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
                'body' => $body
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('FCM sending failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            
            return false;
        }
    }
}