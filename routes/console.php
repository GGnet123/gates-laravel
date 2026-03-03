<?php

use App\Jobs\IndexBlocksJob;
use App\Models\Gate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Gate::whereNull('parent_gate_id')->each(function (Gate $gate) {
        IndexBlocksJob::dispatch($gate);
    });
})->everyMinute();
