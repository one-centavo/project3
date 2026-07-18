<?php

use App\Http\Controllers\Api\V1\ClientIndexController;
use App\Http\Controllers\Api\V1\ClientSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/clients/sync', [ClientSyncController::class, 'sync']);
Route::get('/v1/clients/indices', [ClientIndexController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
