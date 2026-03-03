<?php

namespace App\Jobs;

use App\Models\BlockCursor;
use App\Models\Deposit;
use App\Models\Gate;
use App\Services\RpcClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class IndexBlocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(public Gate $gate) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->gate->id))->dontRelease()];
    }

    public function handle(): void
    {
        $rpc = new RpcClient($this->gate->getRpcUrl());
        $latest = $rpc->blockNumber();

        $cursor = BlockCursor::firstOrCreate(
            ['gate_id' => $this->gate->id],
            ['last_block_number' => $latest - 1, 'last_block_hash' => '0x'.str_repeat('0', 64)]
        );

        $assetGates = Gate::where(function ($q) {
            $q->where('id', $this->gate->id)
                ->orWhere('parent_gate_id', $this->gate->id);
        })->get()->keyBy('id');

        $from = $cursor->last_block_number + 1;
        $to = min($from + 99, $latest);

        if ($from > $latest) {
            return;
        }

        $addressMap = $this->gate->addresses()
            ->pluck('id', 'address')
            ->mapWithKeys(fn ($id, $addr) => [strtolower($addr) => $id])
            ->all();

        $erc20Gates = $assetGates->filter(fn ($g) => $g->asset_type === 'ERC20');
        $nativeGate = $assetGates->first(fn ($g) => $g->asset_type === 'NATIVE');

        $lastBlockNumber = $cursor->last_block_number;
        $lastBlockHash = $cursor->last_block_hash;

        for ($blockNum = $from; $blockNum <= $to; $blockNum++) {
            $block = $rpc->getBlockByNumber($blockNum);

            if (!$block) {
                break;
            }

            if ($lastBlockNumber > 0 && $block['parentHash'] !== $lastBlockHash) {
                Deposit::where('block_number', '>=', $blockNum)
                    ->whereIn('asset_gate_id', $assetGates->keys())
                    ->update(['status' => Deposit::DEPOSIT_STATUS_REORGED]);

                $cursor->update([
                    'last_block_number' => $blockNum - 1,
                    'last_block_hash' => $block['parentHash'],
                ]);

                return;
            }

            if ($nativeGate) {
                $nativeDeposits = [];
                foreach ($block['transactions'] ?? [] as $tx) {
                    $to_addr = strtolower($tx['to'] ?? '');
                    if (!isset($addressMap[$to_addr])) {
                        continue;
                    }
                    $nativeDeposits[] = [
                        'asset_gate_id' => $nativeGate->id,
                        'address_id' => $addressMap[$to_addr],
                        'block_number' => hexdec($block['number']),
                        'block_hash' => $block['hash'],
                        'parent_hash' => $block['parentHash'],
                        'tx_hash' => $tx['hash'],
                        'log_index' => -1,
                        'amount_base_units' => gmp_strval(gmp_init($tx['value'], 16), 10),
                        'status' => Deposit::DEPOSIT_STATUS_PENDING,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if ($nativeDeposits) {
                    DB::table('deposits')->upsert(
                        $nativeDeposits,
                        ['asset_gate_id', 'tx_hash', 'log_index'],
                        ['block_number', 'block_hash', 'parent_hash', 'amount_base_units', 'status', 'address_id', 'updated_at']
                    );
                }
            }

            $lastBlockNumber = hexdec($block['number']);
            $lastBlockHash = $block['hash'];
        }

        Deposit::whereIn('asset_gate_id', $assetGates->keys())
            ->where('status', Deposit::DEPOSIT_STATUS_PENDING)
            ->where('block_number', '<=', $latest - $this->gate->confirmations_required)
            ->update(['status' => Deposit::DEPOSIT_STATUS_CONFIRMED]);

        foreach ($erc20Gates as $erc20Gate) {
            $logs = $rpc->getLogs($erc20Gate->token_contract, $from, $to);
            $erc20Deposits = [];

            foreach ($logs as $log) {
                if (count($log['topics']) < 3) {
                    continue;
                }
                $toAddr = strtolower('0x'.substr($log['topics'][2], 26));
                if (!isset($addressMap[$toAddr])) {
                    continue;
                }
                $erc20Deposits[] = [
                    'asset_gate_id' => $erc20Gate->id,
                    'address_id' => $addressMap[$toAddr],
                    'block_number' => hexdec($log['blockNumber']),
                    'block_hash' => $log['blockHash'],
                    'parent_hash' => $log['blockHash'],
                    'tx_hash' => $log['transactionHash'],
                    'log_index' => hexdec($log['logIndex']),
                    'amount_base_units' => gmp_strval(gmp_init(ltrim($log['data'], '0x') ?: '0', 16), 10),
                    'status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($erc20Deposits) {
                DB::table('deposits')->upsert(
                    $erc20Deposits,
                    ['asset_gate_id', 'tx_hash', 'log_index'],
                    ['block_number', 'block_hash', 'parent_hash', 'amount_base_units', 'status', 'address_id', 'updated_at']
                );
            }
        }

        if ($lastBlockNumber > $cursor->last_block_number) {
            $cursor->update([
                'last_block_number' => $lastBlockNumber,
                'last_block_hash' => $lastBlockHash,
            ]);
        }

    }
}
