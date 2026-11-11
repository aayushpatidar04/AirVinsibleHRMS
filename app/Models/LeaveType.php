<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'code',
        'description',
        'is_paid',
        'allow_half_day',
        'allow_during_probation',
        'annual_entitlement',
        'monthly_entitlement',
        'accrual_frequency',
        'credit_date',
        'max_consecutive_days',
        'notice_period_days',
        'allow_carry_forward',
        'max_carry_forward_days',
        'allow_encashment',
        'allow_negative_balance',
        'expiry_days',
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'allow_half_day' => 'boolean',
        'allow_during_probation' => 'boolean',
        'annual_entitlement' => 'integer',
        'monthly_entitlement' => 'integer',
        'accrual_frequency' => 'string',
        'credit_date' => 'date',
        'max_consecutive_days' => 'integer',
        'notice_period_days' => 'integer',
        'allow_carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'allow_encashment' => 'boolean',
        'allow_negative_balance' => 'boolean',
        'expiry_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class)->whereNull('deleted_at');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class)->whereNull('deleted_at');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
