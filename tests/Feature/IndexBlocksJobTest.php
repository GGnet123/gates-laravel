<?php

namespace Tests\Feature;

use App\Jobs\IndexBlocksJob;
use App\Models\Address;
use App\Models\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexBlocksJobTest extends TestCase
{
    use RefreshDatabase;

    private const string RPC_URL = 'https://rpc.sepolia.ethpandaops.io';

    private const string WALLET_URL = 'http://127.0.0.1:8000';

    private const string OUR_ADDRESS = '0x' . 'a' . str_repeat('0', 39);

    private const string TX_HASH = '0x' . str_repeat('b', 64);

    private const string BLOCK_HASH = '0x' . str_repeat('c', 64);

    private const string GENESIS_HASH = '0x' . str_repeat('0', 64);

    public function test_indexes_native_deposit(): void
    {
        $gate = Gate::create([
            'name' => 'eth_sepolia',
            'rpc_url' => 'unused_in_non_prod',
            'chain_id' => 11155111,
            'confirmations_required' => 12,
            'asset_type' => 'NATIVE',
        ]);

        $address = Address::create([
            'gate_id' => $gate->id,
            'account' => 0,
            'change' => 0,
            'address_index' => 0,
            'address' => self::OUR_ADDRESS,
        ]);

        Http::fake([
            self::WALLET_URL . '/*' => Http::response(['address' => self::OUR_ADDRESS]),
            self::RPC_URL => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x1'])
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => [
                    'number' => '0x1',
                    'hash' => self::BLOCK_HASH,
                    'parentHash' => self::GENESIS_HASH,
                    'transactions' => [
                        [
                            'hash' => self::TX_HASH,
                            'to' => self::OUR_ADDRESS,
                            'value' => '0xde0b6b3a7640000',
                        ],
                    ],
                ]]),
        ]);

        (new IndexBlocksJob($gate))->handle();

        $this->assertDatabaseHas('deposits', [
            'asset_gate_id' => $gate->id,
            'address_id' => $address->id,
            'block_number' => 1,
            'block_hash' => self::BLOCK_HASH,
            'tx_hash' => self::TX_HASH,
            'log_index' => -1,
            'amount_base_units' => '1000000000000000000',
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('block_cursors', [
            'gate_id' => $gate->id,
            'last_block_number' => 1,
            'last_block_hash' => self::BLOCK_HASH,
        ]);
    }
}