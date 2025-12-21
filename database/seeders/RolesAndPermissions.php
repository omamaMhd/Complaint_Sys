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
            'Department_Complaints',
            'Change_Status',
            'Add_Note',

 /*           'view_all_complaints',
            'request_more_info',
            'list_user_complaints',


            // Users (admin)
            'create_employee',
            'delete_employee',
            'employee.view_all',              // عرض جميع الموظفين
          //  'permission.manage',              // إدارة الصلاحيات
            
            // صلاحيات التقارير
            'report.generate',                // إنشاء تقارير
            'report.export',                  // تصدير التقارير
            
            // صلاحيات النظام
            'system.monitor',                 // مراقبة النظام
            'backup.manage',                  // إدارة النسخ الاحتياطي
*/
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

        // Admin → منح كل الصلاحيات
        $adminRole->syncPermissions(Permission::all());

        // Employee → صلاحيات محددة
        // فارغ افتراضيا
        $employeeRole->syncPermissions([
        //   'view_department_complaints', 'change_complaint_status',
        //   'add_complaint_note', 'request_more_info',
        //   'list_user_complaints',
        ]);

    }
}
