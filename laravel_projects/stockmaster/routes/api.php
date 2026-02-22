<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TickController;
use App\Http\Controllers\Api\CandleController;

Route::post('/tick/ingest', [TickController::class, 'ingest']);

// A controllerben index() van, nem get()
Route::get('/candles', [CandleController::class, 'index']);