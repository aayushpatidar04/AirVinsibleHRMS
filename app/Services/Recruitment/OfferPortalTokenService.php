<?php

namespace App\Services\Recruitment;

use App\Models\CandidateOffer;
use Illuminate\Support\Str;
use RuntimeException;

class OfferPortalTokenService
{
    public const TOKEN_BYTES = 48;

    public function issue(
        CandidateOffer $offer,
        bool $forceNew = false
    ): string {
        if (
            !$forceNew &&
            $offer->portal_token_hash &&
            $offer->portalTokenIsValid()
        ) {
            throw new RuntimeException(
                'The raw portal token is not recoverable. Generate a new token when a new URL is required.'
            );
        }

        $plainToken = Str::random(96);

        $offer->forceFill([
            'portal_token_hash' =>
                hash('sha256', $plainToken),

            'portal_token_created_at' =>
                now(),

            'portal_token_expires_at' =>
                $this->resolveExpiry($offer),

            'portal_token_revoked_at' =>
                null,
        ])->save();

        return $plainToken;
    }

    public function rotate(
        CandidateOffer $offer
    ): string {
        return $this->issue(
            $offer,
            forceNew: true
        );
    }

    public function findByPlainToken(
        string $plainToken
    ): ?CandidateOffer {
        if (
            strlen($plainToken) < 40 ||
            strlen($plainToken) > 150
        ) {
            return null;
        }

        return CandidateOffer::query()
            ->where(
                'portal_token_hash',
                hash('sha256', $plainToken)
            )
            ->first();
    }

    private function resolveExpiry(
        CandidateOffer $offer
    ): mixed {
        if ($offer->valid_till) {
            return $offer
                ->valid_till
                ->copy()
                ->endOfDay();
        }

        return now()->addDays(
            config(
                'recruitment.offer_portal.default_expiry_days',
                15
            )
        );
    }
}