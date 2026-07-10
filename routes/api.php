<?php

use App\Http\Controllers\Api\V1\ClienteSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/clients/sync', [ClienteSyncController::class, 'sync']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
