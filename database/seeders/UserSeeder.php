<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        foreach (['admin','interviewer','hr','employee'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $mumbai    = Branch::where('code', 'MUM01')->first();
        $delhi     = Branch::where('code', 'DEL01')->first();
        $bangalore = Branch::where('code', 'BLR01')->first();
        $jaipur    = Branch::where('code', 'JAI01')->first();

        /* ── 1. System Admin ── */
        $admin = User::firstOrCreate(['email' => 'admin@hrms.com'], [
            'first_name'        => 'System',
            'last_name'         => 'Admin',
            'phone'             => '9000000000',
            'employee_id'       => 'ADM001',
            'designation'       => 'System Administrator',
            'department'        => 'IT',
            'branch_id'         => $mumbai?->id,
            'password'          => Hash::make('password'),
            'employment_type'   => 'full_time',
            'employment_status' => 'active',
            'date_of_joining'   => '2020-01-01',
            'is_active'         => true,
            'can_interview'     => true,
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles(['admin','interviewer']);

        /* ── 2. HR Manager (has hr + interviewer role) ── */
        $hr = User::firstOrCreate(['email' => 'hr@hrms.com'], [
            'first_name'        => 'Neha',
            'last_name'         => 'Joshi',
            'phone'             => '9000000001',
            'employee_id'       => 'HR001',
            'designation'       => 'HR Manager',
            'department'        => 'Human Resources',
            'branch_id'         => $mumbai?->id,
            'password'          => Hash::make('password'),
            'employment_type'   => 'full_time',
            'employment_status' => 'active',
            'date_of_joining'   => '2021-03-15',
            'is_active'         => true,
            'can_interview'     => true,
            'email_verified_at' => now(),
        ]);
        $hr->syncRoles(['hr','interviewer']);

        /* ── 3. Employees who are also interviewers ── */
        $interviewers = [
            [
                'first_name'  => 'Rahul',   'last_name'   => 'Sharma',
                'email'       => 'rahul@hrms.com',
                'phone'       => '9111000001', 'emp'        => 'EMP001',
                'designation' => 'Senior Sales Executive',
                'department'  => 'Sales',
                'branch'      => $mumbai,
                'roles'       => ['interviewer'],
            ],
            [
                'first_name'  => 'Priya',   'last_name'   => 'Mehta',
                'email'       => 'priya@hrms.com',
                'phone'       => '9111000002', 'emp'        => 'EMP002',
                'designation' => 'Team Lead – Sales',
                'department'  => 'Sales',
                'branch'      => $mumbai,
                'roles'       => ['interviewer'],
            ],
            [
                'first_name'  => 'Amit',    'last_name'   => 'Verma',
                'email'       => 'amit@hrms.com',
                'phone'       => '9111000003', 'emp'        => 'EMP003',
                'designation' => 'Operations Manager',
                'department'  => 'Operations',
                'branch'      => $delhi,
                'roles'       => ['interviewer'],
            ],
            [
                'first_name'  => 'Sneha',   'last_name'   => 'Gupta',
                'email'       => 'sneha@hrms.com',
                'phone'       => '9111000004', 'emp'        => 'EMP004',
                'designation' => 'Quality Analyst',
                'department'  => 'Quality',
                'branch'      => $bangalore,
                'roles'       => ['interviewer'],
            ],
            [
                'first_name'  => 'Vikram',  'last_name'   => 'Singh',
                'email'       => 'vikram@hrms.com',
                'phone'       => '9111000005', 'emp'        => 'EMP005',
                'designation' => 'Branch Manager',
                'department'  => 'Management',
                'branch'      => $jaipur,
                'roles'       => ['interviewer'],
            ],
            [
                'first_name'  => 'Anjali',  'last_name'   => 'Pandey',
                'email'       => 'anjali@hrms.com',
                'phone'       => '9111000006', 'emp'        => 'EMP006',
                'designation' => 'HR Executive',
                'department'  => 'Human Resources',
                'branch'      => $jaipur,
                'roles'       => ['hr', 'interviewer'],
            ],
        ];

        foreach ($interviewers as $d) {
            $user = User::firstOrCreate(['email' => $d['email']], [
                'first_name'        => $d['first_name'],
                'last_name'         => $d['last_name'],
                'phone'             => $d['phone'],
                'employee_id'       => $d['emp'],
                'designation'       => $d['designation'],
                'department'        => $d['department'],
                'branch_id'         => $d['branch']?->id,
                'password'          => Hash::make('password'),
                'employment_type'   => 'full_time',
                'employment_status' => 'active',
                'date_of_joining'   => now()->subMonths(rand(6,36))->format('Y-m-d'),
                'is_active'         => true,
                'can_interview'     => true,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles($d['roles']);
        }

        /* ── 4. Regular employees (no interview rights yet) ── */
        $employees = [
            ['first_name'=>'Deepak',  'last_name'=>'Rao',    'email'=>'deepak@hrms.com',  'phone'=>'9222000001','emp'=>'EMP007','designation'=>'Sales Executive', 'dept'=>'Sales',    'branch'=>$mumbai],
            ['first_name'=>'Sunita',  'last_name'=>'Kaur',   'email'=>'sunita@hrms.com',  'phone'=>'9222000002','emp'=>'EMP008','designation'=>'Customer Service','dept'=>'Support',  'branch'=>$delhi],
            ['first_name'=>'Karan',   'last_name'=>'Malhotra','email'=>'karan@hrms.com',   'phone'=>'9222000003','emp'=>'EMP009','designation'=>'Jr. Advisor',    'dept'=>'Sales',    'branch'=>$bangalore],
        ];

        foreach ($employees as $d) {
            $user = User::firstOrCreate(['email' => $d['email']], [
                'first_name'        => $d['first_name'],
                'last_name'         => $d['last_name'],
                'phone'             => $d['phone'],
                'employee_id'       => $d['emp'],
                'designation'       => $d['designation'],
                'department'        => $d['dept'],
                'branch_id'         => $d['branch']?->id,
                'password'          => Hash::make('password'),
                'employment_type'   => 'full_time',
                'employment_status' => 'active',
                'date_of_joining'   => now()->subMonths(rand(1,12))->format('Y-m-d'),
                'is_active'         => true,
                'can_interview'     => false,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles(['employee']);
        }

        $this->command->info('✔ ' . User::count() . ' employees seeded.');
        $this->command->info('  admin@hrms.com / password  (Admin + Interviewer)');
        $this->command->info('  hr@hrms.com    / password  (HR + Interviewer)');
        $this->command->info('  rahul@hrms.com / password  (Interviewer)');
    }
}