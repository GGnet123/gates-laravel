<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_gate_id')
                ->constrained('gates')
                ->onDelete('cascade');

            $table->char('to_address', 42);
            $table->string('amount');
            $table->string('amount_base_units');

            $table->enum('status', ['CREATED', 'BROADCASTED', 'FAILED'])->default('CREATED');

            $table->text('signed_tx')->nullable();
            $table->char('tx_hash', 66)->nullable()->unique();

            $table->timestamps();

            $table->index(['asset_gate_id', 'status'], 'withdrawals_gate_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
