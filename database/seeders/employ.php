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
        $counter = 1;

        $plainPassword2 = 'Temp1234';
        foreach ($departments as $dept) {
            for ($i = 1; $i <= 3; $i++) {

            $employee = User::firstOrCreate(
              ['mobile' => $baseNumber . str_pad($counter, 6, '0', STR_PAD_LEFT)],
              [
                'username' => "موظف $i - $dept",
                'password' => Hash::make($plainPassword2),
                'responsible_party' => $dept,
                'must_change_password' => true,
              ]
);

                $employee->assignRole($employeeRole);
                $counter++;
            }
        }
    }
}
