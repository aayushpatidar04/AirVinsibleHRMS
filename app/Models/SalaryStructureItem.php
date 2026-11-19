<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'percentage' => 'float',
        'prorate' => 'boolean',
        'is_basic' => 'boolean',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'structure_id');
    }
}
