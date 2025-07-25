<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\PrivacyPolicyController;
use App\Http\Controllers\API\FaqController;
use App\Http\Controllers\API\TermsConditionController;
use App\Http\Controllers\API\UserAddressController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\PincodeController;
use App\Http\Controllers\API\SiteSettingController;
use App\Http\Controllers\API\SubServiceTypeNameController;
use App\Http\Controllers\API\SubServiceTypeDetailController;
use App\Http\Controllers\API\SubServiceController;
use App\Http\Controllers\API\RazorpayController;
use App\Http\Controllers\API\ContactUsController;

// Admin Login
Route::post('login', [AuthenticationController::class, 'login']);

// User Registration
Route::group(['prefix' => '/register'], function () {
    Route::post('/send-otp', [AuthenticationController::class, 'sendOtpForRegistration'])->middleware('throttle:3,1');
    Route::post('/verify-otp', [AuthenticationController::class, 'verifyOtpAndRegister']);
});

Route::group(['prefix' => '/user'], function () {

    // User Login
    Route::post('/send-login-otp', [AuthenticationController::class, 'sendOtpForLogin'])->middleware('throttle:3,1');
    Route::post('/verify-login-otp', [AuthenticationController::class, 'verifyOtpAndLogin']);
});

// ***********   open apis without auth   **********
Route::get('subservices/by-service/{serviceId}', [SubserviceController::class, 'getSubservicesByServiceId']);

// Site Setting Route
Route::get('/site-setting-show', [SiteSettingController::class, 'show']);

// Privacy Policy Show API
Route::get('/privacy-policy-index', [PrivacyPolicyController::class, 'index']);

// FAQ Show API
Route::get('/faq-index', [FaqController::class, 'index']);

// Terms & Conditions Show API
Route::get('/terms-condition-index', [TermsConditionController::class, 'index']);

// Service List API Route
Route::get('/service-list', [SubServiceController::class, 'serviceList']);
Route::get('/pincode-list', [UserAddressController::class, 'pincodeList']);

// Random Sub Service List Route
Route::get('/random-sub-service-list', [SubServiceController::class, 'randomSubServiceList']);

// Contact Us Route API
    Route::get('/contact-us-store', [ContactUsController::class, 'store']);

// routes which will be use in both admin and user role
Route::middleware(['auth:api', 'role:admin,user'])->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout']);
    Route::get('profile', [AuthenticationController::class, 'profile']);
    Route::post('profile/update', [AuthenticationController::class, 'updateProfile']);
});


// routes which will be use in admin only
Route::middleware(['auth:api', 'role:admin'])->group(function () {
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

    Route::get('/booking-status-report', [RazorpayController::class, 'bookingStatus']);

    Route::get('/admin-booking-report', [RazorpayController::class, 'adminBookingList']);

    Route::post('/admin-update-booking-status/{id}', [RazorpayController::class, 'updateBookingStatus']);
    
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

    // Contact Us List Route
    Route::get('/contact-us-list', [ContactUsController::class, 'index']);
});

// routes which will be use in user only
Route::middleware(['auth:api', 'role:user'])->group(function () {

    Route::group(['prefix' => '/razorpay'], function () {
        Route::post('/create-order', [RazorpayController::class, 'createOrder']);
        Route::post('/verify-signature', [RazorpayController::class, 'verifySignature']);
    });

    // User Address Route API
    Route::group(['prefix' => '/user-address'], function () {
        Route::get('/pincode-list', [UserAddressController::class, 'pincodeList']);
        Route::get('/index', [UserAddressController::class, 'index']);
        Route::post('/store', [UserAddressController::class, 'store']);
        Route::get('/show/{id}', [UserAddressController::class, 'show']);
        Route::post('/update/{id}', [UserAddressController::class, 'update']);
        Route::post('/delete/{id}', [UserAddressController::class, 'delete']);
    });

    Route::get('/user-booking-report', [RazorpayController::class, 'userBookingList']);

    // Sub Service Type Detail List Route API
    Route::get('/sub-service-type-detail-list', [SubServiceTypeDetailController::class, 'index']);

});
