<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes (Laravel 12)
|--------------------------------------------------------------------------
| All API routes will have prefix: /api/v1/...
| Example: http://localhost:8000/api/v1/test
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API v1 is working!',
        'version' => 'v1'
    ]);
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {
    /*
|--------------------------------------------------------------------------
| USERS MODULE
|--------------------------------------------------------------------------
*/
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\UserController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\UserController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
    });

    /*
|--------------------------------------------------------------------------
| ITINERARY MODULE
|--------------------------------------------------------------------------
*/

    Route::prefix('itineraries')->group(function () {
        Route::get('/', [\App\Http\Controllers\ItineraryController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\ItineraryController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\ItineraryController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\ItineraryController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\ItineraryController::class, 'destroy']);
    });

    /*
|--------------------------------------------------------------------------
| OFFSET MODULE
|--------------------------------------------------------------------------
*/

    Route::prefix('offsets')->group(function () {
        Route::get('/', [\App\Http\Controllers\OffsetController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\OffsetController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\OffsetController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\OffsetController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\OffsetController::class, 'destroy']);
    });

    /*
|--------------------------------------------------------------------------
| VENDORS MODULE
|--------------------------------------------------------------------------
*/

    Route::prefix('vendors')->group(function () {
        Route::get('/', [\App\Http\Controllers\VendorController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\VendorController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\VendorController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\VendorController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\VendorController::class, 'destroy']);
    });

    /*
|--------------------------------------------------------------------------
| ADMIN LOGIN MODULE
|--------------------------------------------------------------------------
*/
});

Route::post('/admin/login', [\App\Http\Controllers\AdminController::class, 'login']);
