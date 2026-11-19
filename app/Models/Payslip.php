<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'voided_at' => 'datetime',
        'gross_earnings' => 'float',
        'total_deductions' => 'float',
        'net_pay' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class, 'payroll_run_item_id');
    }

    /** Employees may only ever see published, non-voided payslips. */
    public function scopeVisibleToEmployee($query)
    {
        return $query->whereNotNull('published_at')->whereNull('voided_at');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->voided_at === null;
    }
}
