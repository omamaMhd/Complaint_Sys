<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Citizen;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{

    public function register(Request $request )
    {
        $request->validate([
            'username' => 'required',
            'mobile' => 'required|max:10|unique:citizens',
            'password' => 'required|string|min:8',
        ]);

        $verificationCode = rand(10000, 99999);

          $citizen = Citizen::create([
            'username' => $request->username,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'verification_code' => $verificationCode,
            'code_expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);
        
        $this->sendWhatsAppMessage($citizen->mobile, $verificationCode);

        return response()->json([ 'citizenId' => $citizen->id, 
        'message' => 'Account has been created, verificationCode has been sent via WhatsApp.'], 201);
    }

    private function sendWhatsAppMessage($mobile, $code) {
        $client = new Client(); 
        $endpoint = env('ULTRAMSG_ENDPOINT', 'https://api.ultramsg.com/instance1****/messages/chat');

        $response = $client->post($endpoint, [
            'json' => [
              'token' => env('ULTRAMSG_API_TOKEN'),
              'to' => $mobile,
              'body' => "Your verification code: {$code}" // رسالة واضحة
            ]
        ]);
    }
 
    public function verifyCode(Request $request)
    {
        $request->validate([
            'mobile' => 'required|max:10', 
            'verification_code' => 'required|integer', 
        ]);

        $citizen = Citizen::where('mobile', $request->mobile)->first();

        if (!$citizen) {
            return response()->json(['message' => 'Invalid request or citizen not found.'], 404);
        }
    
        $isCodeCorrect = (int)$citizen->verification_code === (int)$request->verification_code;
        $isCodeExpired = $citizen->code_expires_at === null || now()->isAfter($citizen->code_expires_at);

        if ($isCodeExpired || !$isCodeCorrect) {
            return response()->json(['message' => 'The verification code is incorrect or expired.'], 400);
        }

        // الحالة الصح
        $citizen->is_verified = true;
        $citizen->verification_code = null;
        $citizen->code_expires_at = null;
        $citizen->save();
        
        $token = $citizen->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Verified successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
}

    public function resendVerificationCode(Request $request)
    {

        $request->validate([
        'mobile' => 'required|max:10', 
        ]);

        $citizen = Citizen::where('mobile', $request->mobile)->first();
        if (!$citizen) {
        return response()->json(['message' => 'User not found.'], 404);
    }
        if ($citizen->is_verified) {
            return response()->json(['message' => 'The account has been pre-verified.'], 400);

        }

        $verificationCode = rand(10000, 99999);
        $citizen->verification_code = $verificationCode;
        $citizen->code_expires_at = now()->addMinutes(5);
        $citizen->save();

        $this->sendWhatsAppMessage($citizen->mobile, $verificationCode);

    return response()->json(['message' => 'Verification code has been sent again.'], 200);
}

    public function login(Request $request)
    {
       $request->validate([
           'mobile' => 'required|max:10',
           'password' => 'required|min:8',
       ]);
   
       $citizen = Citizen::where('mobile', $request->mobile)->first();
      
       if (!$citizen || !Hash::check($request->password, $citizen->password)) {
        return response()->json(['message' => 'Invalid mobile number or password.'], 401);
       }

      if (!$citizen->is_verified) {
        return response()->json(['message' => 'Account not verified. Please verify your mobile number first.'], 403);
      } 

       $token = $citizen->createToken('auth_token')->plainTextToken;


       return response()->json([
           'message' => 'Login successfully.',
           'citizen' => [
            'id' => $citizen->id,
            'username' => $citizen->username,
            'mobile' => $citizen->mobile,
            'is_verified' => (bool)$citizen->is_verified
            ],
           'access_token' => $token,
           'token_type' => 'Bearer',
       ], 200);
    }

    public function logout(Request $request)
    {
        $citizen = $request->user('sanctum');
        if (!$citizen) {
        return response()->json(['message' => 'Citizen not authenticated.'], 401);
    }
        $current = $citizen->currentAccessToken();
        if ($current) {
            $current->delete();
    }

    return response()->json(['message' => 'Logged out successfully.'], 200);
    }

}
