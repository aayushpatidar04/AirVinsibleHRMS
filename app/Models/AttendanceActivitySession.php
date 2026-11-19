<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceActivitySession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'attendance_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
