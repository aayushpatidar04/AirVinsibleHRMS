<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'earnings' => 'array',
        'deductions' => 'array',
        'employer' => 'array',
        'attendance_summary' => 'array',
        'exceptions' => 'array',
        'payable_days' => 'float',
        'lop_days' => 'float',
        'present_days' => 'float',
        'absent_days' => 'float',
        'paid_leave_days' => 'float',
        'unpaid_leave_days' => 'float',
        'weekly_off_days' => 'float',
        'holiday_days' => 'float',
        'gross_earnings' => 'float',
        'total_deductions' => 'float',
        'net_pay' => 'float',
        'employer_contributions' => 'float',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'structure_id');
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function hasBlockingExceptions(): bool
    {
        return collect($this->exceptions ?? [])->contains(fn ($e) => ($e['blocking'] ?? false) === true);
    }
}
