<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $rpc_url
 * @property int $confirmations_required
 */
class Gate extends Model
{
    public function getRpcUrl(): string
    {
        if (!app()->isProduction()) {
            return 'https://rpc.sepolia.ethpandaops.io'; // free dev node
        }

        return $this->rpc_url;
    }

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'rpc_url',
        'chain_id',
        'confirmations_required',
        'asset_type',
        'token_contract',
        'token_decimals',
        'parent_gate_id',
    ];

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'parent_gate_id');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Gate::class, 'parent_gate_id');
    }

    /**
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * @return HasOne
     */
    public function blockCursor(): HasOne
    {
        return $this->hasOne(BlockCursor::class);
    }

    /**
     * @return HasMany
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'asset_gate_id');
    }
}
