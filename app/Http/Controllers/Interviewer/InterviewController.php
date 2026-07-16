<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\Response;
use App\Models\User;
use App\Notifications\RoundAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InterviewController extends Controller
{
    /**
     * Show the interview form for a candidate in a specific round.
     */
    public function show(Candidate $candidate, InterviewRound $round)
    {
        $interviewer = Auth::user();

        $progress = CandidateRoundProgress::where('candidate_id', $candidate->id)
            ->where('round_id', $round->id)
            ->where('interviewer_id', $interviewer->id)
            ->firstOrFail();

        $progress->load('round');
        $candidate->load('branch', 'formSubmission.form');

        // Build questions with existing responses
        $questions = $round->questions()->get()->map(fn($q) => [
            'id' => $q->id,
            'question_text' => $q->question_text,
            'question_type' => $q->question_type,
            'is_mandatory' => $q->is_mandatory,
            'is_custom' => $q->is_custom,
            'order' => $q->order,
            'options' => $q->options,
            'response' => $this->getResponse($progress->id, $q->id),
        ]);

        // Get previous round feedback (visible to current interviewer)
        $previousFeedback = CandidateRoundProgress::where('candidate_id', $candidate->id)
            ->where('id', '!=', $progress->id)
            ->where('status', 'completed')
            ->with('round', 'interviewer')
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'round' => $p->round->name,
                'interviewer' => $p->interviewer->full_name,
                'rating' => $p->overall_rating,
                'feedback' => $p->overall_feedback,
                'date' => $p->end_date?->format('d M Y'),
            ]);

        // Available next rounds (rounds after current sequence)
        $nextRounds = InterviewRound::active()
            ->where('sequence_number', '>', $round->sequence_number)
            ->ordered()
            ->get(['id', 'name', 'is_hr_round', 'is_ops_round']);

        // Available interviewers
        $Interviewers = User::role('interviewer')
            ->active()
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        $availableInterviewers = $Interviewers->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->full_name,
            'emp' => $u->employee_id,
            'can_take_round' => $u->canInterviewRound($round),
        ]);

        // NEW: Get registration form fields and submission data
        $form = $candidate->latestSubmission?->form;
        $submissionData = $candidate->latestSubmission?->submission_data ?? [];

        $registrationForm = null;
        if ($form) {
            $registrationForm = [
                'form_name' => $form->name,
                'submitted_at' => $candidate->latestSubmission?->submitted_at?->format('d M Y, h:i A'),
                'fields' => $form->fields->map(fn($field) => [
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'is_mandatory' => $field->is_mandatory,
                    'options' => $field->options,
                    'value' => $submissionData[$field->field_name] ?? null,
                ]),
            ];
        }

        return Inertia::render('Interviewer/Interviews/Show', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'position' => $candidate->position_applied,
                'profile_label' => $candidate->getProfileCategoryLabel(),
                'profile_category' => $candidate->profile_category,
                'branch' => $candidate->branch->name,
                'resume_path' => $candidate->latestSubmission?->resume_path,
                'resume_name' => $candidate->latestSubmission?->resume_original_name,
                'average_rating' => $candidate->getAverageRating(),
                'completed_rounds' => $candidate->completedRoundsCount(),
                'requires_salary_hr' => $candidate->requiresSalaryInHrRound(),
                'salary_post_ops' => $candidate->isSalaryPostOps(),
            ],
            'interview' => [
                'id' => $progress->id,
                'round_id' => $round->id,
                'round_name' => $round->name,
                'is_hr_round' => $round->is_hr_round,
                'is_ops_round' => $round->is_ops_round,
                'status' => $progress->status,
                'started_at' => $progress->start_date?->format('d M Y H:i'),
                'salary_offer_min' => $progress->salary_offer_min,
                'salary_offer_max' => $progress->salary_offer_max,
                'offered_designation' => $progress->offered_designation,
                'salary_offer_status' => $progress->salary_offer_status,
                'overall_feedback' => $progress->overall_feedback,
                'overall_rating' => $progress->overall_rating,
            ],
            'questions' => $questions,
            'previous_feedback' => $previousFeedback,
            'next_rounds' => $nextRounds,
            'available_interviewers' => $availableInterviewers,
            // Salary offer logic for frontend
            'salary_logic' => [
                'is_mandatory' => $round->is_hr_round && $candidate->requiresSalaryInHrRound(),
                'is_optional_hr' => $round->is_hr_round && !$candidate->requiresSalaryInHrRound() && !$candidate->isSalaryPostOps(),
                'is_post_ops' => $candidate->isSalaryPostOps(),
                'show_salary_section' => ($round->is_ops_round && $candidate->requiresSalaryInOpsRound()) || ($round->is_hr_round && ($candidate->requiresSalaryInHrRound() || !$candidate->isSalaryPostOps())),
                'salary_hint' => $this->getSalaryHint($round, $candidate),
            ],
            // Registration form data
            'registration_form' => $registrationForm,
        ]);
    }

    /**
     * Start an interview.
     */
    public function start(Candidate $candidate, InterviewRound $round)
    {
        $progress = $this->getProgress($candidate->id, $round->id);
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

        $progress = CandidateRoundProgress::where('candidate_id', $candidate->id)
            ->where('round_id', $round->id)
            ->firstOrFail();

        $newInterviewer = User::role('interviewer')->active()->findOrFail($data['new_interviewer_id']);

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
    public function saveResponse(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $data = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'response_text' => 'nullable|string',
            'rating_value' => 'nullable|integer|min:1|max:5',
            'yes_no_value' => 'nullable|boolean',
            'selected_options' => 'nullable|array',
            'interviewer_notes' => 'nullable|string|max:1000',
            'question_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $progress = $this->getProgress($candidate->id, $round->id);

        Response::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'question_id' => $data['question_id'],
                'progress_id' => $progress->id,
            ],
            $data
        );

        return back()->with('success', 'Response saved.');
    }

    /**
     * Complete the current round and optionally move to next round.
     */
    public function complete(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $progress = $this->getProgress($candidate->id, $round->id);

        $data = $request->validate([
            'overall_feedback' => 'required|string|max:3000',
            'overall_rating' => 'required|numeric|min:0|max:5',
            'next_round_id' => 'nullable|exists:interview_rounds,id',
            'next_interviewer_id' => 'nullable|exists:users,id',
            // Salary offer range fields for OPS and HR rounds
            'salary_offer_min' => 'nullable|numeric|min:0',
            'salary_offer_max' => 'nullable|numeric|min:0',
            'offered_designation' => 'nullable|string|max:255',
            'salary_offer_status' => 'nullable|in:pending,accepted,negotiating,declined',
        ]);

        if ($round->is_hr_round && $candidate->requiresSalaryInHrRound()) {
            if (is_null($data['salary_offer_min']) || is_null($data['salary_offer_max'])) {
                return back()->withErrors([
                    'salary_offer_min' =>
                        'Final HR salary range is required for this profile.',
                    'salary_offer_max' =>
                        'Final HR salary range is required for this profile.',
                ]);
            }
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

        // ── Complete this round ──
        $progress->completeRound([
            'overall_feedback' => $data['overall_feedback'],
            'overall_rating' => $data['overall_rating'],
            'next_round_id' => $data['next_round_id'] ?? null,
            'next_interviewer_id' => $data['next_interviewer_id'] ?? null,
            'salary_offer_min' => $data['salary_offer_min'] ?? null,
            'salary_offer_max' => $data['salary_offer_max'] ?? null,
            'offered_designation' => $data['offered_designation'] ?? null,
            'salary_offer_status' => $data['salary_offer_status'] ?? null,
        ]);

        // ── Move to next round ──
        if (!empty($data['next_round_id']) && !empty($data['next_interviewer_id'])) {
            $nextRound = InterviewRound::findOrFail($data['next_round_id']);
            $nextInterviewer = User::role('interviewer')->active()->findOrFail($data['next_interviewer_id']);

            if (!$nextInterviewer->canInterviewRound($nextRound)) {
                return back()->withErrors([
                    'next_interviewer_id' => 'Selected interviewer cannot take the chosen next round.',
                ]);
            }

            CandidateRoundProgress::create([
                'candidate_id' => $candidate->id,
                'round_id' => $data['next_round_id'],
                'interviewer_id' => $data['next_interviewer_id'],
                'status' => 'pending',
            ]);

            $candidate->update([
                'current_status' => 'round_completed',
                'current_round_id' => $data['next_round_id'],
                'current_interviewer_id' => $data['next_interviewer_id'],
            ]);
        } else {
            // Final round completed
            $candidate->update([
                'current_status' => 'all_rounds_cleared',
                'current_round_id' => null,
                'current_interviewer_id' => null,
            ]);
        }

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

        $progress = $this->getProgress($candidate->id, $round->id);
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
    public function addCustomQuestion(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $data = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:short_answer,long_answer,rating_scale,yes_no',
        ]);

        $maxOrder = $round->questions()->max('order') ?? 0;

        Question::create([
            'round_id' => $round->id,
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'is_mandatory' => false,
            'is_custom' => true,
            'order' => $maxOrder + 1,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Custom question added.');
    }

    /**
     * Fetch interviewers for a specific round.
     */
    public function getInterviewers(Candidate $candidate, InterviewRound $round)
    {
        $interviewers = User::role('interviewer')
            ->active()
            ->get(['id', 'first_name', 'last_name', 'employee_id'])
            ->filter(fn($u) => $u->canInterviewRound($round))
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'emp' => $u->employee_id,
            ]);

        return response()->json([
            'interviewers' => $interviewers->values(),
        ]);
    }

    /* ── Private Helpers ── */

    private function getProgress(int $candidateId, int $roundId): CandidateRoundProgress
    {
        return CandidateRoundProgress::where('candidate_id', $candidateId)
            ->where('round_id', $roundId)
            ->where('interviewer_id', Auth::id())
            ->firstOrFail();
    }

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
}