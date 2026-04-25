<?php

use App\Http\Controllers\ShareController;

Route::get("/share/{id}", [ShareController::class, 'show']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);

// SPA Catch-all Route
Route::get('/{any}', function () {
    return view('app'); 
})->where('any', '^(?!api|share).*$');
