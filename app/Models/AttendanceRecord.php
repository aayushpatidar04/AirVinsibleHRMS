<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'shift_id',
        'attendance_date',
        'scheduled_start',
        'scheduled_end',
        'first_punch_in',
        'last_punch_out',
        'total_working_hours',
        'system_session_hours',
        'break_duration',
        'overtime_hours',
        'status',
        'is_late',
        'late_minutes',
        'is_early_departure',
        'early_departure_minutes',
        'missing_punch',
        'punch_source',
        'geolocation',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'scheduled_start' => 'datetime:H:i',
        'scheduled_end' => 'datetime:H:i',
        'first_punch_in' => 'datetime:H:i',
        'last_punch_out' => 'datetime:H:i',
        'total_working_hours' => 'decimal:2',
        'system_session_hours' => 'decimal:2',
        'break_duration' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'is_early_departure' => 'boolean',
        'early_departure_minutes' => 'integer',
        'missing_punch' => 'boolean',
        'geolocation' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class)->orderBy('punch_time');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
