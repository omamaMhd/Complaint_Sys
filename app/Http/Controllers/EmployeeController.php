<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $service
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->service->login(
            $request->mobile,
            $request->password
        );

        // لازم يغير كلمة السر
        if ($result['must_change_password']) {
            return response()->json([
                'message' => 'You must change your password first',
                'must_change_password' => true,
                'token' => $result['token'], // توكن مؤقت
            ], 403);
        }

        // تسجيل دخول طبيعي
        return response()->json([
            'token' => $result['token'],
            'user' => $result['user'],
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        // المستخدم موثّق بتوكن تغيير كلمة السر
        $user = $request->user();

        $this->service->changePassword(
            $user,
            $request->new_password
        );

        // ❌ لا نرجع توكن
        return response()->json([
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }

    public function logout(Request $request)
    {
        $this->service->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
