<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class employ extends Seeder
{
    public function run(): void
    {
        // ========================
        // Roles (Spatie)
        // ========================
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // ========================
        // Admin Account
        // ========================
        $admin = User::firstOrCreate(
            ['mobile' => '0987654321'],
            [
                'username' => 'System Admin',
                'password' => Hash::make('admin123'),
                'responsible_party' => null,
            ]
        );
        $admin->assignRole($adminRole);

        // ========================
        // Employees
        // ========================
        $departments = [
            'وزارة الداخلية',
            'وزارة الصحة',
            'وزارة التربية والتعليم',
            'وزارة النقل',
            'وزارة المالية',
            'البلدية',
            'هيئة المياه',
            'هيئة الكهرباء',
            'شرطة المرور'
        ];

        $baseNumber = '0999';
        $plainPassword = 'password123';
        $counter = 1;

        foreach ($departments as $dept) {
            for ($i = 1; $i <= 3; $i++) {

                $employee = User::firstOrCreate(
                    ['mobile' => $baseNumber . str_pad($counter, 6, '0', STR_PAD_LEFT)],
                    [
                        'username' => "موظف $i - $dept",
                        'password' => Hash::make($plainPassword),
                        'responsible_party' => $dept
                    ]
                );

                $employee->assignRole($employeeRole);
                $counter++;
            }
        }
    }
}

// namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// use Illuminate\Database\Seeder;
// use App\Models\User;
// use Illuminate\Support\Facades\Hash;
// class employ extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//           $departments = [
//             'وزارة الداخلية',
//     'وزارة الصحة',
//     'وزارة التربية والتعليم',
//     'وزارة النقل',
//     'وزارة المالية',
//     'البلدية',
//     'هيئة المياه',
//     'هيئة الكهرباء',
//     'شرطة المرور'
//         ];
//  $baseNumber = '0999';
//  $plainPassword = 'password123';
//  $counter = 1;
//         foreach ($departments as $dept) {

//             // إنشاء 3 موظفين لكل جهة
//             for ($i = 1; $i <= 3; $i++) {
//                 User::create([
//                     'username' => "موظف $i - $dept",
//                     'mobile' => $baseNumber . str_pad($counter, 6, '0', STR_PAD_LEFT),
//                     'password' => Hash::make($plainPassword),
//                     'role' => 'employee',
//                     'responsible_party' => $dept
//                 ]);
//                 $counter++;
//             }
//         }

//         // إنشاء مدير النظام
//         User::create([
//             'username' => 'System Admin',
//             'mobile' => '0987654321',
//             'password' => Hash::make('admin123'),
//             'role' => 'admin',
//             'responsible_party' => null,
//         ]);
//     }
// }
