<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentUploadOtp;
use Illuminate\Support\Facades\Mail;

class DocumentUploadOtpService
{
    public function sendOtp(string $email, ?string $ip = null): void
    {
        $candidate = Candidate::where('email', $email)->first();

        $otpRecord = DocumentUploadOtp::generate(
            $email,
            $candidate?->full_name,
            $ip
        );

        Mail::to($email)->send(
            new \App\Mail\DocumentUploadOtpMail($otpRecord->otp)
        );
    }

    public function verifyOtp(string $email, string $otp): bool
    {
        $record = DocumentUploadOtp::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record || !$record->isValid($otp)) {
            return false;
        }

        $record->markUsed();
        return true;
    }

    public function isVerified(string $email): bool
    {
        return DocumentUploadOtp::where('email', $email)
            ->where('used', true)
            ->where('used_at', '>', now()->subMinutes(15))
            ->exists();
    }
}
