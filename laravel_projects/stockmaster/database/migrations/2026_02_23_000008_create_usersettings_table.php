<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FONTOS: a legacy users táblában PK = ID (int)
            $table->integer('user_id');

            // alap
            $table->string('timezone', 64)->default('Europe/Budapest');

            // chart
            $table->string('chart_interval', 10)->default('1m'); // 1m/5m/15m/1h/1d
            $table->string('chart_theme', 20)->default('dark');
            $table->unsignedInteger('chart_limit_initial')->default(1500);
            $table->unsignedInteger('chart_backfill_chunk')->default(1500);

            // news + calendar
            $table->unsignedInteger('news_limit')->default(8);
            $table->unsignedInteger('news_per_symbol_limit')->default(3);
            $table->unsignedInteger('news_portfolio_total_limit')->default(20);
            $table->unsignedInteger('calendar_limit')->default(8);

            // értesítések / kényelmi
            $table->boolean('auto_login')->default(false);
            $table->boolean('receive_notifications')->default(true);

            // extra config
            $table->json('data')->nullable();

            $table->timestamps();

            // 1 user -> 1 settings sor
            $table->unique('user_id');

            // FK: users.ID (nem users.id!)
            $table->foreign('user_id', 'user_settings_user_id_foreign')
                ->references('ID')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};