<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricedata', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->integer('AssetID')->nullable();
            $table->dateTime('Timestamp')->nullable();

            $table->decimal('OpenPrice', 10, 4)->nullable();
            $table->decimal('HighPrice', 10, 4)->nullable();
            $table->decimal('LowPrice', 10, 4)->nullable();
            $table->decimal('ClosePrice', 10, 4)->nullable();

            $table->bigInteger('Volume')->nullable();

            $table->index('AssetID');

            $table->foreign('AssetID', 'pricedata_ibfk_1')
                ->references('ID')->on('assets')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricedata');
    }
};