<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    const DEPOSIT_STATUS_PENDING = 'PENDING';
    const DEPOSIT_STATUS_CONFIRMED = 'CONFIRMED';
    const DEPOSIT_STATUS_REORGED = 'REORGED';
    protected $fillable = [
        'asset_gate_id',
        'address_id',
        'block_number',
        'block_hash',
        'parent_hash',
        'tx_hash',
        'log_index',
        'amount_base_units',
        'status',
    ];

    public function assetGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'asset_gate_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
