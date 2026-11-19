<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePolicy extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'allow_during_probation' => 'boolean',
        'allow_half_day' => 'boolean',
        'allow_negative' => 'boolean',
        'allow_encashment' => 'boolean',
        'count_weekly_offs' => 'boolean',
        'count_holidays' => 'boolean',
        'approval_levels' => 'array',
        'accrual_amount' => 'float',
        'annual_cap' => 'float',
        'max_consecutive_days' => 'float',
        'max_negative_days' => 'float',
        'carry_forward_cap' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveOn($query, CarbonInterface|string $date)
    {
        $date = is_string($date) ? $date : $date->toDateString();

        return $query->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
    }

    /** Approval chain; defaults to the reporting manager only. */
    public function levels(): array
    {
        $levels = array_values(array_filter($this->approval_levels ?? []));

        return $levels ?: ['reporting_manager'];
    }
}
