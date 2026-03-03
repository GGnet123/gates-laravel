<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RpcClient
{
    const string ERC20_TRANSFER_TOPIC = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

    private int $id = 1;

    public function __construct(private string $rpcUrl) {}

    public function blockNumber(): int
    {
        $result = $this->call('eth_blockNumber', []);

        return hexdec($result);
    }

    public function getBlockByNumber(int $block): array
    {
        return $this->call('eth_getBlockByNumber', ['0x'.dechex($block), true]);
    }

    public function getLogs(string $contract, int $from, int $to): array
    {
        return $this->call('eth_getLogs', [[
            'address' => $contract,
            'fromBlock' => '0x'.dechex($from),
            'toBlock' => '0x'.dechex($to),
            'topics' => [self::ERC20_TRANSFER_TOPIC],
        ]]) ?? [];
    }

    public function getTransactionCount(string $address): int
    {
        return hexdec($this->call('eth_getTransactionCount', [$address, 'pending']));
    }

    public function estimateFees(): array
    {
        $history = $this->call('eth_feeHistory', [1, 'latest', [50]]);
        $baseFee = gmp_init(end($history['baseFeePerGas']), 16);
        $priorityFee = gmp_init($history['reward'][0][0], 16);

        return [
            gmp_strval(gmp_add(gmp_mul($baseFee, gmp_init(2)), $priorityFee)),
            gmp_strval($priorityFee),
        ];
    }

    public function sendRawTransaction(string $signedTx): string
    {
        return $this->call('eth_sendRawTransaction', [$signedTx]);
    }

    private function call(string $method, array $params): mixed
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => $this->id++,
            'method' => $method,
            'params' => $params,
        ]);

        return $response->json('result');
    }
}
