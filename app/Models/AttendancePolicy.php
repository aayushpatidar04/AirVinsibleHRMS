<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'description',
        'shift_start_time',
        'shift_end_time',
        'grace_period_start',
        'grace_period_end',
        'grace_period_minutes',
        'max_late_arrivals',
        'allow_early_departure',
        'min_required_hours',
        'min_session_duration_minutes',
        'mandatory_portal_punch',
        'mandatory_machine_punch',
        'break_duration_minutes',
        'max_break_minutes',
        'track_idle_time',
        'overtime_eligible',
        'overtime_multiplier',
        'weekly_offs',
        'public_holidays',
        'half_day_threshold_hours',
        'full_day_threshold_hours',
        'allow_negative_leave_balance',
        'deduct_late_arrival',
        'deduct_early_departure',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'max_late_arrivals' => 'integer',
        'allow_early_departure' => 'boolean',
        'min_required_hours' => 'integer',
        'min_session_duration_minutes' => 'integer',
        'mandatory_portal_punch' => 'boolean',
        'mandatory_machine_punch' => 'boolean',
        'break_duration_minutes' => 'integer',
        'max_break_minutes' => 'integer',
        'track_idle_time' => 'boolean',
        'overtime_eligible' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'weekly_offs' => 'array',
        'public_holidays' => 'array',
        'half_day_threshold_hours' => 'decimal:2',
        'full_day_threshold_hours' => 'decimal:2',
        'allow_negative_leave_balance' => 'boolean',
        'deduct_late_arrival' => 'boolean',
        'deduct_early_departure' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class)->whereNull('deleted_at');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
