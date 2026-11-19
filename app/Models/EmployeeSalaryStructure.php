<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryStructure extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'hourly_rate' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sourceOffer(): BelongsTo
    {
        return $this->belongsTo(CandidateOffer::class, 'source_offer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalaryStructureItem::class, 'structure_id')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeEffectiveOn($query, CarbonInterface|string $date)
    {
        $date = is_string($date) ? $date : $date->toDateString();

        return $query->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
    }

    public function monthlyGross(): float
    {
        return (float) $this->items->where('type', 'earning')->sum('amount');
    }
}
