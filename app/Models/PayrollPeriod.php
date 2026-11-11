<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'start_date',
        'end_date',
        'attendance_cutoff_date',
        'salary_processing_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attendance_cutoff_date' => 'date',
        'salary_processing_date' => 'date',
        'status' => 'string',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_LOCKED = 'locked';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class)->whereNull('deleted_at');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
