<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement();

            $table->string('Username', 255);
            $table->string('Email', 255);
            $table->string('PasswordHash', 255);

            $table->timestamp('RegistrationDate')->useCurrent();

            $table->boolean('IsLoggedIn')->default(false);
            $table->string('PreferredTheme', 20)->default('dark');
            $table->boolean('NotificationsEnabled')->default(true);

            $table->decimal('DemoBalance', 15, 2)->default(10000.00);
            $table->decimal('RealBalance', 15, 2)->default(0.00);

            $table->string('PreferredCurrency', 3)->default('USD');

            // Dumpban nincs unique, de ha akarod, később felvehetjük (pl. Email).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};