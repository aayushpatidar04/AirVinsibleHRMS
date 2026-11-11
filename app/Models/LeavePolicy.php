<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'leave_type_id',
        'employee_category',
        'annual_entitlement',
        'monthly_entitlement',
        'accrual_frequency',
        'credit_date',
        'eligibility_service_days',
        'allow_carry_forward',
        'max_carry_forward_days',
        'allow_encashment',
        'max_consecutive_days',
        'notice_period_days',
        'allow_half_day',
        'allow_during_probation',
        'allow_negative_balance',
        'expiry_days',
        'approval_hierarchy',
        'count_weekly_offs',
        'count_public_holidays',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'annual_entitlement' => 'integer',
        'monthly_entitlement' => 'integer',
        'eligibility_service_days' => 'integer',
        'allow_carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'allow_encashment' => 'boolean',
        'max_consecutive_days' => 'integer',
        'notice_period_days' => 'integer',
        'allow_half_day' => 'boolean',
        'allow_during_probation' => 'boolean',
        'allow_negative_balance' => 'boolean',
        'expiry_days' => 'integer',
        'approval_hierarchy' => 'string',
        'count_weekly_offs' => 'boolean',
        'count_public_holidays' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
