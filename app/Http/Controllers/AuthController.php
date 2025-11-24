<?php

namespace App\Http\Controllers;

use App\Http\Requests\CitizenRegisterRequest;
use App\Http\Requests\CitizenVerifyRequest;
use App\Http\Requests\CitizenResendRequest;
use App\Http\Requests\CitizenLoginRequest;
use App\Http\Requests\CitizenLogoutRequest;
use App\Services\CitizenService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    protected $service;
    public function __construct(CitizenService $service)
    {
        $this->service = $service;
    }

    public function register(CitizenRegisterRequest $request)
    {
        $citizen = $this->service->register($request->only(['username','mobile','password']));
        return response()->json([
            'citizenId' => $citizen->id,
           // 'verification_token' => $citizen->verification_token,
            'message' => 'Account created. Verification code sent via WhatsApp.'
        ], 201);
    }

    public function verifyCode(CitizenVerifyRequest $request)
    {
        $res = $this->service->verify($request->mobile, (int)$request->verification_code);
        if (!$res['ok']) {
            return response()->json(['message' => $res['message']], 400);
        }

        return response()->json([
            'message' => 'Verified successfully.',
            'access_token' =>  $res['token'],
            'token_type' => 'Bearer'
        ], 200);
    }

    public function resendVerificationCode(CitizenResendRequest $request)
    {
        $res = $this->service->resendCode($request->mobile);
        if (!$res['ok']) return response()->json(['message' => $res['message']], 400);
        return response()->json(['message' => 'Verification code resent.'], 200);
    }

    public function login(CitizenLoginRequest $request)
    {
        $res = $this->service->login($request->mobile, $request->password);

        if (!$res['ok']) {
            return response()->json(['message' => $res['message']], 401);
        }

        return response()->json([
            'message' => 'Login successfully.',
            'citizen' => [
                'id' => $res['citizen']->id,
                'username' => $res['citizen']->username,
                'mobile' => $res['citizen']->mobile,
                'is_verified' => (bool)$res['citizen']->is_verified,
            ],
            'access_token' => $res['token'],
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
{
    $res = $this->service->logout($request);

    return response()->json([
        'message' => $res['message']
    ], $res['status']);
}




}







