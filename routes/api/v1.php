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

Route::get('/landing-content', [\App\Http\Controllers\HomeManageController::class, 'getLandingContent']);


/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
//Generate, Send and Verify OTP
Route::post('/auth/send-otp', [\App\Http\Controllers\AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [\App\Http\Controllers\AuthController::class, 'verifyOtp']);

// Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register']);

// Google OAuth login endpoint for exchanging Google accessToken/profile for user login/registration
Route::post('/auth/google-login', [\App\Http\Controllers\AuthController::class, 'googleLogin']);

// Facebook OAuth login 
Route::post('/auth/facebook-login', [\App\Http\Controllers\AuthController::class, 'facebookLogin']);
Route::post('/auth/linkedin-login', [\App\Http\Controllers\AuthController::class, 'linkedinLogin']);

// Route::middleware('auth:user')->get('/auth/me', function (Request $request) {
//     return response()->json([
//         'status' => true,
//         'user' => $request->user()
//     ]);
// });

Route::get('/auth/me', function (Request $request) {

    if (!$request->user()) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated'
        ], 200); // 👈 send 200 instead of 401
    }

    return response()->json([
        'status' => true,
        'user' => $request->user()
    ]);
});

Route::middleware('auth:user')->post('/auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);

Route::middleware('auth:user')->group(function () {


    /*
       |--------------------------------------------------------------------------
       | USERS MODULE
       |--------------------------------------------------------------------------
       */
    // Change from 'profile' to '/profile' to ensure correct route registration
    Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile']);

    Route::prefix('users')->group(function () {
        // Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
        Route::get('/emission-offset-chart/{id}', [\App\Http\Controllers\UserController::class, 'getEmissionOffsetChart']);
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\UserController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\UserController::class, 'update']);
        // Route::delete('/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
    });

    /*--------------------------------------------------------------------------
    | ITINERARY MODULE
    |--------------------------------------------------------------------------
    */

    Route::prefix('itineraries')->group(function () {
        // Get itineraries belonging to the authenticated user
        Route::get('/', [\App\Http\Controllers\ItineraryController::class, 'index']);
        Route::post('/store/', [\App\Http\Controllers\ItineraryController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\ItineraryController::class, 'update']);
    });



    //SingleItinerary 
    // Route::prefix('singleItinerary')->group(function () {

    //     // Route::post('/store', [\App\Http\Controllers\SingleItineraryController::class, 'store']);
    //     Route::get('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'show']);
    // });

    Route::get('/singleItinerary/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'show']);


    // tree offset value
    Route::put('/treeoffsetvalue', [\App\Http\Controllers\HomeManageController::class, 'updateTreeOffsetValue']);

    /*
   |--------------------------------------------------------------------------
   | VENDORS MODULE
   |--------------------------------------------------------------------------
   */

    Route::prefix('vendors')->group(function () {
        // Route::get('/', [\App\Http\Controllers\VendorController::class, 'index']);
        Route::get('/projects_contributed', [\App\Http\Controllers\VendorController::class, 'getProjectContributors']);


    });

});

