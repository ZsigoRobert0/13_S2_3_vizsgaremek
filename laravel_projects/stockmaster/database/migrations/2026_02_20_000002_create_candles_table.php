<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('symbol', 20);
            $table->string('tf', 5);              // "1m","5m","15m","1h","1d"
            $table->unsignedBigInteger('open_ts'); // candle open unix seconds (UTC)
            $table->unsignedBigInteger('close_ts'); // open_ts + tfSeconds - 1

            $table->decimal('open', 16, 6);
            $table->decimal('high', 16, 6);
            $table->decimal('low', 16, 6);
            $table->decimal('close', 16, 6);
            $table->unsignedBigInteger('ticks')->default(0);

            $table->timestamps();

            $table->unique(['symbol', 'tf', 'open_ts'], 'ux_candles_symbol_tf_open');
            $table->index(['symbol', 'tf', 'open_ts'], 'ix_candles_symbol_tf_open');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candles');
    }
};