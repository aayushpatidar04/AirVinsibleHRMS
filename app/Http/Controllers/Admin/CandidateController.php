<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InterviewScheduledMail;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\InterviewRound;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $candidates = Candidate::with('branch', 'currentRound', 'currentInterviewer')
            ->when(
                $request->search,
                fn($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
            )
            ->when($request->branch_id, fn($q, $b) => $q->where('branch_id', $b))
            ->when($request->status, fn($q, $s) => $q->where('current_status', $s))
            ->when($request->final_status, fn($q, $s) => $q->where('final_status', $s))
            ->when($request->profile, fn($q, $p) => $q->where('profile_category', $p))
            ->when($request->applicant_type, fn($q, $a) => $q->where('applicant_type', $a))
            ->latest('registration_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
            'branches' => Branch::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'branch_id', 'status', 'final_status', 'profile', 'applicant_type']),
            'statusOptions' => Candidate::CURRENT_STATUSES,
            'finalStatusOptions' => Candidate::FINAL_STATUSES,
            'profileOptions' => Candidate::PROFILE_LABELS,
            'applicantTypeOptions' => Candidate::APPLICANT_TYPES,
        ]);
    }

    public function show(Candidate $candidate)
    {
        $candidate->load('branch', 'currentRound', 'currentInterviewer', 'formSubmission.form');

        $progressHistory = $candidate->roundProgress()
            ->with('round', 'interviewer', 'responses.question')
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'round_id' => $p->round_id,
                'round_name' => $p->round->name,
                'is_hr_round' => $p->round->is_hr_round,
                'is_ops_round' => $p->round->is_ops_round,
                'interviewer' => $p->interviewer->full_name,
                'interviewer_id' => $p->interviewer_id,
                'status' => $p->status,
                'started_at' => $p->start_date?->format('d M Y H:i'),
                'ended_at' => $p->end_date?->format('d M Y H:i'),
                'duration' => $p->getDurationLabel(),
                'rating' => $p->overall_rating,
                'rating_stars' => $p->getRatingStars(),
                'feedback' => $p->overall_feedback,
                'rejection_reason' => $p->rejection_reason,
                'salary_offer_min' => $p->salary_offer_min,
                'salary_offer_max' => $p->salary_offer_max,
                'offered_designation' => $p->offered_designation,
                'salary_offer_status' => $p->salary_offer_status,
                'responses' => $p->responses->map(fn($r) => [
                    'question' => $r->question->question_text,
                    'type' => $r->question->question_type,
                    'is_mandatory' => $r->question->is_mandatory,
                    'response_text' => $r->response_text,
                    'rating_value' => $r->rating_value,
                    'yes_no_value' => $r->yes_no_value,
                    'interviewer_notes' => $r->interviewer_notes,
                    'question_rating' => $r->question_rating,
                ]),
            ]);

        $availableInterviewers = User::role('interviewer')
            ->active()
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'emp' => $u->employee_id,
                'can_interview_rounds' => $u->allowedRounds->pluck('id')->toArray(),
            ]);

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

        $schedules = InterviewSchedule::where('candidate_id', $candidate->id)
            ->with('round', 'interviewer')
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'round_id' => $s->round_id,
                'round_name' => $s->round->name,
                'interviewer_name' => $s->interviewer->full_name,
                'scheduled_at' => $s->formattedTime(),
                'scheduled_at_raw' => $s->scheduled_at->toISOString(),
                'gmeet_link' => $s->gmeet_link,
                'notes' => $s->notes,
                'status' => $s->status,
                'is_upcoming' => $s->isUpcoming(),
                'time_until' => $s->timeUntil(),
                'sent_at' => $s->sent_at?->format('d M Y H:i'),
            ]);

        $rejectionReasons = [
            'Lack of basic computer knowledge',
            'High Salary Expectation',
            'Poor English & Hindi Communication',
            'Distance Issue',
            'Age Issue',
            'MTI Issue',
            'Shift Issue',
            'Looking for WFH',
            'Required long leave in 90 days',
            'Others',
        ];

        $holdReasons = [
            'Lack of basic computer knowledge',
            'High Salary Expectation',
            'Poor English & Hindi Communication',
            'Distance Issue',
            'Age Issue',
            'MTI Issue',
            'Shift Issue',
            'Looking for WFH',
            'Required long leave in 90 days',
            'Others',
        ];

        $processOptions = [
            'Airtel-DTH',
            'Airtel-BB',
            'Airtel-MNP MP',
            'Airtel-MNP UP-BH',
            'Airtel-Collection',
            'Airtel-Telemedia',
            'Airtel-Marketing',
            'Beetel',
            'LiveKeeping',
            'Unacademy',
            'HDFC-CC',
            'Zepto-Driver Support',
            'Zepto-Store-Sch No 54',
            'Zepto-Store-Bicholi Mardana',
            'Swiggy-Chat',
            'Swiggy-Voice',
            'Swiggy-HITL',
            'Swiggy-Email',
            'Swiggy-PS',
            'Swiggy-GG',
            'Swiggy-PT-Chat',
            'Swiggy-PT-Voice',
        ];

        return Inertia::render('Admin/Candidates/Show', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'position' => $candidate->position_applied,
                'profile_category' => $candidate->profile_category,
                'profile_label' => $candidate->getProfileCategoryLabel(),
                'branch' => $candidate->branch->name,
                'current_status' => $candidate->current_status,
                'final_status' => $candidate->final_status,
                'current_round' => $candidate->currentRound?->name,
                'current_interviewer' => $candidate->currentInterviewer?->full_name,
                'hiring_notes' => $candidate->hiring_notes,
                'final_ctc' => $candidate->final_ctc_offered,
                'final_in_hand' => $candidate->final_in_hand_offered,
                'final_pf_allowed' => $candidate->final_pf_allowed,
                'final_designation' => $candidate->final_designation,
                'average_rating' => $candidate->getAverageRating(),
                'completed_rounds' => $candidate->completedRoundsCount(),
                'registered_at' => $candidate->registration_date->format('d M Y H:i'),
                'resume_path' => $candidate->latestSubmission?->resume_path,
                'resume_name' => $candidate->latestSubmission?->resume_original_name,
                'requires_salary_hr' => $candidate->requiresSalaryInHrRound(),
                'salary_post_ops' => $candidate->isSalaryPostOps(),
                'latest_salary_offer' => $candidate->getLatestSalaryOffer(),
                'applicant_type' => $candidate->applicant_type,
                'applicant_type_label' => $candidate->getApplicantTypeLabel(),
                'approval_status' => $candidate->approval_status,
                'approval_status_label' => $candidate->getApprovalStatusLabel(),
                'approval_notes' => $candidate->approval_notes,
                'approved_at' => $candidate->approved_at?->format('d M Y H:i'),
                'old_employee' => $candidate->oldEmployee ? [
                    'id' => $candidate->oldEmployee->id,
                    'name' => $candidate->oldEmployee->full_name,
                ] : null,
                'requires_approval' => $candidate->requiresApproval(),
                'can_start_interviews' => $candidate->canStartInterviews(),
                'process_name' => $candidate->process_name,
                'rejection_reason' => $candidate->rejection_reason,
                'rejection_remarks' => $candidate->rejection_remarks,
                'hold_reason' => $candidate->hold_reason,
                'hold_remarks' => $candidate->hold_remarks,
                'salary_annexure' => $candidate->salary_annexure,
            ],
            'progress_history' => $progressHistory,
            'finalStatusOptions' => Candidate::FINAL_STATUSES,
            'available_interviewers' => $availableInterviewers,
            'registration_form' => $registrationForm,
            'schedules' => $schedules,
            'rejectionReasons' => $rejectionReasons,
            'holdReasons' => $holdReasons,
            'processOptions' => $processOptions,
        ]);
    }

    public function updateApprovalStatus(Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'approval_status' => 'required|in:pending,approved,rejected',
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        if (!$candidate->requiresApproval()) {
            return back()->withErrors(['approval_status' => 'This candidate does not require rejoin approval.']);
        }

        $candidateUpdate = [
            'approval_status' => $data['approval_status'],
            'approval_notes' => $data['approval_notes'] ?? null,
            'approved_at' => $data['approval_status'] === 'approved' ? now() : null,
            'approved_by' => $data['approval_status'] === 'approved' ? auth()->id() : null,
        ];

        if ($data['approval_status'] === 'approved') {
            $candidateUpdate['current_status'] = 'all_rounds_cleared';
            $candidateUpdate['final_status'] = 'selected';
        } elseif ($data['approval_status'] === 'rejected') {
            $candidateUpdate['current_status'] = 'rejected';
            $candidateUpdate['final_status'] = 'not_selected';
        }

        $candidate->update($candidateUpdate);

        if ($data['approval_status'] === 'approved' && $candidate->oldEmployee) {
            $employee = $candidate->oldEmployee;
            $employee->restore();
            $employee->update([
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'designation' => $candidate->position_applied,
                'branch_id' => $candidate->branch_id,
                'employment_status' => 'active',
                'is_active' => true,
                'date_of_joining' => now(),
            ]);
        }

        return back()->with('success', 'Candidate approval status updated successfully.');
    }

    public function updateFinalStatus(Request $request, Candidate $candidate)
    {
        $baseRules = [
            'final_status' => 'required|in:pending,selected,not_selected',
            'hiring_notes' => 'nullable|string|max:2000',
            'salary_annexure' => 'nullable',
        ];

        // Dynamic validation based on selected status
        $dynamicRules = match ($request->final_status) {
            'selected' => [
                'process_name' => 'required|string|in:' . implode(',', [
                    'Airtel-DTH',
                    'Airtel-BB',
                    'Airtel-MNP MP',
                    'Airtel-MNP UP-BH',
                    'Airtel-Collection',
                    'Airtel-Telemedia',
                    'Airtel-Marketing',
                    'Beetel',
                    'LiveKeeping',
                    'Unacademy',
                    'HDFC-CC',
                    'Zepto-Driver Support',
                    'Zepto-Store-Sch No 54',
                    'Zepto-Store-Bicholi Mardana',
                    'Swiggy-Chat',
                    'Swiggy-Voice',
                    'Swiggy-HITL',
                    'Swiggy-Email',
                    'Swiggy-PS',
                    'Swiggy-GG',
                    'Swiggy-PT-Chat',
                    'Swiggy-PT-Voice',
                ]),
                'final_ctc' => 'required|numeric|min:0',
                'final_in_hand' => 'required|numeric|min:0',
                'final_pf_allowed' => 'required|boolean',
                'final_designation' => 'nullable|string|max:255',
            ],
            'not_selected' => [
                'rejection_reason' => 'required|string|in:' . implode(',', [
                    'Lack of basic computer knowledge',
                    'High Salary Expectation',
                    'Poor English & Hindi Communication',
                    'Distance Issue',
                    'Age Issue',
                    'MTI Issue',
                    'Shift Issue',
                    'Looking for WFH',
                    'Required long leave in 90 days',
                    'Others',
                ]),
                'remarks' => 'nullable|string|max:2000',
            ],
            'pending' => [
                'hold_reason' => 'required|string|in:' . implode(',', [
                    'Lack of basic computer knowledge',
                    'High Salary Expectation',
                    'Poor English & Hindi Communication',
                    'Distance Issue',
                    'Age Issue',
                    'MTI Issue',
                    'Shift Issue',
                    'Looking for WFH',
                    'Required long leave in 90 days',
                    'Others',
                ]),
                'remarks' => 'nullable|string|max:2000',
            ],
            default => [],
        };

        $data = $request->validate(array_merge($baseRules, $dynamicRules));

        // Build update payload
        $updateData = [
            'final_status' => $data['final_status'],
            'hiring_notes' => $data['hiring_notes'] ?? null,
            'salary_annexure' => $data['salary_annexure'] ?? null,
        ];

        // ─── SELECTED ───
        if ($data['final_status'] === 'selected') {
            $updateData['process_name'] = $data['process_name'];
            $updateData['final_ctc_offered'] = $data['final_ctc'] ?? null;
            $updateData['final_in_hand_offered'] = $data['final_in_hand'] ?? null;
            $updateData['final_pf_allowed'] = $data['final_pf_allowed'] ?? false;
            $updateData['final_designation'] = $data['final_designation'] ?? null;
            $updateData['final_salary_offered'] = $data['final_ctc'] ?? null;
            $updateData['current_status'] = 'all_rounds_cleared';

            // Clear rejection/hold fields if previously set
            $updateData['rejection_reason'] = null;
            $updateData['rejection_remarks'] = null;
            $updateData['hold_reason'] = null;
            $updateData['hold_remarks'] = null;
        }

        // ─── NOT SELECTED ───
        if ($data['final_status'] === 'not_selected') {
            $updateData['rejection_reason'] = $data['rejection_reason'];
            $updateData['rejection_remarks'] = $data['remarks'] ?? null;
            $updateData['current_status'] = 'rejected';

            // Clear selected/hold fields
            $updateData['process_name'] = null;
            $updateData['final_salary_offered'] = null;
            $updateData['final_ctc_offered'] = null;
            $updateData['final_in_hand_offered'] = null;
            $updateData['final_pf_allowed'] = false;
            $updateData['final_designation'] = null;
            $updateData['hold_reason'] = null;
            $updateData['hold_remarks'] = null;
        }

        // ─── DECISION PENDING (HOLD) ───
        if ($data['final_status'] === 'pending') {
            $updateData['hold_reason'] = $data['hold_reason'];
            $updateData['hold_remarks'] = $data['remarks'] ?? null;
            $updateData['current_status'] = 'on_hold';

            // Clear selected/rejection fields
            $updateData['process_name'] = null;
            $updateData['final_salary_offered'] = null;
            $updateData['final_ctc_offered'] = null;
            $updateData['final_in_hand_offered'] = null;
            $updateData['final_pf_allowed'] = false;
            $updateData['final_designation'] = null;
            $updateData['rejection_reason'] = null;
            $updateData['rejection_remarks'] = null;
        }

        $candidate->update($updateData);

        return back()->with('success', 'Candidate status updated successfully.');
    }

    public function destroy(Candidate $candidate)
    {
        $candidate->delete();
        return redirect()->route('admin.candidates.index')->with('success', 'Candidate removed.');
    }

    public function scheduleInterview(Request $request, Candidate $candidate, InterviewRound $round)
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'gmeet_link' => ['required', 'url', 'starts_with:https://meet.google.com/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'interviewer_id' => ['required', 'exists:users,id'],
        ]);

        // Check if schedule already exists for this round
        $existing = InterviewSchedule::where('candidate_id', $candidate->id)
            ->where('round_id', $round->id)
            ->where('status', 'scheduled')
            ->first();

        if ($existing) {
            $existing->update([
                'interviewer_id' => $validated['interviewer_id'],
                'scheduled_at' => $validated['scheduled_at'],
                'gmeet_link' => $validated['gmeet_link'],
                'notes' => $validated['notes'],
            ]);
            $schedule = $existing;
        } else {
            $schedule = InterviewSchedule::create([
                'candidate_id' => $candidate->id,
                'round_id' => $round->id,
                'interviewer_id' => $validated['interviewer_id'],
                'scheduled_at' => $validated['scheduled_at'],
                'gmeet_link' => $validated['gmeet_link'],
                'notes' => $validated['notes'],
            ]);
        }

        // Auto-send email to candidate
        Mail::to($candidate->email)->send(new InterviewScheduledMail($schedule));

        $schedule->update(['sent_at' => now()]);

        return back()->with('success', 'Interview scheduled and email sent to candidate.');
    }

    public function updateSchedule(Request $request, InterviewSchedule $schedule)
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'gmeet_link' => ['required', 'url', 'starts_with:https://meet.google.com/'],
            'notes' => ['nullable', 'string'],
        ]);

        $schedule->update($validated);

        // Resend email if time changed significantly
        if ($schedule->wasChanged('scheduled_at') || $schedule->wasChanged('gmeet_link')) {
            Mail::to($schedule->candidate->email)->send(new InterviewScheduledMail($schedule));
            $schedule->update(['sent_at' => now()]);
        }

        return back()->with('success', 'Schedule updated and candidate notified.');
    }

    public function cancelSchedule(InterviewSchedule $schedule)
    {
        $schedule->update(['status' => 'cancelled']);
        return back()->with('success', 'Interview cancelled.');
    }

    public function sendScheduleEmail(InterviewSchedule $schedule)
    {
        Mail::to($schedule->candidate->email)->send(new InterviewScheduledMail($schedule));
        $schedule->update(['sent_at' => now()]);
        return back()->with('success', 'Email resent to candidate.');
    }

}