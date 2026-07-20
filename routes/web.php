<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'clients/manager')->name('clients.manager');

Route::get('/manifest.webmanifest', function () {
    return Response::file(public_path('build/manifest.webmanifest'), [
        'Content-Type' => 'application/manifest+json',
    ]);
});
