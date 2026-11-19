<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    public const DRAFT = 'draft';
    public const CALCULATED = 'calculated';
    public const PENDING_REVIEW = 'pending_review';
    public const PENDING_APPROVAL = 'pending_approval';
    public const APPROVED = 'approved';
    public const PUBLISHED = 'payslips_published';
    public const CLOSED = 'closed';

    public const STATUSES = [
        self::DRAFT => 'Draft',
        self::CALCULATED => 'Calculated',
        self::PENDING_REVIEW => 'Pending review',
        self::PENDING_APPROVAL => 'Pending approval',
        self::APPROVED => 'Approved',
        self::PUBLISHED => 'Payslips published',
        self::CLOSED => 'Closed',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'settings_snapshot' => 'array',
        'calculated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
        'total_gross' => 'float',
        'total_deductions' => 'float',
        'total_net' => 'float',
        'total_employer_cost' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::CALCULATED, self::PENDING_REVIEW], true);
    }

    public function label(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
