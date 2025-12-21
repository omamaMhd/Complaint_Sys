<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function findEmployeeByMobile(string $mobile): ?User
    {
        return User::where('mobile', $mobile)
            ->first();
    }
}
