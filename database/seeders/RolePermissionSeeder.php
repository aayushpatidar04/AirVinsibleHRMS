<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Application guard used by the web application.
     */
    private string $guardName = 'web';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         * Spatie caches roles and permissions.
         * Clear the cache before and after seeding.
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => $this->guardName,
                ]
            );
        }

        $roles = [
            'admin',
            'hr',
            'interviewer',
            'employee',
        ];

        foreach ($roles as $roleName) {
            Role::updateOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => $this->guardName,
                ]
            );
        }

        $this->assignAdminPermissions();
        $this->assignHrPermissions();
        $this->assignInterviewerPermissions();
        $this->assignEmployeePermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            sprintf(
                'Created %d permissions and configured application roles.',
                count($permissions)
            )
        );
    }

    /**
     * Return every permission used by the HRMS.
     *
     * Permissions for upcoming modules are included now so that future
     * modules do not require restructuring the permission foundation.
     */
    private function permissions(): array
    {
        return [
            /*
             |--------------------------------------------------------------------------
             | Dashboard
             |--------------------------------------------------------------------------
             */
            'dashboard.admin.view',
            'dashboard.hr.view',
            'dashboard.interviewer.view',
            'dashboard.employee.view',

            /*
             |--------------------------------------------------------------------------
             | Branch Management
             |--------------------------------------------------------------------------
             */
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',
            'branches.restore',

            /*
             |--------------------------------------------------------------------------
             | Employee Management
             |--------------------------------------------------------------------------
             */
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'employees.restore',
            'employees.import',
            'employees.export',
            'employees.reset-password',
            'employees.activate',
            'employees.deactivate',
            'employees.assign-role',
            'employees.assign-interviewer-rounds',
            'employees.view-sensitive-data',

            /*
             |--------------------------------------------------------------------------
             | Roles and Permissions
             |--------------------------------------------------------------------------
             */
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign-permissions',
            'permissions.view',

            /*
             |--------------------------------------------------------------------------
             | Candidate Management
             |--------------------------------------------------------------------------
             */
            'candidates.view',
            'candidates.view-branch',
            'candidates.view-all-branches',
            'candidates.create',
            'candidates.update',
            'candidates.delete',
            'candidates.restore',
            'candidates.view-documents',
            'candidates.download-documents',
            'candidates.approve',
            'candidates.reject-approval',
            'candidates.place-on-hold',
            'candidates.final-decision',
            'candidates.view-salary',
            'candidates.update-salary',
            'candidates.export',

            /*
             |--------------------------------------------------------------------------
             | Candidate Registration Forms
             |--------------------------------------------------------------------------
             */
            'registration-forms.view',
            'registration-forms.create',
            'registration-forms.update',
            'registration-forms.delete',
            'registration-forms.duplicate',
            'registration-forms.manage-fields',

            /*
             |--------------------------------------------------------------------------
             | QR Codes
             |--------------------------------------------------------------------------
             */
            'qr-codes.view',
            'qr-codes.create',
            'qr-codes.update',
            'qr-codes.delete',
            'qr-codes.download',
            'qr-codes.activate',
            'qr-codes.deactivate',

            /*
             |--------------------------------------------------------------------------
             | Interview Round Configuration
             |--------------------------------------------------------------------------
             */
            'interview-rounds.view',
            'interview-rounds.create',
            'interview-rounds.update',
            'interview-rounds.delete',
            'interview-rounds.reorder',
            'interview-rounds.assign-interviewers',

            /*
             |--------------------------------------------------------------------------
             | Interview Question Configuration
             |--------------------------------------------------------------------------
             */
            'interview-questions.view',
            'interview-questions.create',
            'interview-questions.update',
            'interview-questions.delete',
            'interview-questions.reorder',

            /*
             |--------------------------------------------------------------------------
             | Interview Scheduling and Management
             |--------------------------------------------------------------------------
             */
            'interviews.view',
            'interviews.view-assigned',
            'interviews.view-branch',
            'interviews.view-all-branches',
            'interviews.schedule',
            'interviews.reschedule',
            'interviews.cancel',
            'interviews.mark-no-show',
            'interviews.send-email',
            'interviews.assign-interviewer',
            'interviews.reassign-interviewer',
            'interviews.view-feedback',
            'interviews.view-history',

            /*
             |--------------------------------------------------------------------------
             | Interview Execution
             |--------------------------------------------------------------------------
             */
            'interviews.start',
            'interviews.save-response',
            'interviews.add-custom-question',
            'interviews.complete',
            'interviews.reject-candidate',
            'interviews.recommend-next-round',
            'interviews.move-next-round',
            'interviews.discuss-salary',

            /*
             |--------------------------------------------------------------------------
             | Recruitment Reports
             |--------------------------------------------------------------------------
             */
            'recruitment-reports.view-branch',
            'recruitment-reports.view-all-branches',
            'recruitment-reports.export',

            /*
             |--------------------------------------------------------------------------
             | Offer Management
             |--------------------------------------------------------------------------
             */
            'offers.view',
            'offers.view-branch',
            'offers.view-all-branches',
            'offers.create',
            'offers.update',
            'offers.delete',
            'offers.approve',
            'offers.release',
            'offers.resend',
            'offers.cancel',
            'offers.download',

            /*
             |--------------------------------------------------------------------------
             | Joining and Onboarding
             |--------------------------------------------------------------------------
             */
            'onboarding.view',
            'onboarding.view-branch',
            'onboarding.view-all-branches',
            'onboarding.create',
            'onboarding.update',
            'onboarding.manage-documents',
            'onboarding.verify-documents',
            'onboarding.confirm-joining',
            'onboarding.convert-to-employee',
            'onboarding.cancel',

            /*
             |--------------------------------------------------------------------------
             | Employee Letters
             |--------------------------------------------------------------------------
             */
            'employee-letters.view',
            'employee-letters.create',
            'employee-letters.update',
            'employee-letters.approve',
            'employee-letters.release',
            'employee-letters.download',
            'employee-letters.delete',

            /*
             |--------------------------------------------------------------------------
             | Attendance
             |--------------------------------------------------------------------------
             */
            'attendance.view-own',
            'attendance.view-branch',
            'attendance.view-all-branches',
            'attendance.mark',
            'attendance.update',
            'attendance.regularize',
            'attendance.approve-regularization',
            'attendance.export',

            /*
             |--------------------------------------------------------------------------
             | Shift Management
             |--------------------------------------------------------------------------
             */
            'shifts.view',
            'shifts.create',
            'shifts.update',
            'shifts.delete',
            'shifts.assign-employees',

            /*
             |--------------------------------------------------------------------------
             | Holiday Management
             |--------------------------------------------------------------------------
             */
            'holidays.view',
            'holidays.create',
            'holidays.update',
            'holidays.delete',

            /*
             |--------------------------------------------------------------------------
             | Leave Management
             |--------------------------------------------------------------------------
             */
            'leaves.view-own',
            'leaves.view-branch',
            'leaves.view-all-branches',
            'leaves.apply',
            'leaves.update-own',
            'leaves.cancel-own',
            'leaves.approve',
            'leaves.reject',
            'leave-types.view',
            'leave-types.create',
            'leave-types.update',
            'leave-types.delete',

            /*
             |--------------------------------------------------------------------------
             | Payroll
             |--------------------------------------------------------------------------
             */
            'payroll.view-own',
            'payroll.view-branch',
            'payroll.view-all-branches',
            'payroll.manage-salary-structure',
            'payroll.generate',
            'payroll.recalculate',
            'payroll.approve',
            'payroll.lock',
            'payroll.release',
            'payroll.export',
            'payroll.download-payslip',

            /*
             |--------------------------------------------------------------------------
             | Increment Management
             |--------------------------------------------------------------------------
             */
            'increments.view',
            'increments.create',
            'increments.update',
            'increments.approve',
            'increments.release-letter',
            'increments.cancel',

            /*
             |--------------------------------------------------------------------------
             | Company Settings
             |--------------------------------------------------------------------------
             */
            'company-settings.view',
            'company-settings.update',
            'company-settings.manage-letter-templates',
            'company-settings.manage-email-templates',
            'company-settings.manage-payroll-settings',

            /*
             |--------------------------------------------------------------------------
             | Audit Logs
             |--------------------------------------------------------------------------
             */
            'audit-logs.view',
            'audit-logs.export',
        ];
    }

    /**
     * Admin receives every application permission.
     */
    private function assignAdminPermissions(): void
    {
        $admin = Role::findByName('admin', $this->guardName);

        $admin->syncPermissions(
            Permission::where('guard_name', $this->guardName)->get()
        );
    }

    /**
     * HR operates recruitment and employee processes for their own branch.
     */
    private function assignHrPermissions(): void
    {
        $hr = Role::findByName('hr', $this->guardName);

        $hr->syncPermissions([
            'dashboard.hr.view',

            'branches.view',

            'employees.view',
            'employees.create',
            'employees.update',
            'employees.import',
            'employees.export',
            'employees.reset-password',
            'employees.activate',
            'employees.deactivate',
            'employees.assign-interviewer-rounds',
            'employees.view-sensitive-data',

            'candidates.view',
            'candidates.view-branch',
            'candidates.create',
            'candidates.update',
            'candidates.view-documents',
            'candidates.download-documents',
            'candidates.approve',
            'candidates.reject-approval',
            'candidates.place-on-hold',
            'candidates.final-decision',
            'candidates.view-salary',
            'candidates.update-salary',
            'candidates.export',

            'registration-forms.view',
            'qr-codes.view',
            'qr-codes.download',

            'interview-rounds.view',
            'interview-questions.view',

            'interviews.view',
            'interviews.view-branch',
            'interviews.schedule',
            'interviews.reschedule',
            'interviews.cancel',
            'interviews.mark-no-show',
            'interviews.send-email',
            'interviews.assign-interviewer',
            'interviews.reassign-interviewer',
            'interviews.view-feedback',
            'interviews.view-history',
            'interviews.move-next-round',
            'interviews.discuss-salary',

            /*
             * HR can also conduct an interview when assigned.
             */
            'interviews.view-assigned',
            'interviews.start',
            'interviews.save-response',
            'interviews.add-custom-question',
            'interviews.complete',
            'interviews.reject-candidate',
            'interviews.recommend-next-round',

            'recruitment-reports.view-branch',
            'recruitment-reports.export',

            'offers.view',
            'offers.view-branch',
            'offers.create',
            'offers.update',
            'offers.release',
            'offers.resend',
            'offers.cancel',
            'offers.download',

            'onboarding.view',
            'onboarding.view-branch',
            'onboarding.create',
            'onboarding.update',
            'onboarding.manage-documents',
            'onboarding.verify-documents',
            'onboarding.confirm-joining',
            'onboarding.convert-to-employee',
            'onboarding.cancel',

            'employee-letters.view',
            'employee-letters.create',
            'employee-letters.update',
            'employee-letters.release',
            'employee-letters.download',

            'attendance.view-branch',
            'attendance.mark',
            'attendance.update',
            'attendance.approve-regularization',
            'attendance.export',

            'shifts.view',
            'shifts.assign-employees',

            'holidays.view',

            'leaves.view-branch',
            'leaves.approve',
            'leaves.reject',
            'leave-types.view',

            'payroll.view-branch',
            'payroll.manage-salary-structure',
            'payroll.generate',
            'payroll.recalculate',
            'payroll.release',
            'payroll.export',
            'payroll.download-payslip',

            'increments.view',
            'increments.create',
            'increments.update',
            'increments.release-letter',
        ]);
    }

    /**
     * Interviewer receives access only to assigned interviews.
     */
    private function assignInterviewerPermissions(): void
    {
        $interviewer = Role::findByName('interviewer', $this->guardName);

        $interviewer->syncPermissions([
            'dashboard.interviewer.view',

            'candidates.view',
            'candidates.view-documents',
            'candidates.download-documents',

            'interviews.view',
            'interviews.view-assigned',
            'interviews.view-feedback',
            'interviews.view-history',
            'interviews.start',
            'interviews.save-response',
            'interviews.add-custom-question',
            'interviews.complete',
            'interviews.reject-candidate',
            'interviews.recommend-next-round',
            'interviews.discuss-salary',
        ]);
    }

    /**
     * Employee permissions are intentionally limited to self-service.
     */
    private function assignEmployeePermissions(): void
    {
        $employee = Role::findByName('employee', $this->guardName);

        $employee->syncPermissions([
            'dashboard.employee.view',

            'attendance.view-own',
            'attendance.regularize',

            'holidays.view',

            'leaves.view-own',
            'leaves.apply',
            'leaves.update-own',
            'leaves.cancel-own',

            'payroll.view-own',
            'payroll.download-payslip',

            'employee-letters.view',
            'employee-letters.download',
        ]);
    }
}