<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateFormSubmission;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    /**
     * Show registration form (accessed via QR code scan).
     */
    public function show(string $uuid)
    {
        $qr = QrCode::where('uuid', $uuid)->firstOrFail();

        if (!$qr->isValid()) {
            return Inertia::render('Candidate/Expired', [
                'message' => $qr->isExpired()
                    ? 'This QR code has expired.'
                    : 'This QR code is no longer active.',
            ]);
        }

        $form = $qr->form;
        $branch = $qr->branch;
        $fields = $form->fields()->get()->map(fn($f) => [
            'id' => $f->id,
            'field_name' => $f->field_name,
            'field_label' => $f->field_label,
            'field_type' => $f->field_type,
            'field_placeholder' => $f->field_placeholder,
            'is_mandatory' => $f->is_mandatory,
            'options' => $f->options,
            'is_system' => $f->is_system,
            'order' => $f->order,
            'parent_field_id' => $f->parent_field_id,
            'show_when' => $f->show_when,
        ]);

        return Inertia::render('Candidate/Register', [
            'qrUuid' => $uuid,
            'branch' => ['id' => $branch->id, 'name' => $branch->name, 'city' => $branch->city],
            'form' => ['id' => $form->id, 'name' => $form->name, 'description' => $form->description],
            'fields' => $fields,
            'profileOptions' => Candidate::PROFILE_LABELS,
        ]);
    }

    /**
     * Process registration form submission.
     */
    public function store(Request $request, string $uuid)
    {
        $qr = QrCode::where('uuid', $uuid)->firstOrFail();

        if (!$qr->isValid()) {
            return back()->withErrors(['qr' => 'QR code is no longer valid.']);
        }

        $form = $qr->form;
        $branch = $qr->branch;

        // Build validation rules dynamically from ALL form fields (including system)
        $rules = [];
        $messages = [];

        $formData = $request->all();
        foreach ($form->fields as $field) {
            if (!$field->isVisible($formData)) {
                continue;
            }

            $rules[$field->field_name] = $field->buildValidationRules($formData);
            $messages = array_merge($messages, $field->getValidationMessages());
        }

        $rules['applicant_type'] = 'nullable|in:Fresher,Experienced,Rejoining';

        $validated = $request->validate($rules, $messages);

        // Extract system field values from validated data
        $firstName = $validated['first_name'] ?? 'Unknown';
        $lastName = $validated['last_name'] ?? '';
        $email = $validated['email'] ?? 'no-email@' . uniqid() . '.local';
        $phone = $validated['phone'] ?? '0000000000';
        $position = $validated['position_applied'] ?? 'Not Specified';
        $category = $validated['profile_category'] ?? 'other';
        $applicantType = $validated['applicant_type'] ?? 'Fresher';

        $oldEmployee = User::findExited($email, $phone);
        $isDirectRejoin = false;
        $oldEmployeeId = null;

        if ($oldEmployee && $oldEmployee->isSameDesignation($position)) {
            $isDirectRejoin = true;
            $oldEmployeeId = $oldEmployee->id;
            $applicantType = 'Rejoining';
        }

        $cooldownDays = 30;

        $latestApplication = Candidate::latestByContact($email, $phone);

        if ($latestApplication) {
            $daysSinceLastApplication = $latestApplication->registration_date->diffInDays(now());

            if ($daysSinceLastApplication < $cooldownDays) {
                $remainingDays = ceil($cooldownDays - $daysSinceLastApplication);

                return back()->withErrors([
                    'cooldown' => "You have already registered on {$latestApplication->registration_date->format('d M Y')}. Please wait {$remainingDays} more day(s) before reapplying."
                ]);
            }
        }

        // Handle file uploads
        $submissionData = [];
        $firstFilePath = null;
        $firstFileName = null;

        foreach ($form->fields as $field) {
            $fieldName = $field->field_name;

            if ($field->field_type === 'file') {
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    $path = $file->store("uploads/{$branch->id}/" . date('Y-m'), 'public');
                    $originalName = $file->getClientOriginalName();

                    $submissionData[$fieldName] = [
                        'path' => $path,
                        'name' => $originalName,
                        'url' => Storage::url($path),
                    ];

                    if ($firstFilePath === null) {
                        $firstFilePath = $path;
                        $firstFileName = $originalName;
                    }
                }
            } elseif (isset($validated[$fieldName])) {
                $value = $validated[$fieldName];
                $submissionData[$fieldName] = is_array($value) ? $value : $value;
            }
        }

        // Create candidate
        $candidate = Candidate::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'position_applied' => $position,
            'profile_category' => $category,
            'branch_id' => $branch->id,
            'current_status' => 'new',
            'final_status' => 'pending',
            'applicant_type' => $applicantType,
            'approval_status' => $isDirectRejoin ? 'pending' : (strcasecmp($applicantType, 'Rejoining') === 0 ? 'pending' : null),
            'old_employee_id' => $oldEmployeeId,
        ]);

        // Store submission
        CandidateFormSubmission::create([
            'candidate_id' => $candidate->id,
            'form_id' => $form->id,
            'qr_code_id' => $qr->id,
            'submission_data' => $submissionData,
            'resume_path' => $firstFilePath,
            'resume_original_name' => $firstFileName,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        // Increment QR usage
        $qr->increment('usage_count');

        // Assign first interview round
        $firstRound = InterviewRound::active()->ordered()->first();

        if (!$isDirectRejoin && $firstRound) {
            // Find the least busy interviewer for this round
            $interviewer = $branch->users()
                ->role('interviewer')
                ->active()
                ->forRound($firstRound)
                ->withCount([
                    'candidateRoundProgress as pending_count' => function ($q) use ($firstRound) {
                        $q->where('status', 'pending')
                            ->where('round_id', $firstRound->id);
                    }
                ])
                ->orderBy('pending_count', 'asc')
                ->first();

            // Fallback: if no interviewer found for this round, pick least busy overall
            if (!$interviewer) {
                $interviewer = $branch->users()
                    ->role('interviewer')
                    ->active()
                    ->withCount([
                        'candidateRoundProgress as pending_count' => function ($q) {
                            $q->where('status', 'pending');
                        }
                    ])
                    ->orderBy('pending_count', 'asc')
                    ->first();
            }

            if ($interviewer) {
                CandidateRoundProgress::create([
                    'candidate_id' => $candidate->id,
                    'round_id' => $firstRound->id,
                    'interviewer_id' => $interviewer->id,
                    'status' => 'pending',
                ]);

                $candidate->update([
                    'current_round_id' => $firstRound->id,
                    'current_interviewer_id' => $interviewer->id,
                    'current_status' => 'in_progress',
                ]);
            }
        }


        return Inertia::render('Candidate/Success', [
            'candidate_id' => $candidate->id,
            'name' => $candidate->full_name,
            'position' => $candidate->position_applied,
            'branch' => $branch->name,
        ]);
    }

    /**
     * Show candidate status page.
     */
    public function status(string $uuid)
    {
        $candidate = Candidate::where(function ($q) use ($uuid) {
            $q->where('id', $uuid)
                ->orWhere('email', $uuid);
        })->firstOrFail();

        $progressHistory = $candidate->roundProgress()
            ->with('round', 'interviewer')
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'round' => $p->round->name,
                'interviewer' => $p->interviewer->full_name,
                'status' => $p->status,
                'rating' => $p->overall_rating,
                'feedback' => $p->overall_feedback,
                'date' => $p->start_date?->format('d M Y'),
            ]);

        return Inertia::render('Candidate/Status', [
            'candidate' => [
                'name' => $candidate->full_name,
                'position' => $candidate->position_applied,
                'branch' => $candidate->branch->name,
                'profile' => $candidate->getProfileCategoryLabel(),
                'current_status' => $candidate->current_status,
                'final_status' => $candidate->final_status,
                'average_rating' => $candidate->getAverageRating(),
                'registered_at' => $candidate->registration_date->format('d M Y'),
            ],
            'progress_history' => $progressHistory,
            'status_labels' => Candidate::CURRENT_STATUSES,
            'final_labels' => Candidate::FINAL_STATUSES,
        ]);
    }
}