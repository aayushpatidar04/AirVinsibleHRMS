<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
        'block_on_pending_attendance' => 'boolean',
        'block_on_pending_leave' => 'boolean',
        'separate_reviewer' => 'boolean',
        'pf_enabled' => 'boolean',
        'esic_enabled' => 'boolean',
        'pf_employee_rate' => 'float',
        'pf_employer_rate' => 'float',
        'pf_wage_ceiling' => 'float',
        'esic_employee_rate' => 'float',
        'esic_employer_rate' => 'float',
        'esic_gross_ceiling' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Settings in force for a client on a date, or defaults when none are stored. */
    public static function forClient(int $clientId, $date): self
    {
        $date = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

        return static::query()
            ->where('client_id', $clientId)
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->first()
            ?? new static([
                'client_id' => $clientId,
                'effective_from' => $date,
                'calculation_method' => 'monthly_fixed',
                'divisor_mode' => 'calendar_days',
                'fixed_divisor' => 30,
                'rounding' => 'nearest',
                'precision' => 0,
                'overtime_base' => 'gross',
                'block_on_pending_attendance' => true,
                'block_on_pending_leave' => true,
                'separate_reviewer' => false,
                'pf_enabled' => true,
                'pf_employee_rate' => 12,
                'pf_employer_rate' => 12,
                'pf_wage_ceiling' => null,
                'esic_enabled' => true,
                'esic_employee_rate' => 0.75,
                'esic_employer_rate' => 3.25,
                'esic_gross_ceiling' => 21000,
            ]);
    }
}
