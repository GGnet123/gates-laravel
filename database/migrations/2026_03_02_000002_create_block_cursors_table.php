<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_cursors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gate_id')
                ->unique()
                ->constrained('gates')
                ->onDelete('cascade');

            $table->unsignedBigInteger('last_block_number');
            $table->char('last_block_hash', 66);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_cursors');
    }
};
