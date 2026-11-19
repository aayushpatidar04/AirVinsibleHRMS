<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Raw punch events. Rows are never edited or deleted; a wrong punch is
 * voided (with a reason) and, if needed, superseded by a manual punch that
 * points at the approved correction.
 */
class AttendancePunch extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_BREAK_START = 'break_start';
    public const TYPE_BREAK_END = 'break_end';

    public const SOURCE_PORTAL = 'portal';
    public const SOURCE_MACHINE = 'machine';
    public const SOURCE_AGENT = 'agent';
    public const SOURCE_MANUAL = 'manual';

    protected $guarded = ['id'];

    protected $casts = [
        'attendance_date' => 'date',
        'punched_at' => 'datetime',
        'voided_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_meters' => 'float',
        'distance_meters' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid($query)
    {
        return $query->whereNull('voided_at');
    }
}
