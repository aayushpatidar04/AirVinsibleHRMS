<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attendance_cutoff_date' => 'date',
        'salary_processing_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function days(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
