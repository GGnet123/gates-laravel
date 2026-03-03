<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WalletClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wallet.url'), '/');
    }

    public function createAddress(string $gate, int $account, int $change, int $addressIndex): string
    {
        $response = Http::post("{$this->baseUrl}/api/v1/createaddress", [
            'gate' => $gate,
            'account' => $account,
            'change' => $change,
            'address_index' => $addressIndex,
        ]);

        return $response->json('address');
    }

    public function validateAddress(string $gate, string $address): bool
    {
        $response = Http::post("{$this->baseUrl}/api/v1/validateaddress", [
            'gate' => $gate,
            'address' => $address,
        ]);

        return (bool) $response->json('valid');
    }

    public function signTx(string $gate, int $account, int $change, int $addressIndex, array $txParams): array
    {
        $response = Http::post("{$this->baseUrl}/api/v1/tx", [
            'gate' => $gate,
            'account' => $account,
            'change' => $change,
            'address_index' => $addressIndex,
            'tx_params' => $txParams,
        ]);

        return $response->json();
    }
}