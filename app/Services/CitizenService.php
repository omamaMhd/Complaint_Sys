<?php
namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\NotificationService;


use Exception;

class CitizenService
{
    const MAX_FAILED_ATTEMPTS = 8;
    const LOCK_MINUTES = 10;

    protected $repo;
    protected $notificationService;

    public function __construct(CitizenRepositoryInterface $repo, NotificationService $notificationService)
    {
        $this->repo = $repo;
        $this->notificationService = $notificationService;
    }

    public function register(array $data)
    {
        // prepare data
        $data['password'] = Hash::make($data['password']);
        $data['verification_code'] = rand(100000, 999999); 
        $data['code_expires_at'] = now()->addMinutes(5);
        $data['is_verified'] = false;

        $citizen = $this->repo->create($data);

        // dispatch whatsapp send job (or call directly)
        event(new \App\Events\CitizenVerificationCodeGenerated($citizen));

        return $citizen;
    }

    public function verify(string $mobile, int $code)
    {
        $citizen = $this->repo->findByMobile($mobile);
        if (!$citizen) return ['ok' => false, 'message' => 'Invalid mobile or user not found.'];

        // check expiry and code
        if (!$citizen->mobile || !$citizen->code_expires_at || now()->isAfter($citizen->code_expires_at)) {
            return ['ok' => false, 'message' => 'Verification code expired.'];
        }

        if ((int)$citizen->verification_code !== (int)$code) {
            return ['ok' => false, 'message' => 'Incorrect verification code.'];
        }

        $citizen->is_verified = true;
        $citizen->verification_code = null;
        $citizen->code_expires_at = null;
        $this->repo->save($citizen);

        // generate token
        $token = $citizen->createToken('auth_token')->plainTextToken;

        // Log or event
        Log::info('citizen.verified', ['citizen_id' => $citizen->id]);

        return ['ok' => true, 'token' => $token, 'citizen' => $citizen];
        
    }

    public function resendCode(string $mobile)
    {
        $citizen = $this->repo->findByMobile($mobile);
        if (!$citizen) return ['ok' => false, 'message' => 'User not found.'];

        if ($citizen->is_verified) return ['ok' => false, 'message' => 'Already verified.'];

        $citizen->verification_code = rand(100000, 999999);
        $citizen->code_expires_at = now()->addMinutes(5);
        $this->repo->save($citizen);

        event(new \App\Events\CitizenVerificationCodeGenerated($citizen));

        return ['ok' => true];
    }

public function login(string $mobile, string $password)
    {
        $citizen = $this->repo->findByMobile($mobile);

        if (!$citizen) {
            return ['ok' => false, 'message' => 'Invalid mobile number or password.'];
        }

        /** 🔒 هل الحساب مقفول؟ */
        if ($citizen->locked_until && now()->lessThan($citizen->locked_until)) {
            return [
                'ok' => false,
                'message' => 'Account is temporarily locked. Please try again later.'
            ];
        }

        /** ❌ كلمة مرور خاطئة */
        if (!Hash::check($password, $citizen->password)) {

            $citizen->failed_login_attempts++;
            $citizen->last_failed_login_at = now();

            /** 🚨 وصل الحد الأعلى */
            if ($citizen->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {

                $citizen->locked_until = now()->addMinutes(self::LOCK_MINUTES);

                // 🔔 إشعار أمني
                $this->notificationService->sendSecurityAlertNotification(
                    $citizen,
                    'Multiple failed login attempts'
                );
            }

            $this->repo->save($citizen);

            return ['ok' => false, 'message' => 'Invalid mobile number or password.'];
        }

        /** ✅ تسجيل دخول ناجح */
        if (!$citizen->is_verified) {
            return ['ok' => false, 'message' => 'Account not verified.'];
        }

        // تصفير العدّادات
        $citizen->failed_login_attempts = 0;
        $citizen->locked_until = null;
        $citizen->last_failed_login_at = null;
        $this->repo->save($citizen);

        $token = $citizen->createToken('auth_token')->plainTextToken;

        return [
            'ok' => true,
            'citizen' => $citizen,
            'token' => $token
        ];
    }
    public function logout($citizen)
    {

          if (!$citizen) {
              return [
                 'status' => 401,
                 'message' => 'Citizen not authenticated.'
            ];
        }
        $token = $citizen->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return [
            'status' => 200,
           'message' => 'Logged out successfully.'
        ];
    }    
}
