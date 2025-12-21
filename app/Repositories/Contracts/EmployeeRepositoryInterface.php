<?php


namespace App\Repositories\Contracts;

use App\Models\User;

interface EmployeeRepositoryInterface
{
    public function findEmployeeByMobile(string $mobile): ?User;
}
