<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);

// Social Media Share & Redirection Route
Route::get('/share/{id}', [\App\Http\Controllers\SocialMetaTagController::class, 'share']);

// Temporary// Route to fix permissions
Route::get('/fix-permissions', function () {
    try {
        chmod(storage_path('app/public'), 0775);
        chmod(storage_path('app/public/shares'), 0775);
        return "Permissions updated successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

// Route to fix existing image paths in the database
Route::get('/fix-old-shares', function () {
    $shares = \App\Models\UserShare::all();
    $count = 0;
    foreach ($shares as $share) {
        if (strpos($share->image_path, 'app/public/') === false) {
            $share->image_path = str_replace('/storage/', '/storage/app/public/', $share->image_path);
            $share->save();
            $count++;
        }
    }
    return "Updated $count old share paths!";
});



// Temporary route to fix storage link
Route::get('/link-storage', function () {
    Artisan::call('storage:link');
    return "Storage link created successfully!";
});
