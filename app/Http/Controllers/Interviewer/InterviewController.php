<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateRoundCustomQuestion;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\Response;
use App\Models\User;
use App\Notifications\RoundAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InterviewController extends Controller
{
    private function getAuthorizedProgress(
        Candidate $candidate,
        InterviewRound $round,
        string $ability
    ): CandidateRoundProgress {
        $progress = CandidateRoundProgress::query()
            ->where('candidate_id', $candidate->id)
            ->where('round_id', $round->id)
            ->firstOrFail();

        $this->authorize($ability, $progress);

        return $progress;
    }
    /**
     * Show the interview form for a candidate in a specific round.
     */
    public function show(
        Candidate $candidate,
        InterviewRound $round
    ) {
        $interviewer = Auth::user();

        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'view'
        );

        $progress->load('round');

        $candidate->load([
            'branch',
            'formSubmission.form',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Questions with existing responses
        |--------------------------------------------------------------------------
        */

        $templateQuestions = $round->questions()
            ->ordered()
            ->get()
            ->map(fn(Question $question) => [
                'key' => "template-{$question->id}",

                'id' => $question->id,
                'source' => 'template',

                'question_text' =>
                    $question->question_text,

                'question_type' =>
                    $question->question_type,

                'score_category' =>
                    $question->score_category,

                'is_mandatory' =>
                    $question->is_mandatory,

                'is_custom' => false,

                'order' =>
                    $question->order,

                'options' =>
                    $question->options,

                'response' => $this->getResponse(
                    $progress->id,
                    $question->id
                ),
            ]);

        $customQuestions = $progress->customQuestions()
            ->with(
                'interviewer:id,first_name,last_name'
            )
            ->get()
            ->map(
                fn(
                CandidateRoundCustomQuestion $question
            ) => [
                    'key' => "custom-{$question->id}",

                    'id' => $question->id,
                    'source' => 'custom',

                    'question_text' =>
                        $question->question_text,

                    'question_type' =>
                        $question->question_type,

                    'score_category' =>
                        $question->score_category,

                    'is_mandatory' =>
                        $question->is_mandatory,

                    'is_custom' => true,

                    'order' =>
                        $question->order,

                    'options' =>
                        $question->options,

                    'response' => [
                        'response_text' =>
                            $question->response_text,

                        'rating_value' =>
                            $question->rating_value,

                        'yes_no_value' =>
                            $question->yes_no_value,

                        'selected_options' =>
                            $question->selected_options ?? [],

                        'interviewer_notes' =>
                            $question->interviewer_notes,

                        'question_rating' =>
                            $question->question_rating,
                    ],
                ]
            );

        $questions = $templateQuestions
            ->concat($customQuestions)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Previous-round feedback
        |--------------------------------------------------------------------------
        */

        $previousFeedback =
            CandidateRoundProgress::query()
                ->where(
                    'candidate_id',
                    $candidate->id
                )
                ->where('id', '!=', $progress->id)
                ->where('status', 'completed')
                ->with([
                    'round',
                    'interviewer',
                ])
                ->orderBy('created_at')
                ->get()
                ->map(fn($previousProgress) => [
                    'round' =>
                        $previousProgress->round?->name,

                    'interviewer' =>
                        $previousProgress
                            ->interviewer
                                ?->full_name,

                    'rating' =>
                        $previousProgress->overall_rating,

                    'feedback' =>
                        $previousProgress->overall_feedback,

                    'date' =>
                        $previousProgress
                            ->end_date
                                ?->format('d M Y'),
                ]);

        /*
        |--------------------------------------------------------------------------
        | OPS salary recommendation for HR
        |--------------------------------------------------------------------------
        */

        $opsSalaryProgress = null;

        if ($round->is_hr_round) {
            $opsSalaryProgress =
                CandidateRoundProgress::query()
                    ->where(
                        'candidate_id',
                        $candidate->id
                    )
                    ->where('status', 'completed')
                    ->where('id', '!=', $progress->id)
                    ->whereHas(
                        'round',
                        function ($query) {
                            $query->where(
                                'is_ops_round',
                                true
                            );
                        }
                    )
                    ->with([
                        'round:id,name',
                        'interviewer:id,first_name,last_name,employee_id',
                    ])
                    ->orderByDesc('end_date')
                    ->orderByDesc('id')
                    ->first();
        }

        $opsSalaryRecommendation = null;

        if ($opsSalaryProgress) {
            $opsSalaryRecommendation = [
                'progress_id' =>
                    $opsSalaryProgress->id,

                'round_name' =>
                    $opsSalaryProgress->round?->name,

                'minimum' =>
                    $opsSalaryProgress->salary_offer_min,

                'maximum' =>
                    $opsSalaryProgress->salary_offer_max,

                'designation' =>
                    $opsSalaryProgress
                        ->offered_designation,

                'interviewer' =>
                    $opsSalaryProgress
                        ->interviewer
                            ?->full_name,

                'interviewer_employee_id' =>
                    $opsSalaryProgress
                        ->interviewer
                            ?->employee_id,

                'feedback' =>
                    $opsSalaryProgress
                        ->overall_feedback,

                'completed_at' =>
                    $opsSalaryProgress
                        ->end_date
                            ?->format('d M Y, h:i A'),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Available next rounds
        |--------------------------------------------------------------------------
        */

        $nextRounds = InterviewRound::active()
            ->where(
                'sequence_number',
                '>',
                $round->sequence_number
            )
            ->ordered()
            ->get([
                'id',
                'name',
                'is_hr_round',
                'is_ops_round',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Available interviewers
        |--------------------------------------------------------------------------
        */

        $interviewers = User::query()
            ->active()
            ->canInterview()
            ->get([
                'id',
                'first_name',
                'last_name',
                'employee_id',
            ]);

        $availableInterviewers =
            $interviewers->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->full_name,
                'emp' => $user->employee_id,

                'can_take_round' =>
                    $user->canInterviewRound($round),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Registration form
        |--------------------------------------------------------------------------
        */

        $form =
            $candidate->latestSubmission?->form;

        $submissionData =
            $candidate
                ->latestSubmission
                    ?->submission_data ?? [];

        $registrationForm = null;

        if ($form) {
            $registrationForm = [
                'form_name' =>
                    $form->name,

                'submitted_at' =>
                    $candidate
                        ->latestSubmission
                        ?->submitted_at
                            ?->format('d M Y, h:i A'),

                'fields' =>
                    $form->fields->map(
                        fn($field) => [
                            'field_label' =>
                                $field->field_label,

                            'field_type' =>
                                $field->field_type,

                            'is_mandatory' =>
                                $field->is_mandatory,

                            'options' =>
                                $field->options,

                            'value' =>
                                $submissionData[
                                    $field->field_name
                                ] ?? null,
                        ]
                    ),
            ];
        }

        return Inertia::render(
            'Interviewer/Interviews/Show',
            [
                'candidate' => [
                    'id' =>
                        $candidate->id,

                    'name' =>
                        $candidate->full_name,

                    'email' =>
                        $candidate->email,

                    'phone' =>
                        $candidate->phone,

                    'position' =>
                        $candidate->position_applied,

                    'profile_label' =>
                        $candidate
                            ->getProfileCategoryLabel(),

                    'profile_category' =>
                        $candidate->profile_category,

                    'branch' =>
                        $candidate->branch?->name,

                    'resume_path' =>
                        $candidate
                            ->latestSubmission
                                ?->resume_path,

                    'resume_name' =>
                        $candidate
                            ->latestSubmission
                                ?->resume_original_name,

                    'average_rating' =>
                        $candidate->getAverageRating(),

                    'completed_rounds' =>
                        $candidate
                            ->completedRoundsCount(),
                ],

                'interview' => [
                    'id' =>
                        $progress->id,

                    'round_id' =>
                        $round->id,

                    'round_name' =>
                        $round->name,

                    'is_hr_round' =>
                        (bool) $round->is_hr_round,

                    'is_ops_round' =>
                        (bool) $round->is_ops_round,

                    'status' =>
                        $progress->status,

                    'started_at' =>
                        $progress
                            ->start_date
                                ?->format('d M Y H:i'),

                    /*
                     * For OPS:
                     * These are recommendation values.
                     *
                     * For HR:
                     * These are discussed values.
                     */
                    'salary_offer_min' =>
                        $progress->salary_offer_min,

                    'salary_offer_max' =>
                        $progress->salary_offer_max,

                    'offered_designation' =>
                        $progress->offered_designation,

                    /*
                     * Used by HR as candidate response.
                     */
                    'salary_offer_status' =>
                        $progress->salary_offer_status,

                    'overall_feedback' =>
                        $progress->overall_feedback,

                    'overall_rating' =>
                        $progress->overall_rating,
                ],

                'ops_salary_recommendation' =>
                    $opsSalaryRecommendation,

                'questions' =>
                    $questions,

                'previous_feedback' =>
                    $previousFeedback,

                'next_rounds' =>
                    $nextRounds,

                'available_interviewers' =>
                    $availableInterviewers,

                'registration_form' =>
                    $registrationForm,

                'scoreCategories' =>
                    Question::SCORE_CATEGORIES,
            ]
        );
    }

    /**
     * Start an interview.
     */
    public function start(Candidate $candidate, InterviewRound $round)
    {
        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'start'
        );

        $progress->startInterview();


        return back()->with('success', 'Interview started.');
    }

    /**
     * Reassign the current round to another interviewer.
     */
    public function reassign(Request $request, Candidate $candidate, InterviewRound $round)
    {

        $data = $request->validate([
            'new_interviewer_id' => 'required|exists:users,id',
        ]);

        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'reassign'
        );

        $newInterviewer = User::query()
            ->active()
            ->canInterview()
            ->findOrFail($data['new_interviewer_id']);

        if (!$newInterviewer->canInterviewRound($round)) {
            return back()->withErrors([
                'new_interviewer_id' => 'Selected interviewer is not configured for this round.',
            ]);
        }

        $progress->update(['interviewer_id' => $newInterviewer->id]);
        $candidate->update(['current_interviewer_id' => $newInterviewer->id]);

        $newInterviewer->notify(new RoundAssigned($progress));

        return back()->with('success', 'Interview reassigned to the selected interviewer.');
    }

    /**
     * Save or update a single response.
     */
    public function saveResponse(
        Request $request,
        Candidate $candidate,
        InterviewRound $round
    ) {

        $data = $request->validate([
            'question_id' => [
                'required',
                'integer',
            ],

            'question_source' => [
                'required',
                'in:template,custom',
            ],

            'response_text' => [
                'nullable',
                'string',
            ],

            'rating_value' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],

            'yes_no_value' => [
                'nullable',
                'boolean',
            ],

            'selected_options' => [
                'nullable',
                'array',
            ],

            'selected_options.*' => [
                'nullable',
                'string',
                'max:500',
            ],

            'interviewer_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'question_rating' => [
                'nullable',
                'numeric',
                'min:0',
                'max:5',
            ],
        ]);

        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'saveResponse'
        );

        $responseValues = [
            'response_text' =>
                $data['response_text'] ?? null,

            'rating_value' =>
                $data['rating_value'] ?? null,

            'yes_no_value' =>
                $data['yes_no_value'] ?? null,

            'selected_options' =>
                $data['selected_options'] ?? [],

            'interviewer_notes' =>
                $data['interviewer_notes'] ?? null,

            'question_rating' =>
                $data['question_rating'] ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Template-question response
        |--------------------------------------------------------------------------
        */

        if ($data['question_source'] === 'template') {
            /*
             * Security check: ensure the question actually belongs to this round.
             */
            $question = $round->questions()
                ->whereKey($data['question_id'])
                ->firstOrFail();

            Response::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'question_id' => $question->id,
                    'progress_id' => $progress->id,
                ],
                $responseValues
            );

            return back()->with(
                'success',
                'Response saved.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Interview-specific custom-question response
        |--------------------------------------------------------------------------
        */

        $customQuestion = $progress
            ->customQuestions()
            ->whereKey($data['question_id'])
            ->firstOrFail();

        $customQuestion->update([
            ...$responseValues,
            'answered_at' => now(),
        ]);

        return back()->with(
            'success',
            'Custom-question response saved.'
        );
    }

    /**
     * Complete the current round and optionally move to next round.
     */
    public function complete(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'complete'
        );

        $isOpsRound = (bool) $round->is_ops_round;
        $isHrRound = (bool) $round->is_hr_round;

        $data = $request->validate([
            'overall_feedback' => [
                'required',
                'string',
                'max:3000',
            ],

            'overall_rating' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
            ],

            'completion_action' => [
                'required',
                Rule::in([
                    'continue_to_next_round',
                    'mark_all_rounds_cleared',
                ]),
            ],

            'next_round_id' => [
                'nullable',
                'exists:interview_rounds,id',
            ],

            'next_interviewer_id' => [
                'nullable',
                'exists:users,id',
            ],

            'salary_offer_min' => [
                ($isOpsRound || $isHrRound)
                ? 'required'
                : 'nullable',
                'numeric',
                'min:0',
            ],

            'salary_offer_max' => [
                ($isOpsRound || $isHrRound)
                ? 'required'
                : 'nullable',
                'numeric',
                'min:0',
                'gte:salary_offer_min',
            ],

            'offered_designation' => [
                $round->is_ops_round ? 'nullable' : 'nullable',
                'string',
                'max:255',
            ],

            'salary_offer_status' => [
                $isHrRound
                ? 'required'
                : 'nullable',
                Rule::in([
                    'accepted',
                    'negotiating',
                    'pending',
                    'declined',
                ]),
            ],

            'salary_discussion_notes' => [
                'nullable',
                'string',
            ],
        ]);

        $isHrRound = (bool) $round->is_hr_round;
        if (
            !$isHrRound &&
            $data['completion_action'] !== 'continue_to_next_round'
        ) {
            return back()->withErrors([
                'completion_action' =>
                    'Only the HR round can mark all rounds as cleared.',
            ]);
        }

        if (
            $data['completion_action'] === 'continue_to_next_round'
            && (
                empty($data['next_round_id'])
                || empty($data['next_interviewer_id'])
            )
        ) {
            return back()->withErrors([
                'next_round_id' =>
                    'Please select the next round and interviewer.',
            ]);
        }

        if (
            $data['completion_action'] === 'mark_all_rounds_cleared'
            && !$isHrRound
        ) {
            return back()->withErrors([
                'completion_action' =>
                    'Only HR can mark all interview rounds as cleared.',
            ]);
        }

        if (
            !empty($data['next_round_id'])
            && (int) $data['next_round_id'] === (int) $round->id
        ) {
            return back()->withErrors([
                'next_round_id' =>
                    'The current round cannot be selected as the next round.',
            ]);
        }

        if (!is_null($data['salary_offer_min']) && !is_null($data['salary_offer_max']) && $data['salary_offer_max'] < $data['salary_offer_min']) {
            return back()->withErrors([
                'salary_offer_max' => 'Maximum salary must be greater than or equal to minimum salary.',
            ]);
        }

        // ── All mandatory questions must be answered ──
        if (!$progress->allMandatoryQuestionsAnswered()) {
            $unanswered = $progress->unansweredMandatoryQuestions()
                ->pluck('question_text')
                ->implode(', ');

            return back()->withErrors([
                'questions' => "Please answer all mandatory questions: {$unanswered}",
            ]);
        }

        $nextRound = null;
        $nextInterviewer = null;

        if (
            $data['completion_action'] ===
            'continue_to_next_round'
        ) {
            $nextRound = InterviewRound::query()
                ->findOrFail($data['next_round_id']);

            $nextInterviewer = User::query()
                ->active()
                ->canInterview()
                ->findOrFail($data['next_interviewer_id']);

            if (
                !$nextInterviewer->canInterviewRound(
                    $nextRound
                )
            ) {
                return back()->withErrors([
                    'next_interviewer_id' =>
                        'Selected interviewer cannot take the chosen next round.',
                ]);
            }

            $alreadyAssigned =
                CandidateRoundProgress::query()
                    ->where(
                        'candidate_id',
                        $candidate->id
                    )
                    ->where(
                        'round_id',
                        $nextRound->id
                    )
                    ->whereIn('status', [
                        'pending',
                        'in_progress',
                    ])
                    ->exists();

            if ($alreadyAssigned) {
                return back()->withErrors([
                    'next_round_id' =>
                        'This candidate already has an active assignment for the selected round.',
                ]);
            }
        }

        $nextProgress = null;

        // ── Complete this round ──
        DB::transaction(function () use ($progress, $candidate, $data, $isOpsRound, $isHrRound, $nextRound, $nextInterviewer, &$nextProgress) {
            /*
            |--------------------------------------------------------------------------
            | Complete current round
            |--------------------------------------------------------------------------
            */

            $progress->completeRound([
                'overall_feedback' =>
                    $data['overall_feedback'],

                'overall_rating' =>
                    $data['overall_rating'],

                'next_round_id' =>
                    $data['completion_action'] ===
                    'continue_to_next_round'
                    ? $nextRound->id
                    : null,

                'next_interviewer_id' =>
                    $data['completion_action'] ===
                    'continue_to_next_round'
                    ? $nextInterviewer->id
                    : null,

                // Salary is saved only as OPS recommendation
                'salary_offer_min' =>
                    $isOpsRound || $isHrRound
                    ? $data['salary_offer_min']
                    : null,

                'salary_offer_max' =>
                    $isOpsRound || $isHrRound
                    ? $data['salary_offer_max']
                    : null,

                'offered_designation' =>
                    $isOpsRound || $isHrRound
                    ? ($data['offered_designation'] ?? null)
                    : null,

                // No real offer status during interviews
                'salary_offer_status' => $data['salary_offer_status'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Continue to next round
            |--------------------------------------------------------------------------
            */

            if (
                $data['completion_action'] ===
                'continue_to_next_round'
            ) {
                $nextProgress =
                    CandidateRoundProgress::create([
                        'candidate_id' =>
                            $candidate->id,

                        'round_id' =>
                            $nextRound->id,

                        'interviewer_id' =>
                            $nextInterviewer->id,

                        'status' => 'pending',
                    ]);

                $candidate->update([
                    'current_status' => 'in_progress',
                    'current_round_id' =>
                        $nextRound->id,
                    'current_interviewer_id' =>
                        $nextInterviewer->id,
                ]);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | HR explicitly clears all rounds
            |--------------------------------------------------------------------------
            */

            $candidate->update([
                'current_status' =>
                    'all_rounds_cleared',

                'current_round_id' => null,

                'current_interviewer_id' => null,
            ]);
        });

        if ($nextProgress && $nextInterviewer) {
            $nextInterviewer->notify(
                new RoundAssigned($nextProgress)
            );
        }

        $message =
            $data['completion_action'] ===
            'mark_all_rounds_cleared'
            ? 'HR round completed and all interview rounds have been cleared.'
            : 'Interview completed and the next round has been assigned.';

        return redirect()
            ->route('interviewer.dashboard')
            ->with('success', 'Interview completed successfully.');
    }

    /**
     * Reject a candidate at the current round.
     */
    public function reject(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'overall_feedback' => 'nullable|string|max:2000',
        ]);

        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'reject'
        );
        $progress->rejectCandidate($data['rejection_reason']);

        if (!empty($data['overall_feedback'])) {
            $progress->update(['overall_feedback' => $data['overall_feedback']]);
        }

        $candidate->update([
            'current_status' => 'rejected',
            'current_round_id' => null,
            'current_interviewer_id' => null,
        ]);

        return redirect()
            ->route('interviewer.dashboard')
            ->with('success', 'Candidate rejected.');
    }

    /**
     * Add a custom question during an interview.
     */
    public function addCustomQuestion(
        Request $request,
        Candidate $candidate,
        InterviewRound $round
    ) {
        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'addCustomQuestion'
        );

        $data = $request->validate([
            'question_text' => [
                'required',
                'string',
                'max:1000',
            ],

            'question_type' => [
                'required',
                'in:short_answer,long_answer,rating_scale,yes_no,multiple_choice',
            ],

            'options' => [
                'nullable',
                'array',
            ],

            'options.*' => [
                'nullable',
                'string',
                'max:500',
            ],
            'score_category' => ['nullable', Rule::in(array_keys(Question::SCORE_CATEGORIES))],

        ]);

        $maxOrder = $progress
            ->customQuestions()
            ->max('order') ?? 0;

        $progress->customQuestions()->create([
            'candidate_id' => $candidate->id,
            'interviewer_id' => $request->user()->id,

            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'score_category' => $data['score_category'],

            'is_mandatory' => false,
            'options' => $data['options'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return back()->with(
            'success',
            'Custom question added to this interview only.'
        );
    }

    /**
     * Fetch interviewers for a specific round.
     */
    public function getInterviewers(
        Candidate $candidate,
        InterviewRound $round
    ) {
        $this->authorize('view', $candidate);

        $interviewers = User::query()
            ->active()
            ->canInterview()
            ->forRound($round)
            ->with('branch:id,name')
            ->withCount([
                'candidateRoundProgress as pending_interviews_count' =>
                    fn($query) => $query->whereIn(
                        'status',
                        ['pending', 'in_progress']
                    ),
            ])
            ->orderBy('pending_interviews_count')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->full_name,
                'employee_id' => $user->employee_id,
                'designation' => $user->designation,
                'branch_id' => $user->branch_id,
                'branch_name' => $user->branch?->name ?? 'No Branch',
                'pending_interviews_count' =>
                    $user->pending_interviews_count,
            ]);

        return response()->json([
            'interviewers' => $interviewers,
        ]);
    }

    /* ── Private Helpers ── */

    private function getResponse(int $progressId, int $questionId): ?array
    {
        $r = Response::where('progress_id', $progressId)
            ->where('question_id', $questionId)
            ->first();

        if (!$r)
            return null;

        return [
            'id' => $r->id,
            'response_text' => $r->response_text,
            'rating_value' => $r->rating_value,
            'yes_no_value' => $r->yes_no_value,
            'selected_options' => $r->selected_options,
            'interviewer_notes' => $r->interviewer_notes,
            'question_rating' => $r->question_rating,
        ];
    }

    private function getSalaryHint(InterviewRound $round, Candidate $candidate): string
    {
        if ($round->is_ops_round && $candidate->requiresSalaryInOpsRound()) {
            return 'Record a salary range (minimum and maximum) for Advisor/Executive profiles in this OPS round when available.';
        }

        if ($round->is_hr_round && $candidate->requiresSalaryInHrRound()) {
            return 'Final HR salary range is required for this profile in the HR round.';
        }

        if ($round->is_hr_round) {
            return 'You may optionally record a salary range for this profile in the HR round.';
        }

        if ($round->is_ops_round) {
            return 'This OPS round does not require salary discussion for this profile.';
        }

        return '';
    }

    public function deleteCustomQuestion(
        Candidate $candidate,
        InterviewRound $round,
        CandidateRoundCustomQuestion $customQuestion
    ) {
        $progress = $this->getAuthorizedProgress(
            $candidate,
            $round,
            'addCustomQuestion'
        );

        abort_if(
            $progress->status === 'completed',
            422,
            'Questions cannot be deleted after the interview is completed.'
        );

        $question = $progress
            ->customQuestions()
            ->whereKey($customQuestion->id)
            ->firstOrFail();

        $question->delete();

        return back()->with(
            'success',
            'Custom question removed.'
        );
    }
}