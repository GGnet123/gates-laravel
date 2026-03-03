<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_gate_id')
                ->constrained('gates')
                ->onDelete('cascade');

            $table->foreignId('address_id')
                ->constrained('addresses')
                ->onDelete('cascade');

            $table->unsignedBigInteger('block_number');
            $table->char('block_hash', 66);
            $table->char('parent_hash', 66);
            $table->char('tx_hash', 66);
            $table->integer('log_index')->default(-1);
            $table->string('amount_base_units');

            $table->enum('status', ['PENDING', 'CONFIRMED', 'REORGED'])->default('PENDING');

            $table->timestamps();

            $table->unique(['asset_gate_id', 'tx_hash', 'log_index'], 'deposits_uniqueness');

            $table->index('block_number');
            $table->index('tx_hash');
            $table->index('address_id');
            $table->index(['asset_gate_id', 'status'], 'deposits_gate_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
