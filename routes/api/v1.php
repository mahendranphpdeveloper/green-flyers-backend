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
    Route::delete('/{userId}/{itineraryId}', [\App\Http\Controllers\ItineraryController::class, 'destroy']);
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
Route::put('/admin/passwordChange', [\App\Http\Controllers\AdminController::class, 'NewPasswordChange']);

Route::get('/admin/email-templates/offset-reminder', [\App\Http\Controllers\EmailController::class, 'getOffsetReminderTemplate']);
Route::get('/admin/email-templates/deletion-notification', [\App\Http\Controllers\EmailController::class, 'getDeletionNotificationTemplate']);
Route::put('/admin/email-templates/offset-reminder', [\App\Http\Controllers\EmailController::class, 'updateOffsetReminderTemplate']);
Route::put('/admin/email-templates/deletion-notification', [\App\Http\Controllers\EmailController::class, 'updateDeletionNotificationTemplate']);


//admin side manage homepage content
Route::prefix('admin/manage-home')->group(function () {
    //carousel
    Route::get('/carousel', [\App\Http\Controllers\HomeManageController::class, 'getHomeCarousel']);
    Route::post('/carousel', [\App\Http\Controllers\HomeManageController::class, 'addHomeCarousel']);
    Route::put('/carousel/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCarousel']);
    Route::delete('/carousel/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeCarousel']);

    //cards
    Route::get('/cards', [\App\Http\Controllers\HomeManageController::class, 'getHomeCards']);
    Route::post('/cards', [\App\Http\Controllers\HomeManageController::class, 'addHomeCards']);
    Route::put('/cards/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCards']);
    Route::delete('/cards/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeCards']);

   //call to action
   Route::get('/call-to-action1', [\App\Http\Controllers\HomeManageController::class, 'getHomeCallToAction1']);
   Route::put('/call-to-action1', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCallToAction1']);
   Route::get('/call-to-action2', [\App\Http\Controllers\HomeManageController::class, 'getHomeCallToAction2']);
   Route::put('/call-to-action2', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCallToAction2']);

   //FAQ
   Route::get('/faq', [\App\Http\Controllers\HomeManageController::class, 'getHomeFAQ']);
   Route::put('/faq/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeFAQ']);
   Route::delete('/faq/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeFAQ']);

   //visual section 
   Route::get('/visual-section', [\App\Http\Controllers\HomeManageController::class, 'getHomeVisualSection']);
   Route::put('/visual-section/', [\App\Http\Controllers\HomeManageController::class, 'updateHomeVisualSection']);
  
   
});

      
});

 /*
|--------------------------------------------------------------------------
| ADMIN LOGIN MODULE
|--------------------------------------------------------------------------
*/
Route::post('/admin/login', [\App\Http\Controllers\AdminController::class, 'adminLogin']);




//email controller
Route::post('/email', [\App\Http\Controllers\EmailController::class, 'send']);









