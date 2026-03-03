<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\Withdrawal;
use App\Services\RpcClient;
use App\Services\WalletClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function store(Request $request, WalletClient $wallet): JsonResponse
    {
        $request->validate([
            'asset_gate' => ['required', 'string', 'exists:gates,name'],
            'to_address' => ['required', 'string', 'regex:/^0x[0-9a-fA-F]{40}$/'],
            'amount' => ['required', 'string', 'numeric'],
        ]);

        $gate = Gate::where('name', $request->asset_gate)->firstOrFail();
        $baseGate = $gate->parent ?? $gate;

        if (!$wallet->validateAddress('ethereum', $request->to_address)) {
            return response()->json(['error' => 'Invalid to_address'], 422);
        }

        $decimals = $gate->asset_type === 'NATIVE' ? 18 : $gate->token_decimals;
        $amountBaseUnits = bcmul($request->amount, bcpow('10', (string)$decimals), 0);

        $withdrawal = Withdrawal::create([
            'asset_gate_id' => $gate->id,
            'to_address' => $request->to_address,
            'amount' => $request->amount,
            'amount_base_units' => $amountBaseUnits,
            'status' => Withdrawal::STATUS_CREATED,
        ]);

        $rpc = new RpcClient($baseGate->getRpcUrl());

        $hotAccount = config('services.wallet.hot_account');
        $hotChange = config('services.wallet.hot_change');
        $hotIndex = config('services.wallet.hot_index');

        $hotAddress = $wallet->createAddress('ethereum', $hotAccount, $hotChange, $hotIndex);
        $nonce = $rpc->getTransactionCount($hotAddress);
        [$maxFeePerGas, $maxPriorityFeePerGas] = $rpc->estimateFees();

        if ($gate->asset_type === 'NATIVE') {
            $txTo = $request->to_address;
            $valueWei = $amountBaseUnits;
            $data = '0x';
            $gasLimit = 21000;
        } else {
            $txTo = $gate->token_contract;
            $valueWei = '0';
            $data = $this->encodeTransferCalldata($request->to_address, $amountBaseUnits);
            $gasLimit = 90000;
        }

        $signed = $wallet->signTx('ethereum', $hotAccount, $hotChange, $hotIndex, [
            'to' => $txTo,
            'value_wei' => $valueWei,
            'data' => $data,
            'nonce' => $nonce,
            'chain_id' => (int)$gate->chain_id,
            'gas_limit' => $gasLimit,
            'max_fee_per_gas_wei' => $maxFeePerGas,
            'max_priority_fee_per_gas_wei' => $maxPriorityFeePerGas,
        ]);

        $rpc->sendRawTransaction($signed['signed_tx']);

        $withdrawal->update([
            'signed_tx' => $signed['signed_tx'],
            'tx_hash' => $signed['tx_hash'],
            'status' => Withdrawal::STATUS_BROADCASTED,
        ]);

        return response()->json([
            'id' => $withdrawal->id,
            'asset_gate' => $gate->name,
            'amount' => $withdrawal->amount,
            'amount_base_units' => $withdrawal->amount_base_units,
            'status' => $withdrawal->status,
            'tx_hash' => $withdrawal->tx_hash,
        ]);
    }

    private function encodeTransferCalldata(string $toAddress, string $amountBaseUnits): string
    {
        $paddedAddress = str_pad(ltrim($toAddress, '0x'), 64, '0', STR_PAD_LEFT);
        $amountHex = gmp_strval(gmp_init($amountBaseUnits, 10), 16);
        $paddedAmount = str_pad($amountHex, 64, '0', STR_PAD_LEFT);

        return '0xa9059cbb' . $paddedAddress . $paddedAmount;
    }
}
