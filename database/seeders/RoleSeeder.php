<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = ['admin', 'interviewer', 'hr', 'employee'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Permissions (optional — can be expanded later)
        $permissions = [
            // Branch
            'branch.view', 'branch.create', 'branch.edit', 'branch.delete',
            // Employee
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'employee.assign-role',
            // Candidate
            'candidate.view', 'candidate.create', 'candidate.edit', 'candidate.delete',
            'candidate.update-status',
            // Interview
            'interview.conduct', 'interview.view-all',
            // Round
            'round.create', 'round.edit', 'round.delete',
            // Form
            'form.create', 'form.edit', 'form.delete',
            // QR
            'qr.create', 'qr.manage',
            // Reports
            'report.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions($permissions); // Admin gets all

        $interviewerRole = Role::findByName('interviewer');
        $interviewerRole->syncPermissions([
            'candidate.view',
            'interview.conduct',
        ]);

        $hrRole = Role::findByName('hr');
        $hrRole->syncPermissions([
            'candidate.view', 'candidate.create', 'candidate.update-status',
            'interview.view-all',
            'employee.view',
            'report.view',
        ]);

        $this->command->info('✔ Roles & permissions seeded: ' . implode(', ', $roles));
    }
}