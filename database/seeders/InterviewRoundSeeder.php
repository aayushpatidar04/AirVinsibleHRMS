<?php

namespace Database\Seeders;

use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InterviewRoundSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@hrms.com')->first();

        $rounds = [
            /* ──── Round 1: First Round ──── */
            [
                'name'            => 'First Round',
                'slug'            => 'first-round',
                'sequence_number' => 1,
                'description'     => 'Initial screening — assess basic eligibility, communication and overall fitment.',
                'is_mandatory'    => true,
                'is_hr_round'     => false,
                'is_ops_round'    => false,
                'questions' => [
                    ['text' => 'Tell me about yourself.',                              'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'Why are you interested in this role?',                 'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'What is your current CTC (₹ LPA)?',                   'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'What is your expected CTC (₹ LPA)?',                  'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'What is your notice period (days)?',                   'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'Do you have prior BPO/sales/customer service experience?','type' => 'yes_no',   'mandatory' => true],
                    ['text' => 'Rate your communication skills (1 = poor, 5 = excellent).','type' => 'rating_scale','mandatory' => false],
                    ['text' => 'Are you comfortable with rotational/night shifts?',    'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'How did you come to know about this opening?',         'type' => 'short_answer', 'mandatory' => false],
                ],
            ],

            /* ──── Round 2: OPS Round ──── */
            [
                'name'            => 'OPS Round',
                'slug'            => 'ops-round',
                'sequence_number' => 2,
                'description'     => 'Operations round — evaluates domain knowledge, work readiness and attitude. Salary discussion for TL / QA / AM / OM profiles happens during this round.',
                'is_mandatory'    => true,
                'is_hr_round'     => false,
                'is_ops_round'    => true,
                'questions' => [
                    ['text' => 'Describe your last job role and key responsibilities.',       'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'How do you handle irate or difficult customers?',             'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'Have you managed or mentored a team before?',                 'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'If yes, how large was the team and for how long?',            'type' => 'short_answer', 'mandatory' => false],
                    ['text' => 'What is your strongest professional skill?',                  'type' => 'long_answer',  'mandatory' => true],
                    ['text' => 'Rate your problem-solving ability (1 = poor, 5 = excellent).','type' => 'rating_scale', 'mandatory' => false],
                    ['text' => 'What is your expected date of joining if selected?',          'type' => 'short_answer', 'mandatory' => true],
                    ['text' => 'Are you currently interviewing at other companies?',          'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Any relocation constraints?',                                 'type' => 'short_answer', 'mandatory' => false],
                ],
            ],

            /* ──── Round 3: HR Round ──── */
            [
                'name'            => 'HR Round',
                'slug'            => 'hr-round',
                'sequence_number' => 3,
                'description'     => 'Final HR round. SALARY OFFER IS MANDATORY for Advisor/Executive profiles. For TL/QA/AM/OM salary is discussed post OPS round — optional here.',
                'is_mandatory'    => true,
                'is_hr_round'     => true,
                'is_ops_round'    => false,
                'questions' => [
                    ['text' => 'Are you comfortable with the company policies and code of conduct?','type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Are you comfortable with the offered work location / mode?',        'type' => 'yes_no',       'mandatory' => true],
                    ['text' => 'Do you have any questions about the role or company?',              'type' => 'long_answer',  'mandatory' => false],
                    ['text' => 'Can you provide 2 professional references?',                        'type' => 'short_answer', 'mandatory' => false],
                    ['text' => 'Do you have any pending offers or counter-offers from current employer?','type' => 'yes_no',  'mandatory' => true],
                    ['text' => 'Overall candidate impression (1 = poor, 5 = excellent).',           'type' => 'rating_scale', 'mandatory' => true],
                    ['text' => 'Any additional notes / remarks about this candidate?',              'type' => 'long_answer',  'mandatory' => false],
                ],
            ],
        ];

        foreach ($rounds as $rd) {
            $round = InterviewRound::firstOrCreate(
                ['slug' => $rd['slug']],
                [
                    'name'            => $rd['name'],
                    'slug'            => $rd['slug'],
                    'sequence_number' => $rd['sequence_number'],
                    'description'     => $rd['description'],
                    'is_mandatory'    => $rd['is_mandatory'],
                    'is_hr_round'     => $rd['is_hr_round'],
                    'is_ops_round'    => $rd['is_ops_round'],
                    'is_active'       => true,
                    'created_by'      => $admin?->id,
                ]
            );

            foreach ($rd['questions'] as $idx => $q) {
                Question::firstOrCreate(
                    ['round_id' => $round->id, 'question_text' => $q['text']],
                    [
                        'question_type' => $q['type'],
                        'is_mandatory'  => $q['mandatory'],
                        'is_custom'     => false,
                        'order'         => $idx + 1,
                        'created_by'    => $admin?->id,
                    ]
                );
            }
        }

        $this->command->info('✔ ' . count($rounds) . ' interview rounds seeded with questions.');
    }
}