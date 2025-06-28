<?php
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\PrivacyPolicyController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\TermsConditionController;
use App\Http\Controllers\API\UserAddressController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\PincodeController;

 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
 
// Public routes (no authentication required)
// Route::post('register', [AuthenticationController::class, 'register'])->name('register');
Route::post('/register/send-otp', [AuthenticationController::class, 'sendOtpForRegistration']);
Route::post('/register/verify-otp', [AuthenticationController::class, 'verifyOtpAndRegister']);
Route::post('login', [AuthenticationController::class, 'login'])->name('login');

// Protected routes (authentication required)
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
    Route::put('profile/update', [AuthenticationController::class, 'updateProfile']);
    Route::get('profile', [AuthenticationController::class, 'profile'])->name('profile');
});


Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('/check-admin', [AuthenticationController::class, 'admin']);

    // Registered User Route
    Route::group(['prefix' => '/user'], function () {
        Route::get('/index', [UserController::class, 'index']);
        Route::get('/show/{id}', [UserController::class, 'show']);
        Route::post('/update/{id}', [UserController::class, 'update']);
    });

    // Service Route
    Route::group(['prefix' => '/service'], function () {
        Route::get('/index', [ServiceController::class, 'index']);
        Route::get('/show/{id}', [ServiceController::class, 'show']);
        Route::post('/update/{id}', [ServiceController::class, 'update']);
    });

    // Pincode Routes
    Route::group(['prefix' => '/pincode'], function () {
        Route::get('/index', [PincodeController::class, 'index']);
        Route::post('/store', [PincodeController::class, 'store']);
        Route::get('/show/{id}', [PincodeController::class, 'show']);
        Route::post('/update/{id}', [PincodeController::class, 'update']);
        Route::post('/delete/{id}', [PincodeController::class, 'delete']);
    });

    // Privacy Policy Routes
    Route::group(['prefix' => '/privacy-policy'], function () {
        Route::get('/index', [PrivacyPolicyController::class, 'index']);
        Route::post('/store', [PrivacyPolicyController::class, 'store']);
        Route::get('/show/{id}', [PrivacyPolicyController::class, 'show']);
        Route::post('/update/{id}', [PrivacyPolicyController::class, 'update']);
        Route::post('/delete/{id}', [PrivacyPolicyController::class, 'delete']);
    });

    // FAQ Routes
    Route::group(['prefix' => '/faq'], function () {
        Route::get('/index', [FaqController::class, 'index']);
        Route::post('/store', [FaqController::class, 'store']);
        Route::get('/show/{id}', [FaqController::class, 'show']);
        Route::post('/update/{id}', [FaqController::class, 'update']);
        Route::post('/delete/{id}', [FaqController::class, 'delete']);
    });

    // Terms & Conditions Routes
    Route::group(['prefix' => '/terms-condition'], function () {
        Route::get('/index', [TermsConditionController::class, 'index']);
        Route::post('/store', [TermsConditionController::class, 'store']);
        Route::get('/show/{id}', [TermsConditionController::class, 'show']);
        Route::post('/update/{id}', [TermsConditionController::class, 'update']);
        Route::post('/delete/{id}', [TermsConditionController::class, 'delete']);
    });
});

Route::middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('/check-user', [AuthenticationController::class, 'user']);

    // Privacy Policy Show API
    Route::get('/privacy-policy-index', [PrivacyPolicyController::class, 'index']);

    // FAQ Show API
    Route::get('/faq-index', [FaqController::class, 'index']);

    // Terms & Conditions Show API
    Route::get('/terms-condition-index', [TermsConditionController::class, 'index']);

    Route::group(['prefix' => '/user-address'], function () {
        Route::get('/index', [UserAddressController::class, 'index']);
        Route::post('/store', [UserAddressController::class, 'store']);
        Route::get('/show/{id}', [UserAddressController::class, 'show']);
        Route::post('/update/{id}', [UserAddressController::class, 'update']);
        Route::post('/delete/{id}', [UserAddressController::class, 'delete']);
    });
    
});

