<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tutorialprogress', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->integer('UserID')->nullable();
            $table->integer('TutorialID')->nullable();

            // SQL dump: bit(1) DEFAULT NULL
            $table->boolean('IsCompleted')->nullable();

            $table->dateTime('StartedAt')->nullable();
            $table->dateTime('CompletedAt')->nullable();

            $table->index('UserID');
            $table->index('TutorialID');

            $table->foreign('UserID', 'tutorialprogress_ibfk_1')
                ->references('ID')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('TutorialID', 'tutorialprogress_ibfk_2')
                ->references('ID')->on('tutorials')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorialprogress');
    }
};