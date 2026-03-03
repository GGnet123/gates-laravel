<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Gate;
use App\Services\WalletClient;
use Illuminate\Console\Command;

class CreateAddressCommand extends Command
{
    protected $signature = 'wallet:create-address
                            {gate : Base gate name}
                            {--account=0 : HD account index}
                            {--change=0 : HD change index}';

    protected $description = 'Generate a new address';

    public function handle(WalletClient $wallet): int
    {
        $gate = Gate::where('name', $this->argument('gate'))->firstOrFail();

        $account = (int)$this->option('account');
        $change = (int)$this->option('change');
        $addressIndex = ($gate->addresses()
            ->where('account', $account)
            ->where('change', $change)
            ->max('address_index') ?? -1) + 1;

        $address = $wallet->createAddress('ethereum', $account, $change, $addressIndex);

        Address::create([
            'gate_id' => $gate->id,
            'account' => $account,
            'change' => $change,
            'address_index' => $addressIndex,
            'address' => $address,
        ]);

        $this->line("m/44'/60'/{$account}'/{$change}/{$addressIndex}  →  {$address}");

        return self::SUCCESS;
    }
}
