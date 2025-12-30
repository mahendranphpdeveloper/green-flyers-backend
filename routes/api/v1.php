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

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register']);

// Google OAuth login endpoint for exchanging Google accessToken/profile for user login/registration
Route::post('/auth/google-login', [\App\Http\Controllers\AuthController::class, 'googleLogin']);


Route::middleware('auth:sanctum')->group(function () {

    
 /*
    |--------------------------------------------------------------------------
    | USERS MODULE
    |--------------------------------------------------------------------------
    */
    // Change from 'profile' to '/profile' to ensure correct route registration
    Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile']);

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
    // Get itineraries belonging to the authenticated user
    Route::get('/', [\App\Http\Controllers\ItineraryController::class, 'index']);
    Route::post('/store/', [\App\Http\Controllers\ItineraryController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\ItineraryController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\ItineraryController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ItineraryController::class, 'destroy']);
});



//SingleItinerary 
Route::prefix('singleItinerary')->group(function () {
    Route::get('/', [\App\Http\Controllers\SingleItineraryController::class, 'index']);
    Route::post('/store', [\App\Http\Controllers\SingleItineraryController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'show']);
    Route::get('/{userId}/itinerary/{ItineraryId}', [\App\Http\Controllers\SingleItineraryController::class, 'getByUserAndItinerary']);
    Route::put('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'destroy']);
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

//admin to check the old password & update the password
Route::post('/admin/password', [\App\Http\Controllers\AdminController::class, 'verifyOldPassword']);
Route::post('/admin/passwordChange', [\App\Http\Controllers\AdminController::class, 'NewPasswordChange']);
      
});

 /*
|--------------------------------------------------------------------------
| ADMIN LOGIN MODULE
|--------------------------------------------------------------------------
*/
Route::post('/admin/login', [\App\Http\Controllers\AdminController::class, 'adminLogin']);




//email controller
Route::post('/email', [\App\Http\Controllers\EmailController::class, 'send']);





