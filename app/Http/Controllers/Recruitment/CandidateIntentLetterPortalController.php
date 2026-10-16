<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\AcknowledgeCandidateIntentLetterRequest;
use App\Http\Requests\Recruitment\DeclineCandidateIntentLetterRequest;
use App\Models\CandidateIntentLetter;
use App\Services\Recruitment\CandidateIntentLetterPdfService;
use App\Services\Recruitment\CandidateIntentLetterPortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateIntentLetterPortalController extends Controller
{
    public function __construct(
        private readonly CandidateIntentLetterPortalTokenService $tokenService,
        private readonly CandidateIntentLetterPdfService $pdfService
    ) {
    }

    public function show(Request $request, string $token): Response
    {
        $letter = $this->resolveLetter($token);
        $letter->loadMissing('candidate');

        if ($letter->status === CandidateIntentLetter::STATUS_SENT) {
            $letter->forceFill([
                'status' => CandidateIntentLetter::STATUS_VIEWED,
                'viewed_at' => now(),
            ])->save();
        }

        return Inertia::render('Public/IntentLetterPortal/Show', [
            'company' => [
                'name' => config('company.legal_name', 'Airvincible Pvt. Ltd.'),
                'email' => config('company.email') ?: 'hr@airvincible.com',
                'phone' => config('company.phone'),
            ],
            'candidate' => [
                'name' => $letter->candidate->full_name,
                'email' => $letter->candidate->email,
            ],
            'letter' => [
                'letter_number' => $letter->letter_number,
                'status' => $letter->status,
                'position_title' => $letter->position_title,
                'work_location' => $letter->work_location,
                'monthly_ctc' => (float) $letter->monthly_ctc,
                'monthly_in_hand' => (float) $letter->monthly_in_hand,
                'joining_date' => $letter->joining_date->format('Y-m-d'),
                'letter_date' => $letter->letter_date->format('Y-m-d'),
                'acknowledgement_name' => $letter->acknowledgement_name,
                'acknowledged_at' => $letter->acknowledged_at?->toISOString(),
                'rejection_name' => $letter->rejection_name,
                'rejection_remark' => $letter->rejection_remark,
                'rejected_at' => $letter->rejected_at?->toISOString(),
            ],
            'portal' => [
                'can_acknowledge' => in_array($letter->status, [
                    CandidateIntentLetter::STATUS_SENT,
                    CandidateIntentLetter::STATUS_VIEWED,
                ], true),
                'acknowledged' => $letter->isAcknowledged(),
                'declined' => $letter->isDeclined(),
                'routes' => [
                    'acknowledge' => route(
                        'candidate-intent-letters.portal.acknowledge',
                        $token
                    ),
                    'download' => route(
                        'candidate-intent-letters.portal.download',
                        $token
                    ),
                    'decline' => route(
                        'candidate-intent-letters.portal.decline',
                        $token
                    ),
                ],
            ],
        ]);
    }

    public function acknowledge(
        AcknowledgeCandidateIntentLetterRequest $request,
        string $token
    ): RedirectResponse {
        $letter = $this->resolveLetter($token);

        if (!in_array($letter->status, [
            CandidateIntentLetter::STATUS_SENT,
            CandidateIntentLetter::STATUS_VIEWED,
        ], true)) {
            return back()->with('error', 'This Letter of Intent cannot be acknowledged.');
        }

        DB::transaction(function () use ($letter, $request): void {
            $lockedLetter = CandidateIntentLetter::query()
                ->lockForUpdate()
                ->findOrFail($letter->id);

            if (!in_array($lockedLetter->status, [
                CandidateIntentLetter::STATUS_SENT,
                CandidateIntentLetter::STATUS_VIEWED,
            ], true)) {
                abort(409, 'This Letter of Intent has already been acknowledged.');
            }

            $lockedLetter->forceFill([
                'status' => CandidateIntentLetter::STATUS_ACKNOWLEDGED,
                'acknowledgement_name' => $request->validated('candidate_name'),
                'acknowledgement_text' => CandidateIntentLetter::acknowledgementText(
                    $request->validated('candidate_name')
                ),
                'candidate_consent' => true,
                'acknowledged_at' => now(),
                'acknowledgement_ip' => $request->ip(),
                'acknowledgement_user_agent' => substr(
                    (string) $request->userAgent(),
                    0,
                    2000
                ),
            ])->save();

            $this->pdfService->generateAcknowledged($lockedLetter);
        });

        return back()->with(
            'success',
            'Thank you. Your Letter of Intent acknowledgement has been recorded.'
        );
    }

    public function decline(
        DeclineCandidateIntentLetterRequest $request,
        string $token
    ): RedirectResponse {
        $letter = $this->resolveLetter($token);

        if (!in_array($letter->status, [
            CandidateIntentLetter::STATUS_SENT,
            CandidateIntentLetter::STATUS_VIEWED,
        ], true)) {
            return back()->with(
                'error',
                'This Letter of Intent can no longer be declined.'
            );
        }

        DB::transaction(function () use ($letter, $request): void {
            $lockedLetter = CandidateIntentLetter::query()
                ->lockForUpdate()
                ->findOrFail($letter->id);

            if (!in_array($lockedLetter->status, [
                CandidateIntentLetter::STATUS_SENT,
                CandidateIntentLetter::STATUS_VIEWED,
            ], true)) {
                abort(409, 'This Letter of Intent has already received a response.');
            }

            $lockedLetter->forceFill([
                'status' => CandidateIntentLetter::STATUS_DECLINED,
                'rejection_name' => $request->validated('candidate_name'),
                'rejection_remark' => trim($request->validated('reason')),
                'rejected_at' => now(),
                'rejection_ip' => $request->ip(),
                'rejection_user_agent' => substr(
                    (string) $request->userAgent(),
                    0,
                    2000
                ),
            ])->save();
        });

        return back()->with(
            'success',
            'Your decision and reason have been sent to HR.'
        );
    }

    public function download(string $token): StreamedResponse
    {
        $letter = $this->resolveLetter($token);
        $path = $letter->acknowledged_pdf_path ?: $letter->pdf_path;

        abort_unless(
            $path && Storage::disk('local')->exists($path),
            404,
            'The Letter of Intent PDF is not available.'
        );

        $filename = $letter->letter_number
            . ($letter->acknowledged_pdf_path ? '-acknowledged' : '')
            . '.pdf';

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function resolveLetter(string $token): CandidateIntentLetter
    {
        $letter = $this->tokenService->findByPlainToken($token);

        abort_unless(
            $letter &&
            $letter->isPortalTokenValid() &&
            $letter->status !== CandidateIntentLetter::STATUS_CANCELLED,
            404
        );

        return $letter;
    }
}
