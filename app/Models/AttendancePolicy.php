<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendancePolicy extends Model
{
    use SoftDeletes;

    public const SOURCES = ['portal', 'machine', 'agent'];

    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'weekly_offs' => 'array',
        'source_priority' => 'array',
        'require_portal_punch' => 'boolean',
        'require_machine_punch' => 'boolean',
        'geofence_required' => 'boolean',
        'deduct_excess_break' => 'boolean',
        'track_idle_time' => 'boolean',
        'deduct_late' => 'boolean',
        'deduct_early' => 'boolean',
        'overtime_eligible' => 'boolean',
        'overtime_requires_approval' => 'boolean',
        'overtime_multiplier' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function isWeeklyOff(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, array_map('intval', $this->weekly_offs ?? []), true);
    }

    /** Frozen copy stored on attendance records and payroll runs. */
    public function snapshot(): array
    {
        return $this->only([
            'id', 'name', 'effective_from', 'effective_to', 'weekly_offs', 'full_day_minutes', 'half_day_minutes',
            'min_session_minutes', 'grace_minutes', 'early_departure_minutes', 'max_late_per_month',
            'lates_per_half_day', 'deduct_late', 'deduct_early', 'require_portal_punch', 'require_machine_punch',
            'source_priority', 'source_tolerance_minutes', 'geofence_required', 'paid_break_minutes',
            'max_break_minutes', 'deduct_excess_break', 'track_idle_time', 'overtime_eligible',
            'overtime_min_minutes', 'overtime_multiplier', 'overtime_requires_approval', 'missing_punch_treatment',
        ]);
    }
}
