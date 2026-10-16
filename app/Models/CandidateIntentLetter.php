<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateIntentLetter extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'candidate_id',
        'letter_number',
        'status',
        'position_title',
        'work_location',
        'monthly_ctc',
        'monthly_in_hand',
        'joining_date',
        'letter_date',
        'pdf_path',
        'acknowledged_pdf_path',
        'sent_to_email',
        'sent_at',
        'viewed_at',
        'sent_by',
        'send_count',
        'portal_token_hash',
        'portal_token_expires_at',
        'acknowledgement_name',
        'acknowledgement_text',
        'acknowledged_at',
        'acknowledgement_ip',
        'acknowledgement_user_agent',
        'candidate_consent',
        'rejection_name',
        'rejection_remark',
        'rejected_at',
        'rejection_ip',
        'rejection_user_agent',
    ];

    protected $casts = [
        'monthly_ctc' => 'decimal:2',
        'monthly_in_hand' => 'decimal:2',
        'joining_date' => 'date',
        'letter_date' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'portal_token_expires_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'rejected_at' => 'datetime',
        'candidate_consent' => 'boolean',
        'send_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (CandidateIntentLetter $letter): void {
            if (!$letter->letter_number) {
                $letter->forceFill([
                    'letter_number' => sprintf(
                        'LOI-%s-%05d',
                        $letter->created_at->format('Y'),
                        $letter->id
                    ),
                ])->save();
            }
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED
            && $this->acknowledged_at !== null
            && $this->candidate_consent;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED
            && $this->rejected_at !== null
            && filled($this->rejection_remark);
    }

    public static function acknowledgementText(string $candidateName): string
    {
        return sprintf(
            'I, %s, acknowledge receipt of this Letter of Intent, confirm that I have read and understood its terms and employee declaration, and agree to comply with the applicable rules, policies, and requirements of %s. I understand that this Letter of Intent is preliminary and is not the final employment offer.',
            $candidateName,
            config('company.legal_name', 'Airvincible Pvt. Ltd.')
        );
    }

    public function isPortalTokenValid(): bool
    {
        return $this->portal_token_hash !== null
            && $this->portal_token_expires_at?->isFuture();
    }

}
