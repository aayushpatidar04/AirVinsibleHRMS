<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'payroll_period_id',
        'run_number',
        'pay_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'employee_count',
        'calculated_by',
        'calculated_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'published_by',
        'published_at',
        'notes',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'status' => 'string',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'employee_count' => 'integer',
        'calculated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAYSLIPS_PUBLISHED = 'payslips_published';
    public const STATUS_CLOSED = 'closed';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(PayrollCalculation::class)->whereNull('deleted_at');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class)->whereNull('deleted_at');
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
