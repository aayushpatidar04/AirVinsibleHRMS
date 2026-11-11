<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'client_id')->whereNull('deleted_at');
    }

    public function attendancePolicies(): HasMany
    {
        return $this->hasMany(AttendancePolicy::class)->whereNull('deleted_at');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class)->whereNull('deleted_at');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->whereNull('deleted_at');
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class)->whereNull('deleted_at');
    }

    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class)->whereNull('deleted_at');
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class)->whereNull('deleted_at');
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class)->whereNull('deleted_at');
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(PayrollComponent::class)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
