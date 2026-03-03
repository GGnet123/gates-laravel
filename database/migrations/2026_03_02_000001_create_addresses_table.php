<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gate_id')
                ->constrained('gates')
                ->onDelete('cascade');

            $table->unsignedInteger('account');
            $table->unsignedTinyInteger('change');
            $table->unsignedInteger('address_index');

            $table->char('address', 42)->unique();

            $table->timestamps();

            $table->unique(
                ['gate_id', 'account', 'change', 'address_index'],
                'addresses_hd_path_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
