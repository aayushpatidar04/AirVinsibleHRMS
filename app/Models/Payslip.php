<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_calculation_id',
        'user_id',
        'client_id',
        'payslip_number',
        'period_start',
        'period_end',
        'payment_date',
        'gross_earnings',
        'total_deductions',
        'net_pay',
        'earning_breakdown',
        'deduction_breakdown',
        'pdf_path',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'earning_breakdown' => 'array',
        'deduction_breakdown' => 'array',
        'pdf_path' => 'string',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function payrollCalculation(): BelongsTo
    {
        return $this->belongsTo(PayrollCalculation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
