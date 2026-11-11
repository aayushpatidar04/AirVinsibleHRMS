<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollCalculation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_run_id',
        'user_id',
        'client_id',
        'period_start',
        'period_end',
        'payable_days',
        'present_days',
        'absent_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'weekly_off_days',
        'holiday_days',
        'late_arrivals',
        'early_departures',
        'total_working_hours',
        'overtime_hours',
        'gross_earnings',
        'total_deductions',
        'net_pay',
        'earning_breakdown',
        'deduction_breakdown',
        'adjustments',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payable_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'paid_leave_days' => 'integer',
        'unpaid_leave_days' => 'integer',
        'weekly_off_days' => 'integer',
        'holiday_days' => 'integer',
        'late_arrivals' => 'integer',
        'early_departures' => 'integer',
        'total_working_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'earning_breakdown' => 'array',
        'deduction_breakdown' => 'array',
        'adjustments' => 'array',
        'status' => 'string',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }
}
