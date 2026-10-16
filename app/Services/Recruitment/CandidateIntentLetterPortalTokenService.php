<?php

namespace App\Services\Recruitment;

use App\Models\CandidateIntentLetter;
use Illuminate\Support\Str;

class CandidateIntentLetterPortalTokenService
{
    public function issue(CandidateIntentLetter $letter): string
    {
        $plainToken = Str::random(96);

        $letter->forceFill([
            'portal_token_hash' => hash('sha256', $plainToken),
            'portal_token_expires_at' => now()->addDays(
                (int) config('recruitment.intent_letter_portal_expiry_days', 30)
            ),
        ])->save();

        return $plainToken;
    }

    public function findByPlainToken(string $plainToken): ?CandidateIntentLetter
    {
        if (strlen($plainToken) < 40 || strlen($plainToken) > 150) {
            return null;
        }

        return CandidateIntentLetter::query()
            ->where('portal_token_hash', hash('sha256', $plainToken))
            ->first();
    }
}
