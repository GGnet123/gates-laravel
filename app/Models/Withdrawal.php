<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    const STATUS_CREATED = 'CREATED';

    const STATUS_BROADCASTED = 'BROADCASTED';

    protected $fillable = [
        'asset_gate_id',
        'to_address',
        'amount',
        'amount_base_units',
        'status',
        'signed_tx',
        'tx_hash',
    ];

    public function assetGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'asset_gate_id');
    }
}
