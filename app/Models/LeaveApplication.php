<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveApplication extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_half_day' => 'boolean',
        'total_days' => 'float',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class, 'policy_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('level');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function scopeOverlapping($query, string $start, string $end)
    {
        return $query->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }
}
