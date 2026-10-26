<?php

namespace App\Services\Recruitment;

use App\Mail\CandidateIntentLetterMail;
use App\Models\CandidateIntentLetter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CandidateIntentLetterDeliveryService
{
    public function send(
        CandidateIntentLetter $letter,
        int $senderId,
        CandidateIntentLetterPdfService $pdfService,
        CandidateIntentLetterPortalTokenService $tokenService
    ): void {
        $letter->loadMissing('candidate');

        if (!$letter->candidate?->email) {
            throw new RuntimeException(
                'The candidate does not have an email address.'
            );
        }

        if (
            !$letter->pdf_path ||
            !Storage::disk('local')->exists($letter->pdf_path)
        ) {
            $pdfService->generate($letter);
        }

        $token = $tokenService->issue($letter);
        $portalUrl = route('candidate-intent-letters.portal.show', $token);

        Mail::to($letter->candidate->email)->send(
            new CandidateIntentLetterMail($letter, $portalUrl)
        );

        $letter->forceFill([
            'status' => CandidateIntentLetter::STATUS_SENT,
            'sent_to_email' => $letter->candidate->email,
            'sent_at' => now(),
            'sent_by' => $senderId,
            'send_count' => $letter->send_count + 1,
        ])->save();
    }
}
