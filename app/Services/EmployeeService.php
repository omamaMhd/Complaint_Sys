<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $repository
    ) {}
     
    public function login(string $mobile, string $password): array
   {
    // جلب آخر نسخة من المستخدم
    $employee = $this->repository->findEmployeeByMobile($mobile);

    if (! $employee || ! Hash::check($password, $employee->password)) {
        throw ValidationException::withMessages([
            'credentials' => ['Invalid mobile or password'],
        ]);
    }

    if ($employee->must_change_password) {
         // إنشاء توكن بدون صلاحيات للواجهات التي تتطلب تغيير كلمة السر
        $token = $employee->createToken(
            'change-password-token',
            ['change-password']
        )->plainTextToken;

        return [
            'must_change_password' => true,
            'token' => $token,
        ];
    }
 // توكن عادي مع صلاحيات كاملة
    $token = $employee->createToken('employee-token')->plainTextToken;

    return [
        'must_change_password' => false,
        'token' => $token,
        'user' => $employee,
     //   'user' => $employee->only(['id', 'username', 'mobile', 'responsible_party'])
    ];
}


    public function changePassword(User $user, string $newPassword): void
    {
        if (! $user->must_change_password) {
            throw ValidationException::withMessages([
                'password' => ['Password change is not required'],
            ]);
        }

         $user->password = Hash::make($newPassword);
         $user->must_change_password = false;
         $user->save(); 


        // حذف توكن تغيير كلمة السر
        $user->currentAccessToken()->delete();
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
