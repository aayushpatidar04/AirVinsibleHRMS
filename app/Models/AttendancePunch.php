<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'client_id',
        'punch_time',
        'punch_type',
        'source',
        'device_id',
        'geolocation',
        'ip_address',
        'notes',
        'is_manual_correction',
        'corrected_by',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'geolocation' => 'array',
        'is_manual_correction' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public const PUNCH_IN = 'in';
    public const PUNCH_OUT = 'out';
    public const BREAK_START = 'break_start';
    public const BREAK_END = 'break_end';
}
