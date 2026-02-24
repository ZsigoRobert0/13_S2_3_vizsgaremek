<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->integer('UserID')->nullable();
            $table->string('Title', 100)->nullable();
            $table->text('Message')->nullable();

            $table->dateTime('CreatedAt')->nullable();

            // SQL dump: bit(1) DEFAULT NULL
            $table->boolean('IsRead')->nullable();

            $table->index('UserID');

            $table->foreign('UserID', 'notifications_ibfk_1')
                ->references('ID')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};