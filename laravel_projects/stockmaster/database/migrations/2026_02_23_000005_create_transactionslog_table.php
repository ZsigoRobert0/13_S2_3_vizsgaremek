<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactionslog', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->integer('UserID')->nullable();
            $table->string('Type', 20)->nullable();
            $table->decimal('Amount', 10, 2)->nullable();
            $table->dateTime('TransactionTime')->nullable();
            $table->text('Description')->nullable();

            $table->index('UserID');

            $table->foreign('UserID', 'transactionslog_ibfk_1')
                ->references('ID')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactionslog');
    }
};