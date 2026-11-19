<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['acted_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