/*
|--------------------------------------------------------------------------
| SHARED ROUTES (User & Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin,user'])->group(function () {
    Route::get('/singleItinerary/{userId}/itinerary/{ItineraryId}', [\App\Http\Controllers\SingleItineraryController::class, 'getByUserAndItinerary']);
});

// Admin Routes
Route::middleware(['auth:admin', 'admin'])->group(function () {
    Route::get('/admin/me', function (Request $request) {
        return response()->json([
            'status' => true,
            'admin' => $request->user()
        ]);
    });
    Route::post('/admin/logout', [\App\Http\Controllers\AdminController::class, 'logout']);

    // admin to check the old password & update the password
    Route::post('/admin/password', [\App\Http\Controllers\AdminController::class, 'verifyOldPassword']);
    Route::put('/admin/passwordChange', [\App\Http\Controllers\AdminController::class, 'NewPasswordChange']);

    Route::get('/admin/email-templates/offset-reminder', [\App\Http\Controllers\EmailController::class, 'getOffsetReminderTemplate']);
    Route::get('/admin/email-templates/deletion-notification', [\App\Http\Controllers\EmailController::class, 'getDeletionNotificationTemplate']);
    Route::put('/admin/email-templates/offset-reminder', [\App\Http\Controllers\EmailController::class, 'updateOffsetReminderTemplate']);
    Route::put('/admin/email-templates/deletion-notification', [\App\Http\Controllers\EmailController::class, 'updateDeletionNotificationTemplate']);

    // admin side manage homepage content
    Route::prefix('/admin/home-manage')->group(function () {
        Route::post('/carousel', [\App\Http\Controllers\HomeManageController::class, 'addHomeCarousel']);
        Route::put('/carousel/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCarousel']);
        Route::delete('/carousel/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeCarousel']);

        Route::post('/cards', [\App\Http\Controllers\HomeManageController::class, 'addHomeCards']);
        Route::put('/cards/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCards']);
        Route::delete('/cards/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeCards']);

        Route::put('/call-to-action1', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCallToAction1']);
        Route::put('/call-to-action2', [\App\Http\Controllers\HomeManageController::class, 'updateHomeCallToAction2']);

        Route::post('/faq', [\App\Http\Controllers\HomeManageController::class, 'storeHomeFAQ']);
        Route::put('/faq/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeFAQ']);
        Route::delete('/faq/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeFAQ']);

        Route::put('/faq/visual-section/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeVisualSection']);

        Route::put('/bgimage', [\App\Http\Controllers\HomeManageController::class, 'updateLoginBackgroundImage']);

        Route::post('/terms', [\App\Http\Controllers\HomeManageController::class, 'storeHomeTerms']);
        Route::put('/terms/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeTerms']);
        Route::delete('/terms/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomeTerms']);

        Route::post('/privacy-policy', [\App\Http\Controllers\HomeManageController::class, 'storeHomePrivacyPolicy']);
        Route::put('/privacy-policy/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomePrivacyPolicy']);
        Route::delete('/privacy-policy/{id}', [\App\Http\Controllers\HomeManageController::class, 'deleteHomePrivacyPolicy']);

        Route::put('/terms-privacy-top-content/{id}', [\App\Http\Controllers\HomeManageController::class, 'updateHomeTermsPolicyTopContent']);
    });

    // for bulk upload
    Route::post('/admin/vendors/bulk', [\App\Http\Controllers\VendorBulkController::class, 'bulkUpload']);

    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/stats', [App\Http\Controllers\AdminDashboardController::class, 'getAdminDashboardStats']);
        Route::get('/user-distribution-chart', [App\Http\Controllers\AdminDashboardController::class, 'getUserDistributionCharts']);
        Route::get('/total-users-chart', [App\Http\Controllers\AdminDashboardController::class, 'getMonthlyUsersChart']);
        Route::get('/project-types-chart', [App\Http\Controllers\AdminDashboardController::class, 'getProjectTypesChart']);
        Route::get('/total-trees-chart', [App\Http\Controllers\AdminDashboardController::class, 'getMonthlyTreesPlantedChart']);
        Route::get('/carbon-offset-progress-chart', [App\Http\Controllers\AdminDashboardController::class, 'getMonthlyCarbonOffsetChart']);
        Route::get('/emissions-offset-chart', [App\Http\Controllers\AdminDashboardController::class, 'getAdminEmissionOffsetChart']);
        Route::get('/pending-verfication-offsets', [App\Http\Controllers\AdminDashboardController::class, 'getPendingVerificationOffsets']);
    });


    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
        Route::delete('/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
    });

    Route::prefix('itineraries')->group(function () {
        Route::get('/{id}', [\App\Http\Controllers\ItineraryController::class, 'show']);
        Route::delete('/{userId}/{itineraryId}', [\App\Http\Controllers\ItineraryController::class, 'destroy']);
    });

    Route::prefix('singleItinerary')->group(function () {
        Route::get('/', [\App\Http\Controllers\SingleItineraryController::class, 'index']);
        // Route::get('/{userId}/itinerary/{ItineraryId}', [\App\Http\Controllers\SingleItineraryController::class, 'getByUserAndItinerary']);
        Route::put('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\SingleItineraryController::class, 'destroy']);
    });

    Route::prefix('vendors')->group(function () {
        Route::post('/', [\App\Http\Controllers\VendorController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\VendorController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\VendorController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\VendorController::class, 'destroy']);
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

Route::prefix('vendors')->group(function () {
      Route::get('/', [\App\Http\Controllers\VendorController::class, 'index']);
  });

Route::prefix('/admin/home-manage')->group(function () {
    Route::get('/carousel', [\App\Http\Controllers\HomeManageController::class, 'getHomeCarousel']);
    Route::get('/cards', [\App\Http\Controllers\HomeManageController::class, 'getHomeCards']);
    Route::get('/call-to-action1', [\App\Http\Controllers\HomeManageController::class, 'getHomeCallToAction1']);
    Route::get('/call-to-action2', [\App\Http\Controllers\HomeManageController::class, 'getHomeCallToAction2']);
    Route::get('/faq', [\App\Http\Controllers\HomeManageController::class, 'getHomeFAQ']);
    Route::get('/faq/visual-section', [\App\Http\Controllers\HomeManageController::class, 'getHomeVisualSection']);
    Route::get('/bgimage', [\App\Http\Controllers\HomeManageController::class, 'getLoginBackgroundImage']);
    Route::get('/terms', [\App\Http\Controllers\HomeManageController::class, 'getHomeTerms']);
    Route::get('/privacy-policy', [\App\Http\Controllers\HomeManageController::class, 'getHomePrivacyPolicy']);
    Route::get('/terms-privacy-top-content/{id}', [\App\Http\Controllers\HomeManageController::class, 'getHomeTermsPolicyTopContent']);


});

// admin notification reminder send to user
Route::prefix('admin/notification-reminder')->group(function () {
    Route::post('/store', [\App\Http\Controllers\AdminNotificationController::class, 'store']);
    Route::get('/{userId}', [\App\Http\Controllers\AdminNotificationController::class, 'getUserNotifications']);
    Route::get('/', [\App\Http\Controllers\AdminNotificationController::class, 'getNotificationRemainders']);
    Route::put('/', [\App\Http\Controllers\AdminNotificationController::class, 'updateNotificationRemainders']);
});

Route::get('/admin/certificate/download/{path}', [\App\Http\Controllers\AdminCertificateController::class, 'download'])->where('path', '.*');
Route::get('/admin/certificate/view/{fileName}', [\App\Http\Controllers\CertificateController::class, 'view']);

// tree offset value
Route::get('/treeoffsetvalue', [\App\Http\Controllers\HomeManageController::class, 'getTreeOffsetValue']);

// Mark notification as read
Route::put('/users/notifications/{id}', [\App\Http\Controllers\AdminNotificationController::class, 'markAsRead']);

Route::post('singleItinerary/store', [\App\Http\Controllers\SingleItineraryController::class, 'store']);

// Route::get('/emission/details', [\App\Http\Controllers\ApiCallsController::class, 'getEmissionDetails']);

Route::get('/emission/api-calls/stats', [\App\Http\Controllers\ApiCallsController::class, 'apiCallsDashboardStats']);
Route::get('/emission/api-calls/details', [\App\Http\Controllers\ApiCallsController::class, 'getApiCallDetails']);
Route::get('/emission/from-db/details', [\App\Http\Controllers\ApiCallsController::class, 'getAllFromDb']);
Route::post('/emission/verify', [\App\Http\Controllers\ApiCallsController::class, 'verify']);
Route::post('/emission/store', [\App\Http\Controllers\ApiCallsController::class, 'storeEmission']);













