<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['is_paid' => 'boolean', 'is_active' => 'boolean'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Types that apply to a client: company-wide ones plus the client's own. */
    public function scopeForClient($query, ?int $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->whereNull('client_id');
            if ($clientId) {
                $q->orWhere('client_id', $clientId);
            }
        });
    }
}
