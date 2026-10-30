<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DocumentUploadOtp extends Model
{
    protected $table = 'document_upload_otps';

    protected $fillable = [
        'email',
        'candidate_name',
        'otp',
        'expires_at',
        'used',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    const OTP_EXPIRY_MINUTES = 10;

    public static function generate(string $email, ?string $candidateName = null, ?string $ip = null): self
    {
        // Invalidate previous unused OTPs for this email
        self::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        return self::create([
            'email' => $email,
            'candidate_name' => $candidateName,
            'otp' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'ip_address' => $ip,
        ]);
    }

    public function isValid(string $otp): bool
    {
        if ($this->used) {
            return false;
        }

        if ($this->expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->otp, $otp);
    }

    public function markUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    public static function findValid(string $email, string $otp): ?self
    {
        $record = self::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record || !$record->isValid($otp)) {
            return null;
        }

        return $record;
    }
}
