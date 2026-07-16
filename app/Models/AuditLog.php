<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'model_type',
        'model_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'created_at' => 'datetime',
    ];

    /* ─── Relations ─── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─── Static helper ─── */

    public static function record(string $event, Model $model, array $old = [], array $new = []): void
    {
        static::create([
            'user_id'    => Auth::id(),
            'model_type' => get_class($model),
            'model_id'   => $model->getKey(),
            'event'      => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}