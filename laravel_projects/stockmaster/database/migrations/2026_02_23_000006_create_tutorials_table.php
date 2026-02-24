<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tutorials', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->string('Title', 200)->nullable();
            $table->integer('DifficultyLevel')->nullable();
            $table->string('Tags', 200)->nullable();
            $table->text('Content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorials');
    }
};