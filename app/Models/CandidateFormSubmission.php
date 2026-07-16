<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateFormSubmission extends Model
{
    use HasFactory;

    protected $table = 'form_submissions';

    protected $fillable = [
        'candidate_id',
        'form_id',
        'qr_code_id',
        'submission_data',
        'resume_path',
        'resume_original_name',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected $casts = [
        'submission_data' => 'json',
        'submitted_at'    => 'datetime',
    ];

    /* ─── Relations ─── */

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'form_id');
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class, 'qr_code_id');
    }
}