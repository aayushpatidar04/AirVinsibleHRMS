<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficeLocation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'max_accuracy_meters' => 'integer',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Locations an employee may punch from: their client's, their branch's, or company-wide. */
    public function scopeAvailableTo($query, User $user)
    {
        return $query->active()->where(function ($q) use ($user) {
            $q->where(fn ($w) => $w->whereNull('client_id')->whereNull('branch_id'));

            if ($user->client_id) {
                $q->orWhere('client_id', $user->client_id);
            }

            if ($user->branch_id) {
                $q->orWhere('branch_id', $user->branch_id);
            }
        });
    }
}
