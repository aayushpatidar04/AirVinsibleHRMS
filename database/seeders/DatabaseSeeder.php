<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\FormField;
use App\Models\InterviewRound;
use App\Models\QrCode;
use App\Models\RegistrationForm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $this->createRoles();

        // 2. Branches
        $branches = $this->createBranches();

        // 3. Admin user
        $admin = $this->createAdmin();

        // 4. Interviewers
        $interviewers = $this->createInterviewers($branches);

        // 5. Interview Rounds
        $rounds = $this->createInterviewRounds($admin);

        // 6. Registration Form
        $form = $this->createRegistrationForm($branches[0], $admin);

        // 7. QR Code
        $this->createQrCode($branches[0], $form, $admin);

        // 8. Sample candidates
        $this->createSampleCandidates($branches[0], $rounds, $interviewers);

        $this->command->info('✅ Database seeded successfully.');
    }

    private function createRoles(): void
    {
        foreach (['admin', 'interviewer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->command->info('Roles created.');
    }

    private function createBranches(): array
    {
        $data = [
            ['name' => 'Mumbai Head Office', 'code' => 'MUM01', 'city' => 'Mumbai',    'state' => 'Maharashtra'],
            ['name' => 'Delhi Branch',       'code' => 'DEL01', 'city' => 'New Delhi', 'state' => 'Delhi'],
            ['name' => 'Bangalore Branch',   'code' => 'BLR01', 'city' => 'Bangalore', 'state' => 'Karnataka'],
            ['name' => 'Jaipur Branch',      'code' => 'JAI01', 'city' => 'Jaipur',    'state' => 'Rajasthan'],
        ];

        $branches = [];
        foreach ($data as $i => $d) {
            $branches[] = Branch::firstOrCreate(['code' => $d['code']], array_merge($d, [
                'address'     => "123 Business Park, {$d['city']}",
                'postal_code' => '400001',
                'country'     => 'India',
                'phone'       => '0' . (9000000001 + $i),
                'email'       => strtolower($d['code']) . '@hrms.com',
                'is_active'   => true,
            ]));
        }

        $this->command->info(count($branches) . ' branches created.');
        return $branches;
    }

    private function createAdmin(): User
    {
        $admin = User::firstOrCreate(['email' => 'admin@hrms.com'], [
            'first_name'  => 'System',
            'last_name'   => 'Admin',
            'phone'       => '9000000000',
            'employee_id' => 'ADM001',
            'password'    => Hash::make('password'),
            'is_active'   => true,
        ]);

        $admin->syncRoles(['admin']);

        $this->command->info('Admin created: admin@hrms.com / password');
        return $admin;
    }

    private function createInterviewers(array $branches): array
    {
        $interviewerData = [
            ['first_name' => 'Rahul',   'last_name' => 'Sharma',  'email' => 'rahul@hrms.com',   'emp' => 'INT001', 'branch' => 0],
            ['first_name' => 'Priya',   'last_name' => 'Mehta',   'email' => 'priya@hrms.com',   'emp' => 'INT002', 'branch' => 0],
            ['first_name' => 'Amit',    'last_name' => 'Verma',   'email' => 'amit@hrms.com',    'emp' => 'INT003', 'branch' => 1],
            ['first_name' => 'Sneha',   'last_name' => 'Gupta',   'email' => 'sneha@hrms.com',   'emp' => 'INT004', 'branch' => 2],
            ['first_name' => 'Vikram',  'last_name' => 'Singh',   'email' => 'vikram@hrms.com',  'emp' => 'INT005', 'branch' => 3],
            ['first_name' => 'Anjali',  'last_name' => 'Pandey',  'email' => 'anjali@hrms.com',  'emp' => 'INT006', 'branch' => 3],
        ];

        $interviewers = [];
        foreach ($interviewerData as $i => $d) {
            $user = User::firstOrCreate(['email' => $d['email']], [
                'first_name'  => $d['first_name'],
                'last_name'   => $d['last_name'],
                'phone'       => '90000' . str_pad($i + 10, 5, '0', STR_PAD_LEFT),
                'employee_id' => $d['emp'],
                'branch_id'   => $branches[$d['branch']]->id,
                'password'    => Hash::make('password'),
                'is_active'   => true,
            ]);
            $user->syncRoles(['interviewer']);
            $interviewers[] = $user;
        }

        $this->command->info(count($interviewers) . ' interviewers created. (password: password)');
        return $interviewers;
    }

    private function createInterviewRounds(User $admin): array
    {
        $roundData = [
            [
                'name'            => 'First Round',
                'slug'            => 'first-round',
                'sequence_number' => 1,
                'description'     => 'Initial screening round to assess basic eligibility and communication.',
                'is_mandatory'    => true,
                'is_hr_round'     => false,
                'is_ops_round'    => false,
                'questions'       => [
                    ['text' => 'Tell me about yourself.',                   'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'Why are you interested in this role?',      'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'What is your expected CTC?',                'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'What is your current CTC?',                 'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'What is your notice period?',               'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'Rate your communication skills (1–5).',     'type' => 'rating_scale', 'mandatory' => false],
                    ['text' => 'Do you have prior BPO/sales experience?',   'type' => 'yes_no',       'mandatory' => true],
                ],
            ],
            [
                'name'            => 'OPS Round',
                'slug'            => 'ops-round',
                'sequence_number' => 2,
                'description'     => 'Operations round — evaluates domain knowledge and work readiness. Salary discussion for TL/QA/AM/OM profiles happens here.',
                'is_mandatory'    => true,
                'is_hr_round'     => false,
                'is_ops_round'    => true,
                'questions'       => [
                    ['text' => 'Describe your previous work experience.',       'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'How do you handle difficult customers?',        'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'What are your key strengths?',                  'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'Have you managed a team before?',               'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Rate your problem-solving ability (1–5).',      'type' => 'rating_scale', 'mandatory' => false],
                    ['text' => 'Are you comfortable with rotational shifts?',   'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'What is your expected joining date?',           'type' => 'short_answer', 'mandatory' => true],
                ],
            ],
            [
                'name'            => 'HR Round',
                'slug'            => 'hr-round',
                'sequence_number' => 3,
                'description'     => 'HR round — salary offer MANDATORY for Advisor/Executive. For TL/QA/AM/OM salary is discussed after OPS round.',
                'is_mandatory'    => true,
                'is_hr_round'     => true,
                'is_ops_round'    => false,
                'questions'       => [
                    ['text' => 'Are you comfortable with the company policies?',    'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Do you have any questions about the role?',          'type' => 'long_answer',  'mandatory' => false],
                    ['text' => 'Are you comfortable with the location/work mode?',  'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Can you provide reference contacts?',               'type' => 'short_answer', 'mandatory' => false],
                    ['text' => 'Any offer/interview in progress with other company?','type' => 'yes_no',      'mandatory' => true],
                    ['text' => 'Overall candidate impression (1–5).',               'type' => 'rating_scale', 'mandatory' => true],
                ],
            ],
        ];

        $rounds = [];
        foreach ($roundData as $rd) {
            $round = InterviewRound::firstOrCreate(['slug' => $rd['slug']], [
                'name'            => $rd['name'],
                'slug'            => $rd['slug'],
                'sequence_number' => $rd['sequence_number'],
                'description'     => $rd['description'],
                'is_mandatory'    => $rd['is_mandatory'],
                'is_hr_round'     => $rd['is_hr_round'],
                'is_ops_round'    => $rd['is_ops_round'],
                'is_active'       => true,
                'created_by'      => $admin->id,
            ]);

            foreach ($rd['questions'] as $idx => $q) {
                \App\Models\Question::firstOrCreate(
                    ['round_id' => $round->id, 'question_text' => $q['text']],
                    [
                        'question_type' => $q['type'],
                        'is_mandatory'  => $q['mandatory'],
                        'is_custom'     => false,
                        'order'         => $idx + 1,
                        'created_by'    => $admin->id,
                    ]
                );
            }

            $rounds[] = $round;
        }

        $this->command->info(count($rounds) . ' interview rounds created with questions.');
        return $rounds;
    }

    private function createRegistrationForm(Branch $branch, User $admin): RegistrationForm
    {
        $form = RegistrationForm::firstOrCreate(
            ['name' => 'Standard Registration Form', 'branch_id' => null],
            [
                'description' => 'Standard candidate registration form for walk-in interviews.',
                'version'     => 1,
                'is_active'   => true,
                'created_by'  => $admin->id,
            ]
        );

        if ($form->fields()->count() === 0) {
            $fields = [
                ['field_name' => 'first_name',      'field_label' => 'First Name',           'field_type' => 'text',     'is_mandatory' => true,  'order' => 1],
                ['field_name' => 'last_name',        'field_label' => 'Last Name',            'field_type' => 'text',     'is_mandatory' => true,  'order' => 2],
                ['field_name' => 'email',            'field_label' => 'Email Address',        'field_type' => 'email',    'is_mandatory' => true,  'order' => 3],
                ['field_name' => 'phone',            'field_label' => 'Phone Number',         'field_type' => 'phone',    'is_mandatory' => true,  'order' => 4],
                ['field_name' => 'position_applied', 'field_label' => 'Position Applied For', 'field_type' => 'text',     'is_mandatory' => true,  'order' => 5],
                ['field_name' => 'profile_category', 'field_label' => 'Profile Category',    'field_type' => 'dropdown', 'is_mandatory' => true,  'order' => 6,
                    'options' => ['advisory_executive' => 'Advisor / Executive', 'leadership' => 'TL / QA / AM / OM', 'other' => 'Other']],
                ['field_name' => 'current_company',  'field_label' => 'Current Company',      'field_type' => 'text',     'is_mandatory' => false, 'order' => 7],
                ['field_name' => 'experience_years', 'field_label' => 'Years of Experience',  'field_type' => 'number',   'is_mandatory' => false, 'order' => 8],
                ['field_name' => 'current_ctc',      'field_label' => 'Current CTC (LPA)',    'field_type' => 'number',   'is_mandatory' => false, 'order' => 9],
                ['field_name' => 'resume',            'field_label' => 'Upload Resume / CV',  'field_type' => 'file',     'is_mandatory' => false, 'order' => 10],
            ];

            foreach ($fields as $field) {
                FormField::create(array_merge($field, ['form_id' => $form->id]));
            }
        }

        $this->command->info('Registration form created.');
        return $form;
    }

    private function createQrCode(Branch $branch, RegistrationForm $form, User $admin): void
    {
        QrCode::firstOrCreate(['label' => 'Main QR – Mumbai Walk-In'], [
            'uuid'        => Str::uuid()->toString(),
            'branch_id'   => $branch->id,
            'form_id'     => $form->id,
            'expiry_date' => now()->addYear(),
            'is_active'   => true,
            'created_by'  => $admin->id,
        ]);

        $this->command->info('Sample QR code created.');
    }

    private function createSampleCandidates(Branch $branch, array $rounds, array $interviewers): void
    {
        $sampleCandidates = [
            ['first_name' => 'Ravi',    'last_name' => 'Kumar',  'email' => 'ravi@test.com',   'phone' => '9111000001', 'position' => 'Sales Executive',    'profile' => 'advisory_executive', 'status' => 'in_progress', 'round_idx' => 0],
            ['first_name' => 'Pooja',   'last_name' => 'Shah',   'email' => 'pooja@test.com',  'phone' => '9111000002', 'position' => 'Team Lead',           'profile' => 'leadership',         'status' => 'in_progress', 'round_idx' => 0],
            ['first_name' => 'Arjun',   'last_name' => 'Nair',   'email' => 'arjun@test.com',  'phone' => '9111000003', 'position' => 'Senior Advisor',      'profile' => 'advisory_executive', 'status' => 'round_completed', 'round_idx' => 1],
            ['first_name' => 'Meena',   'last_name' => 'Das',    'email' => 'meena@test.com',  'phone' => '9111000004', 'position' => 'QA Analyst',          'profile' => 'leadership',         'status' => 'in_progress', 'round_idx' => 1],
            ['first_name' => 'Rohit',   'last_name' => 'Bose',   'email' => 'rohit@test.com',  'phone' => '9111000005', 'position' => 'Customer Executive',  'profile' => 'other',              'status' => 'rejected',    'round_idx' => 0],
        ];

        foreach ($sampleCandidates as $cd) {
            $interviewer = $interviewers[array_rand($interviewers)];
            $round       = $rounds[$cd['round_idx']];

            $candidate = Candidate::firstOrCreate(['email' => $cd['email']], [
                'first_name'             => $cd['first_name'],
                'last_name'              => $cd['last_name'],
                'phone'                  => $cd['phone'],
                'position_applied'       => $cd['position'],
                'profile_category'       => $cd['profile'],
                'branch_id'              => $branch->id,
                'current_round_id'       => $round->id,
                'current_interviewer_id' => $interviewer->id,
                'current_status'         => $cd['status'],
                'final_status'           => 'pending',
            ]);

            CandidateRoundProgress::firstOrCreate(
                ['candidate_id' => $candidate->id, 'round_id' => $round->id],
                [
                    'interviewer_id' => $interviewer->id,
                    'status'         => $cd['status'] === 'rejected' ? 'rejected' : 'pending',
                ]
            );
        }

        $this->command->info(count($sampleCandidates) . ' sample candidates created.');
    }
}