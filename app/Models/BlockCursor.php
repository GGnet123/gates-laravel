<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockCursor extends Model
{
    protected $fillable = [
        'gate_id',
        'last_block_number',
        'last_block_hash',
    ];

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }
}
