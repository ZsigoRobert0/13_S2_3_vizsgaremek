<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_ticks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('symbol', 20)->index();
            $table->unsignedBigInteger('ts'); // unix seconds (UTC)
            $table->decimal('price', 16, 6);
            $table->decimal('bid', 16, 6)->nullable();
            $table->decimal('ask', 16, 6)->nullable();
            $table->string('source', 20)->default('finnhub');
            $table->timestamps();

            $table->unique(['symbol', 'ts'], 'ux_ticks_symbol_ts');
            $table->index(['symbol', 'ts'], 'ix_ticks_symbol_ts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_ticks');
    }
};