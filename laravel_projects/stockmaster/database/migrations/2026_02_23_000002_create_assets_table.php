<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->string('Symbol', 32);
            $table->string('Name', 255);
            $table->boolean('IsTradable')->default(true);

            $table->unique('Symbol', 'uq_symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};