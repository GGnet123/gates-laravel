<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('gates')->updateOrInsert(
            ['name' => 'eth_sepolia'],
            [
                'rpc_url' => env('ETH_SEPOLIA_RPC_URL', 'https://rpc.sepolia.org'),
                'chain_id' => 11155111,
                'confirmations_required' => 12,
                'asset_type' => 'NATIVE',
                'token_contract' => null,
                'token_decimals' => 18,
                'parent_gate_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $ethSepoliaId = DB::table('gates')->where('name', 'eth_sepolia')->value('id');

        DB::table('gates')->updateOrInsert(
            ['name' => 'usdc_sepolia'],
            [
                'rpc_url' => null,
                'chain_id' => 11155111,
                'confirmations_required' => 12,
                'asset_type' => 'ERC20',
                'token_contract' => '0x1c7D4B196Cb0C7B01d743Fbc6116a902379C7238',
                'token_decimals' => 6,
                'parent_gate_id' => $ethSepoliaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
