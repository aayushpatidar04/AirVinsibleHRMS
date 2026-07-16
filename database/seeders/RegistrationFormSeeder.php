<?php

namespace Database\Seeders;

use App\Models\FormField;
use App\Models\QrCode;
use App\Models\RegistrationForm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegistrationFormSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@hrms.com')->first();

        /* ── Global registration form (not branch-specific) ── */
        $form = RegistrationForm::firstOrCreate(
            ['name' => 'Standard Walk-In Registration', 'branch_id' => null],
            [
                'description' => 'Standard candidate registration form for all walk-in interviews.',
                'version'     => 1,
                'is_active'   => true,
                'created_by'  => $admin->id,
            ]
        );

        if ($form->fields()->count() === 0) {
            $fields = [
                ['field_name' => 'first_name',       'field_label' => 'First Name',              'field_type' => 'text',     'is_mandatory' => true,  'order' => 1,  'field_placeholder' => 'Enter your first name'],
                ['field_name' => 'last_name',         'field_label' => 'Last Name',               'field_type' => 'text',     'is_mandatory' => true,  'order' => 2,  'field_placeholder' => 'Enter your last name'],
                ['field_name' => 'email',             'field_label' => 'Email Address',           'field_type' => 'email',    'is_mandatory' => true,  'order' => 3,  'field_placeholder' => 'you@email.com'],
                ['field_name' => 'phone',             'field_label' => 'Mobile Number',           'field_type' => 'phone',    'is_mandatory' => true,  'order' => 4,  'field_placeholder' => '10-digit mobile number'],
                ['field_name' => 'position_applied',  'field_label' => 'Position Applied For',    'field_type' => 'text',     'is_mandatory' => true,  'order' => 5,  'field_placeholder' => 'e.g. Sales Executive, Team Lead'],
                [
                    'field_name'        => 'profile_category',
                    'field_label'       => 'Profile Category',
                    'field_type'        => 'dropdown',
                    'is_mandatory'      => true,
                    'order'             => 6,
                    'field_placeholder' => 'Select profile category',
                    'options'           => [
                        'advisory_executive' => 'Advisor / Executive',
                        'leadership'         => 'TL / QA / AM / OM',
                        'other'              => 'Other',
                    ],
                ],
                ['field_name' => 'current_company',   'field_label' => 'Current / Last Company',  'field_type' => 'text',     'is_mandatory' => false, 'order' => 7,  'field_placeholder' => 'Company name (optional)'],
                ['field_name' => 'experience_years',  'field_label' => 'Total Experience (years)', 'field_type' => 'number',   'is_mandatory' => false, 'order' => 8,  'field_placeholder' => 'e.g. 2'],
                ['field_name' => 'current_ctc',       'field_label' => 'Current CTC (₹ LPA)',      'field_type' => 'number',   'is_mandatory' => false, 'order' => 9,  'field_placeholder' => 'e.g. 3.5'],
                ['field_name' => 'resume',             'field_label' => 'Upload Resume / CV',       'field_type' => 'file',     'is_mandatory' => false, 'order' => 10, 'field_placeholder' => 'PDF, DOC or DOCX (max 5 MB)'],
            ];

            foreach ($fields as $f) {
                FormField::create(array_merge($f, ['form_id' => $form->id]));
            }
        }

        /* ── Create a QR code for each branch ── */
        $branches = \App\Models\Branch::all();
        foreach ($branches as $branch) {
            QrCode::firstOrCreate(
                ['label' => "Walk-In QR — {$branch->name}"],
                [
                    'uuid'        => Str::uuid()->toString(),
                    'branch_id'   => $branch->id,
                    'form_id'     => $form->id,
                    'expiry_date' => now()->addYear(),
                    'is_active'   => true,
                    'created_by'  => $admin->id,
                ]
            );
        }

        $this->command->info('✔ Registration form seeded with ' . count($branches) . ' QR codes (one per branch).');
    }
}