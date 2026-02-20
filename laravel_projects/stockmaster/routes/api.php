<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TickController;
use App\Http\Controllers\Api\CandleController;

Route::post('/tick/ingest', [TickController::class, 'ingest']);
Route::get('/candles', [CandleController::class, 'get']);