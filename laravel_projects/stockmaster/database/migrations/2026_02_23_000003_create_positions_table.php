<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->integer('UserID');
            $table->integer('AssetID');

            $table->dateTime('OpenTime');
            $table->dateTime('CloseTime')->nullable();

            $table->decimal('Quantity', 12, 2);
            $table->decimal('EntryPrice', 12, 4);
            $table->decimal('ExitPrice', 12, 4)->nullable();

            $table->enum('PositionType', ['buy', 'sell']);
            $table->boolean('IsOpen')->default(true);

            $table->decimal('ProfitLoss', 12, 2)->nullable();

            // Dumpban nincs FK positionsre, csak PK. Indexeket viszont érdemes:
            $table->index('UserID');
            $table->index('AssetID');
            $table->index('IsOpen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};