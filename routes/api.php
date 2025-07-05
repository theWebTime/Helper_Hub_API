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
use App\Http\Controllers\API\SiteSettingController;
use App\Http\Controllers\API\SubServiceTypeNameController;
use App\Http\Controllers\API\SubServiceTypeDetailController;
use App\Http\Controllers\API\SubServiceController;

 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
 
// Public routes (no authentication required)
// Route::post('register', [AuthenticationController::class, 'register'])->name('register');
Route::post('/register/send-otp', [AuthenticationController::class, 'sendOtpForRegistration']);
Route::post('/register/verify-otp', [AuthenticationController::class, 'verifyOtpAndRegister']);
Route::post('login', [AuthenticationController::class, 'login'])->name('login');

Route::post('user/send-login-otp', [AuthenticationController::class, 'sendOtpForLogin']);
Route::post('user/verify-login-otp', [AuthenticationController::class, 'verifyOtpAndLogin']);

//Site Setting Route
Route::get('/site-setting-show', [SiteSettingController::class, 'show']);

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

    // Sub Service  Routes
    Route::group(['prefix' => '/sub-service'], function () {
        Route::get('/service-name-list', [SubServiceController::class, 'serviceList']);
        Route::get('/sub-service-type-name-list', [SubServiceTypeDetailController::class, 'listIndex']);
        Route::get('/index', [SubServiceController::class, 'index']);
        Route::post('/store', [SubServiceController::class, 'store']);
        Route::get('/show/{id}', [SubServiceController::class, 'show']);
        Route::post('/update/{id}', [SubServiceController::class, 'update']);
        Route::post('/delete/{id}', [SubServiceController::class, 'delete']);
    });

    // Pincode Routes
    Route::group(['prefix' => '/pincode'], function () {
        Route::get('/index', [PincodeController::class, 'index']);
        Route::post('/store', [PincodeController::class, 'store']);
        Route::get('/show/{id}', [PincodeController::class, 'show']);
        Route::post('/update/{id}', [PincodeController::class, 'update']);
        Route::post('/delete/{id}', [PincodeController::class, 'delete']);
    });

    // User Address List Route
        Route::get('/user-address-list', [UserAddressController::class, 'userAddressList']);

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

    // Site Setting Routes
    Route::group(['prefix' => '/site-setting'], function () {
        Route::post('/store', [SiteSettingController::class, 'updateOrCreate']);
    });

    // Sub Service Type Name List Route
    Route::get('/sub-service-type-name-list', [SubServiceTypeNameController::class, 'list']);

    // Sub Service Type Detail Routes
    Route::group(['prefix' => '/sub-service-type-detail'], function () {
        Route::get('/sub-service-type-name-list', [SubServiceTypeDetailController::class, 'listIndex']);
        Route::get('/index', [SubServiceTypeDetailController::class, 'index']);
        Route::post('/store', [SubServiceTypeDetailController::class, 'store']);
        Route::get('/show/{id}', [SubServiceTypeDetailController::class, 'show']);
        Route::post('/update/{id}', [SubServiceTypeDetailController::class, 'update']);
        Route::post('/delete/{id}', [SubServiceTypeDetailController::class, 'delete']);
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

    // User Address Route API
    Route::group(['prefix' => '/user-address'], function () {
        Route::get('/index', [UserAddressController::class, 'index']);
        Route::post('/store', [UserAddressController::class, 'store']);
        Route::get('/show/{id}', [UserAddressController::class, 'show']);
        Route::post('/update/{id}', [UserAddressController::class, 'update']);
        Route::post('/delete/{id}', [UserAddressController::class, 'delete']);
    });

    // Sub Service Type Name List Route API
    Route::get('/sub-service-type-name-list', [SubServiceTypeNameController::class, 'list']);

    // Sub Service Type Detail List Route API
    Route::get('/sub-service-type-detail-list', [SubServiceTypeDetailController::class, 'index']);
    
});

