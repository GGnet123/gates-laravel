<?php

namespace App\Console\Commands;

use App\Jobs\IndexBlocksJob;
use App\Models\Gate;
use Illuminate\Console\Command;

class IndexBlocksCommand extends Command
{
    protected $signature = 'blockchain:index {--base_gate= : Base gate name}';

    protected $description = 'Trigger block indexing for a base gate';

    public function handle(): int
    {
        $gate = Gate::where('name', $this->option('base_gate'))->firstOrFail();

        IndexBlocksJob::dispatch($gate);

        $this->line("Dispatched indexing job for gate: {$gate->name}");

        return self::SUCCESS;
    }
}