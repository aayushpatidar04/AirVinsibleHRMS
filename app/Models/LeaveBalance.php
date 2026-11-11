<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'credited_balance',
        'used_balance',
        'pending_balance',
        'carry_forward_balance',
        'encashed_balance',
        'expired_balance',
        'adjusted_balance',
    ];

    protected $casts = [
        'year' => 'integer',
        'opening_balance' => 'decimal:2',
        'credited_balance' => 'decimal:2',
        'used_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'carry_forward_balance' => 'decimal:2',
        'encashed_balance' => 'decimal:2',
        'expired_balance' => 'decimal:2',
        'adjusted_balance' => 'decimal:2',
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

    public function getAvailableBalanceAttribute(): float
    {
        return (float) ($this->opening_balance + $this->credited_balance + $this->carry_forward_balance + $this->adjusted_balance - $this->used_balance - $this->pending_balance - $this->encashed_balance - $this->expired_balance);
    }
}
