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
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $event,
        Model $model,
        array $old = [],
        array $new = [],
        ?int $userId = null
    ): self {
        return static::create([
            'user_id' => $userId ?? Auth::id(),

            'model_type' => $model->getMorphClass(),

            'model_id' => $model->getKey(),

            'event' => $event,

            'old_values' => $old ?: null,

            'new_values' => $new ?: null,

            'ip_address' =>
                app()->runningInConsole()
                ? null
                : request()->ip(),

            'user_agent' =>
                app()->runningInConsole()
                ? null
                : request()->userAgent(),

            'created_at' => now(),
        ]);
    }
}