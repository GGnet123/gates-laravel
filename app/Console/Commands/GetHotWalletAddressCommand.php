<?php

namespace App\Console\Commands;

use App\Services\WalletClient;
use Illuminate\Console\Command;

class GetHotWalletAddressCommand extends Command
{
    protected $signature = 'wallet:hot-address';

    protected $description = 'Get the hot wallet address used for withdrawals';

    public function handle(WalletClient $wallet): int
    {
        $address = $wallet->createAddress(
            'ethereum',
            config('services.wallet.hot_account'),
            config('services.wallet.hot_change'),
            config('services.wallet.hot_index'),
        );

        $this->line($address);

        return self::SUCCESS;
    }
}