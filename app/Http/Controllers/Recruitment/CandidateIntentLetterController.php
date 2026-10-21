<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\SaveCandidateIntentLetterRequest;
use App\Mail\CandidateIntentLetterMail;
use App\Models\Candidate;
use App\Models\CandidateIntentLetter;
use App\Services\Recruitment\CandidateIntentLetterPdfService;
use App\Services\Recruitment\CandidateIntentLetterPortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CandidateIntentLetterController extends Controller
{
    public function create(Candidate $candidate): Response|RedirectResponse
    {
        $this->authorize('finalDecision', $candidate);
        abort_unless($candidate->final_status === 'selected', 403);

        $latestLetter = $candidate->latestIntentLetter;

        if (
            $latestLetter &&
            $latestLetter->status !== CandidateIntentLetter::STATUS_CANCELLED
        ) {
            return redirect()->route(
                'recruitment.intent-letters.show',
                $latestLetter
            );
        }

        return Inertia::render('Recruitment/IntentLetters/Create', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
            ],
            'defaults' => [
                'position_title' => $candidate->final_designation
                    ?: $candidate->position_applied,
                'department' => '',
                'reporting_to' => '',
                'work_location' => $candidate->branch?->name ?? '',
                'monthly_ctc' => $candidate->final_ctc_offered ?? '',
                'monthly_in_hand' => $candidate->final_in_hand_offered ?? '',
                'joining_date' => '',
            ],
            'routes' => [
                'store' => route(
                    'recruitment.candidates.intent-letters.store',
                    $candidate
                ),
                'back' => route('recruitment.candidates.show', $candidate),
            ],
        ]);
    }

    public function store(
        SaveCandidateIntentLetterRequest $request,
        Candidate $candidate,
        CandidateIntentLetterPdfService $pdfService
    ): RedirectResponse {
        $this->authorize('finalDecision', $candidate);
        abort_unless($candidate->final_status === 'selected', 403);

        $letter = DB::transaction(function () use ($candidate, $request): CandidateIntentLetter {
            $lockedCandidate = Candidate::query()
                ->lockForUpdate()
                ->findOrFail($candidate->id);

            if ($lockedCandidate->final_status !== 'selected') {
                abort(409, 'The candidate must be selected before an intent letter can be created.');
            }

            $existingLetter = $lockedCandidate->intentLetters()
                ->latest('id')
                ->first();

            if (
                $existingLetter &&
                $existingLetter->status !== CandidateIntentLetter::STATUS_CANCELLED
            ) {
                abort(409, 'An active Letter of Intent already exists for this candidate.');
            }

            return $lockedCandidate->intentLetters()->create([
                ...$request->validated(),
                'status' => CandidateIntentLetter::STATUS_DRAFT,
                'letter_date' => today(),
            ]);
        });

        $pdfService->generate($letter);

        return redirect()
            ->route('recruitment.intent-letters.show', $letter)
            ->with('success', 'Letter of Intent draft created.');
    }

    public function edit(CandidateIntentLetter $candidateIntentLetter): Response
    {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);
        abort_unless(
            $candidateIntentLetter->status === CandidateIntentLetter::STATUS_DRAFT,
            409,
            'Only draft intent letters can be edited.'
        );

        return Inertia::render('Recruitment/IntentLetters/Edit', [
            'candidate' => [
                'id' => $candidateIntentLetter->candidate->id,
                'name' => $candidateIntentLetter->candidate->full_name,
                'email' => $candidateIntentLetter->candidate->email,
            ],
            'defaults' => [
                'position_title' => $candidateIntentLetter->position_title,
                'department' => $candidateIntentLetter->department,
                'reporting_to' => $candidateIntentLetter->reporting_to,
                'work_location' => $candidateIntentLetter->work_location,
                'monthly_ctc' => $candidateIntentLetter->monthly_ctc,
                'monthly_in_hand' => $candidateIntentLetter->monthly_in_hand,
                'joining_date' => $candidateIntentLetter->joining_date->format('Y-m-d'),
            ],
            'routes' => [
                'update' => route(
                    'recruitment.intent-letters.update',
                    $candidateIntentLetter
                ),
                'back' => route(
                    'recruitment.intent-letters.show',
                    $candidateIntentLetter
                ),
            ],
        ]);
    }

    public function update(
        SaveCandidateIntentLetterRequest $request,
        CandidateIntentLetter $candidateIntentLetter,
        CandidateIntentLetterPdfService $pdfService
    ): RedirectResponse {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);
        abort_unless(
            $candidateIntentLetter->status === CandidateIntentLetter::STATUS_DRAFT,
            409,
            'Only draft intent letters can be edited.'
        );

        $candidateIntentLetter->update($request->validated());
        $pdfService->generate($candidateIntentLetter);

        return redirect()
            ->route('recruitment.intent-letters.show', $candidateIntentLetter)
            ->with('success', 'Letter of Intent draft updated.');
    }

    public function show(
        CandidateIntentLetter $candidateIntentLetter
    ): Response {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);

        $candidateIntentLetter->loadMissing(['candidate', 'sender']);
        $candidate = $candidateIntentLetter->candidate;

        return Inertia::render('Recruitment/IntentLetters/Show', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
            ],
            'letter' => [
                'id' => $candidateIntentLetter->id,
                'letter_number' => $candidateIntentLetter->letter_number,
                'status' => $candidateIntentLetter->status,
                'position_title' => $candidateIntentLetter->position_title,
                'department' => $candidateIntentLetter->department,
                'reporting_to' => $candidateIntentLetter->reporting_to,
                'work_location' => $candidateIntentLetter->work_location,
                'monthly_ctc' => (float) $candidateIntentLetter->monthly_ctc,
                'monthly_in_hand' => (float) $candidateIntentLetter->monthly_in_hand,
                'joining_date' => $candidateIntentLetter->joining_date->format('Y-m-d'),
                'letter_date' => $candidateIntentLetter->letter_date->format('Y-m-d'),
                'sent_at' => $candidateIntentLetter->sent_at?->toISOString(),
                'viewed_at' => $candidateIntentLetter->viewed_at?->toISOString(),
                'acknowledged_at' => $candidateIntentLetter->acknowledged_at?->toISOString(),
                'acknowledgement_name' => $candidateIntentLetter->acknowledgement_name,
                'acknowledgement_text' => $candidateIntentLetter->acknowledgement_text,
                'candidate_consent' => $candidateIntentLetter->candidate_consent,
                'sent_to_email' => $candidateIntentLetter->sent_to_email,
                'send_count' => $candidateIntentLetter->send_count,
                'pdf_available' => filled($candidateIntentLetter->pdf_path),
                'acknowledged_pdf_available' => filled(
                    $candidateIntentLetter->acknowledged_pdf_path
                ),
                'rejection_name' => $candidateIntentLetter->rejection_name,
                'rejection_remark' => $candidateIntentLetter->rejection_remark,
                'rejected_at' => $candidateIntentLetter->rejected_at?->toISOString(),
                'sender_name' => $candidateIntentLetter->sender?->name,
            ],
            'permissions' => [
                'edit' => $candidateIntentLetter->status === CandidateIntentLetter::STATUS_DRAFT,
                'send' => in_array($candidateIntentLetter->status, [
                    CandidateIntentLetter::STATUS_DRAFT,
                    CandidateIntentLetter::STATUS_SENT,
                    CandidateIntentLetter::STATUS_VIEWED,
                ], true),
                'cancel' => !in_array($candidateIntentLetter->status, [
                    CandidateIntentLetter::STATUS_CANCELLED,
                ], true) && !$candidate->offers()->exists(),
                'create_offer' => $candidateIntentLetter->isAcknowledged()
                    && !$candidate->offers()->open()->exists(),
            ],
            'routes' => [
                'candidate' => route('recruitment.candidates.show', $candidate),
                'edit' => route('recruitment.intent-letters.edit', $candidateIntentLetter),
                'send' => route('recruitment.intent-letters.send', $candidateIntentLetter),
                'cancel' => route('recruitment.intent-letters.cancel', $candidateIntentLetter),
                'download' => route('recruitment.intent-letters.download', $candidateIntentLetter),
                'create_offer' => route(
                    'recruitment.candidates.offers.create',
                    $candidate
                ),
            ],
        ]);
    }

    public function send(
        CandidateIntentLetter $candidateIntentLetter,
        CandidateIntentLetterPdfService $pdfService,
        CandidateIntentLetterPortalTokenService $tokenService
    ): RedirectResponse {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);

        if (!in_array($candidateIntentLetter->status, [
            CandidateIntentLetter::STATUS_DRAFT,
            CandidateIntentLetter::STATUS_SENT,
            CandidateIntentLetter::STATUS_VIEWED,
        ], true)) {
            return back()->with('error', 'This Letter of Intent cannot be sent.');
        }

        if (!$candidateIntentLetter->candidate->email) {
            return back()->with('error', 'The candidate does not have an email address.');
        }

        try {
            if (
                !$candidateIntentLetter->pdf_path ||
                !Storage::disk('local')->exists($candidateIntentLetter->pdf_path)
            ) {
                $pdfService->generate($candidateIntentLetter);
            }

            $token = $tokenService->issue($candidateIntentLetter);
            $portalUrl = route('candidate-intent-letters.portal.show', $token);

            Mail::to($candidateIntentLetter->candidate->email)->send(
                new CandidateIntentLetterMail($candidateIntentLetter, $portalUrl)
            );

            $candidateIntentLetter->forceFill([
                'status' => CandidateIntentLetter::STATUS_SENT,
                'sent_to_email' => $candidateIntentLetter->candidate->email,
                'sent_at' => now(),
                'sent_by' => request()->user()->id,
                'send_count' => $candidateIntentLetter->send_count + 1,
            ])->save();

            return back()->with('success', 'Letter of Intent sent to the candidate.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to send the Letter of Intent.'
            );
        }
    }

    public function cancel(
        CandidateIntentLetter $candidateIntentLetter
    ): RedirectResponse {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);

        $candidate = DB::transaction(function () use ($candidateIntentLetter): Candidate {
            $candidate = Candidate::query()
                ->lockForUpdate()
                ->findOrFail($candidateIntentLetter->candidate_id);

            $letter = CandidateIntentLetter::query()
                ->lockForUpdate()
                ->findOrFail($candidateIntentLetter->id);

            if (
                $letter->status === CandidateIntentLetter::STATUS_CANCELLED ||
                $candidate->offers()->exists()
            ) {
                abort(409, 'This Letter of Intent cannot be cancelled after a formal offer has been created.');
            }

            $letter->forceFill([
                'status' => CandidateIntentLetter::STATUS_CANCELLED,
                'portal_token_hash' => null,
                'portal_token_expires_at' => null,
            ])->save();

            return $candidate;
        });

        return redirect()
            ->route('recruitment.candidates.show', $candidate)
            ->with('success', 'Letter of Intent cancelled.');
    }

    public function download(
        CandidateIntentLetter $candidateIntentLetter
    ): StreamedResponse|RedirectResponse {
        $this->authorize('finalDecision', $candidateIntentLetter->candidate);

        $path = $candidateIntentLetter->acknowledged_pdf_path
            ?: $candidateIntentLetter->pdf_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->with('error', 'The Letter of Intent PDF is not available.');
        }

        $filename = $candidateIntentLetter->letter_number
            . ($candidateIntentLetter->acknowledged_pdf_path ? '-acknowledged' : '')
            . '.pdf';

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
