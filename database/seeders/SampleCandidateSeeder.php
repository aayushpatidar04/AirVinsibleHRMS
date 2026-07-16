<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\CandidateFormSubmission;
use App\Models\InterviewRound;
use App\Models\RegistrationForm;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleCandidateSeeder extends Seeder
{
    public function run(): void
    {
        $mumbai = Branch::where('code', 'MUM01')->first();
        $delhi = Branch::where('code', 'DEL01')->first();
        $jaipur = Branch::where('code', 'JAI01')->first();

        $round1 = InterviewRound::where('slug', 'first-round')->first();
        $round2 = InterviewRound::where('slug', 'ops-round')->first();
        $round3 = InterviewRound::where('slug', 'hr-round')->first();

        $form = RegistrationForm::first();

        // Interviewers by branch
        $mumInt1 = User::where('email', 'rahul@hrms.com')->first();
        $mumInt2 = User::where('email', 'priya@hrms.com')->first();
        $jaiInt = User::where('email', 'vikram@hrms.com')->first();
        $delInt = User::where('email', 'amit@hrms.com')->first();

        $candidates = [
            // ── New candidate waiting for first round (Mumbai)
            [
                'first_name' => 'Ravi',
                'last_name' => 'Kumar',
                'email' => 'ravi.kumar@test.com',
                'phone' => '9200000001',
                'position' => 'Sales Executive',
                'profile' => 'advisory_executive',
                'branch' => $mumbai,
                'current_status' => 'in_progress',
                'final_status' => 'pending',
                'round' => $round1,
                'interviewer' => $mumInt1,
                'progress_status' => 'pending',
            ],
            // ── In progress first round (Mumbai)
            [
                'first_name' => 'Pooja',
                'last_name' => 'Shah',
                'email' => 'pooja.shah@test.com',
                'phone' => '9200000002',
                'position' => 'Senior Advisor',
                'profile' => 'advisory_executive',
                'branch' => $mumbai,
                'current_status' => 'in_progress',
                'final_status' => 'pending',
                'round' => $round1,
                'interviewer' => $mumInt2,
                'progress_status' => 'in_progress',
            ],
            // ── Completed first round, now in OPS round (Mumbai)
            [
                'first_name' => 'Arjun',
                'last_name' => 'Nair',
                'email' => 'arjun.nair@test.com',
                'phone' => '9200000003',
                'position' => 'Team Lead',
                'profile' => 'leadership',
                'branch' => $mumbai,
                'current_status' => 'in_progress',
                'final_status' => 'pending',
                'round' => $round2,
                'interviewer' => $mumInt1,
                'progress_status' => 'pending',
                'prev_rounds' => [
                    [
                        'round' => $round1,
                        'interviewer' => $mumInt2,
                        'status' => 'completed',
                        'feedback' => 'Good communication. Confident. Recommended for next round.',
                        'rating' => 4.0,
                    ],
                ],
            ],
            // ── Leadership profile in OPS round — salary discussion here (Delhi)
            [
                'first_name' => 'Meena',
                'last_name' => 'Das',
                'email' => 'meena.das@test.com',
                'phone' => '9200000004',
                'position' => 'QA Lead',
                'profile' => 'leadership',
                'branch' => $delhi,
                'current_status' => 'in_progress',
                'final_status' => 'pending',
                'round' => $round2,
                'interviewer' => $delInt,
                'progress_status' => 'in_progress',
                'prev_rounds' => [
                    [
                        'round' => $round1,
                        'interviewer' => $delInt,
                        'status' => 'completed',
                        'feedback' => 'Strong QA background. Good analytical skills.',
                        'rating' => 4.5,
                    ],
                ],
            ],
            // ── Advisory profile in HR Round — salary offer mandatory (Jaipur)
            [
                'first_name' => 'Rohit',
                'last_name' => 'Bose',
                'email' => 'rohit.bose@test.com',
                'phone' => '9200000005',
                'position' => 'Executive',
                'profile' => 'advisory_executive',
                'branch' => $jaipur,
                'current_status' => 'in_progress',
                'final_status' => 'pending',
                'round' => $round3,
                'interviewer' => $jaiInt,
                'progress_status' => 'pending',
                'prev_rounds' => [
                    [
                        'round' => $round1,
                        'interviewer' => $jaiInt,
                        'status' => 'completed',
                        'feedback' => 'Excellent first impression. Very articulate.',
                        'rating' => 4.0,
                    ],
                    [
                        'round' => $round2,
                        'interviewer' => $jaiInt,
                        'status' => 'completed',
                        'feedback' => 'Solid domain knowledge. Handles pressure well.',
                        'rating' => 4.5,
                    ],
                ],
            ],
            // ── All rounds cleared, selected (Mumbai)
            [
                'first_name' => 'Kavya',
                'last_name' => 'Iyer',
                'email' => 'kavya.iyer@test.com',
                'phone' => '9200000006',
                'position' => 'Sales Advisor',
                'profile' => 'advisory_executive',
                'branch' => $mumbai,
                'current_status' => 'all_rounds_cleared',
                'final_status' => 'selected',
                'round' => null,
                'interviewer' => null,
                'progress_status' => null,
                'prev_rounds' => [
                    ['round' => $round1, 'interviewer' => $mumInt1, 'status' => 'completed', 'feedback' => 'Very confident. Good communication.', 'rating' => 4.5],
                    ['round' => $round2, 'interviewer' => $mumInt2, 'status' => 'completed', 'feedback' => 'Excellent OPS knowledge.', 'rating' => 4.0],
                    [
                        'round' => $round3,
                        'interviewer' => $mumInt1,
                        'status' => 'completed',
                        'feedback' => 'Salary negotiated. Ready to join.',
                        'rating' => 4.5,
                        'salary' => 420000,
                        'designation' => 'Senior Sales Advisor'
                    ],
                ],
                'final_salary' => 420000,
                'final_designation' => 'Senior Sales Advisor',
            ],
            // ── Rejected at first round (Delhi)
            [
                'first_name' => 'Suresh',
                'last_name' => 'Pillai',
                'email' => 'suresh.pillai@test.com',
                'phone' => '9200000007',
                'position' => 'Customer Executive',
                'profile' => 'other',
                'branch' => $delhi,
                'current_status' => 'rejected',
                'final_status' => 'not_selected',
                'round' => null,
                'interviewer' => null,
                'progress_status' => null,
                'prev_rounds' => [
                    [
                        'round' => $round1,
                        'interviewer' => $delInt,
                        'status' => 'rejected',
                        'feedback' => 'Communication skills below required level.',
                        'rating' => 1.5,
                        'rejection_reason' => 'Did not meet minimum communication requirements for this role.',
                    ],
                ],
            ],
        ];

        foreach ($candidates as $cd) {
            $candidate = Candidate::firstOrCreate(
                ['email' => $cd['email']],
                [
                    'first_name' => $cd['first_name'],
                    'last_name' => $cd['last_name'],
                    'phone' => $cd['phone'],
                    'position_applied' => $cd['position'],
                    'profile_category' => $cd['profile'],
                    'branch_id' => $cd['branch']?->id,
                    'current_round_id' => $cd['round']?->id,
                    'current_interviewer_id' => $cd['interviewer']?->id,
                    'current_status' => $cd['current_status'],
                    'final_status' => $cd['final_status'],
                    'final_salary_offered' => $cd['final_salary'] ?? null,
                    'final_designation' => $cd['final_designation'] ?? null,
                ]
            );

            // Create form submission
            if ($form && $cd['branch']) {
                CandidateFormSubmission::firstOrCreate(
                    ['candidate_id' => $candidate->id, 'form_id' => $form->id],
                    [
                        'qr_code_id' => null,
                        'submission_data' => [
                            'first_name' => $cd['first_name'],
                            'last_name' => $cd['last_name'],
                            'email' => $cd['email'],
                            'phone' => $cd['phone'],
                            'position_applied' => $cd['position'],
                            'profile_category' => $cd['profile'],
                        ],
                        'submitted_at' => now()->subDays(rand(1, 10)),
                    ]
                );
            }

            // Create previous round progress
            foreach ($cd['prev_rounds'] ?? [] as $pr) {
                CandidateRoundProgress::firstOrCreate(
                    ['candidate_id' => $candidate->id, 'round_id' => $pr['round']->id],
                    [
                        'interviewer_id' => $pr['interviewer']->id,
                        'status' => $pr['status'],
                        'start_date' => now()->subDays(rand(2, 8)),
                        'end_date' => now()->subDays(rand(1, 4)),
                        'duration_minutes' => rand(20, 60),
                        'overall_feedback' => $pr['feedback'],
                        'overall_rating' => $pr['rating'],
                        'rejection_reason' => $pr['rejection_reason'] ?? null,
                        'salary_offer_amount' => $pr['salary'] ?? null,
                        'offered_designation' => $pr['designation'] ?? null,
                        'salary_offer_status' => isset($pr['salary']) ? 'accepted' : null,
                    ]
                );
            }

            // Create current round progress
            if ($cd['round'] && $cd['interviewer'] && $cd['progress_status']) {
                CandidateRoundProgress::firstOrCreate(
                    ['candidate_id' => $candidate->id, 'round_id' => $cd['round']->id],
                    [
                        'interviewer_id' => $cd['interviewer']->id,
                        'status' => $cd['progress_status'],
                        'start_date' => $cd['progress_status'] === 'in_progress' ? now()->subHours(1) : null,
                    ]
                );
            }
        }

        $this->command->info('✔ ' . count($candidates) . ' sample candidates seeded with round progress.');
    }
}