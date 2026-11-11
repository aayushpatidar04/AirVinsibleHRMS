<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'code',
        'type',
        'calculation_method',
        'default_value',
        'percentage_base',
        'is_statutory',
        'is_active',
        'formula',
    ];

    protected $casts = [
        'type' => 'string',
        'calculation_method' => 'string',
        'default_value' => 'decimal:2',
        'percentage_base' => 'string',
        'is_statutory' => 'boolean',
        'is_active' => 'boolean',
        'formula' => 'string',
    ];

    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
