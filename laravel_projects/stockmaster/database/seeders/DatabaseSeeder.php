<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\TutorialSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
    $this->call([
        StockMasterSeeder::class,
        TutorialSeeder::class,
        ]);
    }
}