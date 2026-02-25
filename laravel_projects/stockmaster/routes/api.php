<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\TickController;
use App\Http\Controllers\Api\CandleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\AssetsController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\PositionsController;
use App\Http\Controllers\Api\PricesController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\CalendarController;

Route::post('/tick/ingest', [TickController::class, 'ingest']);

Route::get('/candles', [CandleController::class, 'index']);
Route::get('/assets', [AssetsController::class, 'index']);
Route::get('/prices', [PricesController::class, 'index']);

Route::get('/settings', [SettingsController::class, 'show']);
Route::post('/settings', [SettingsController::class, 'update']);

Route::get('/state', [StateController::class, 'show']);

Route::post('/positions/open', [PositionsController::class, 'open']);
Route::post('/positions/close-by-asset', [PositionsController::class, 'closeByAsset']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/calendar', [CalendarController::class, 'index']);