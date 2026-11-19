<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LEAVE = 'leave';
    public const STATUS_WEEKLY_OFF = 'weekly_off';
    public const STATUS_HOLIDAY = 'holiday';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_HALF_DAY => 'Half day',
        self::STATUS_ABSENT => 'Absent',
        self::STATUS_LEAVE => 'On leave',
        self::STATUS_WEEKLY_OFF => 'Weekly off',
        self::STATUS_HOLIDAY => 'Holiday',
        self::STATUS_INCOMPLETE => 'Incomplete',
        self::STATUS_PENDING => 'Pending',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'attendance_date' => 'date',
        'first_in' => 'datetime',
        'last_out' => 'datetime',
        'computed_at' => 'datetime',
        'locked_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'policy_snapshot' => 'array',
        'shift_snapshot' => 'array',
        'flags' => 'array',
        'payable_fraction' => 'float',
        'override_fraction' => 'float',
        'is_late' => 'boolean',
        'is_early' => 'boolean',
        'overtime_approved' => 'boolean',
        'leave_is_paid' => 'boolean',
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

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class, 'user_id', 'user_id')
            ->whereColumn('attendance_punches.attendance_date', 'attendance_records.attendance_date');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }
}
