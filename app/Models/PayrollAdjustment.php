<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    public const TYPES = [
        'incentive' => 'Incentive',
        'bonus' => 'Bonus',
        'commission' => 'Commission',
        'reimbursement' => 'Reimbursement',
        'allowance' => 'Allowance',
        'arrears' => 'Arrears',
        'deduction' => 'Deduction',
    ];

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'float', 'is_deduction' => 'boolean', 'voided_at' => 'datetime'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }
}
