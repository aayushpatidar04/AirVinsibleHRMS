<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Company-wide holidays plus those for the given client. */
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
