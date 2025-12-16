<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
class RolesAndPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // تنظيف الكاش (مهم)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =========================
        // 1️⃣ الصلاحيات
        // =========================
        $permissions = [
            // Complaints
            'view_all_complaints',
            'view_department_complaints',
            'change_complaint_status',
            'add_complaint_note',
            'request_more_info',

            // Users (admin)
            'create_employee',
            'update_employee',
            'delete_employee',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // =========================
        // 2️⃣ الأدوار
        // =========================
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // =========================
        // 3️⃣ ربط الصلاحيات بالأدوار
        // =========================

        // Admin → كل الصلاحيات
        $adminRole->syncPermissions(Permission::all());

        // Employee → صلاحيات محددة
        $employeeRole->syncPermissions([
            'view_department_complaints',
            'change_complaint_status',
            'add_complaint_note',
            'request_more_info',
        ]);
        // User::where('responsible_party', null)->each(function ($user) use ($adminRole) {
        //     $user->assignRole($adminRole);
        // });

        // User::whereNotNull('responsible_party')->each(function ($user) use ($employeeRole) {
        //     $user->assignRole($employeeRole);
        // });
    }
}
