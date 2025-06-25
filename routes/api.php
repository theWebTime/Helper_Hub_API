<?php
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\PrivacyPolicyController;
 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
 
// Public routes (no authentication required)
Route::post('register', [AuthenticationController::class, 'register'])->name('register');
Route::post('login', [AuthenticationController::class, 'login'])->name('login');

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('/check-admin', [AuthenticationController::class, 'admin']);
});

Route::middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('/check-user', [AuthenticationController::class, 'user']);
});

// Protected routes (authentication required)
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
    Route::put('profile/update', [AuthenticationController::class, 'updateProfile']);
    Route::get('profile', [AuthenticationController::class, 'profile'])->name('profile');

     // Privacy Policy Routes
    Route::group(['prefix' => '/privacy-policy'], function () {
        Route::get('/index', [PrivacyPolicyController::class, 'index']);
        Route::post('/store', [PrivacyPolicyController::class, 'store']);
        Route::get('/show/{id}', [PrivacyPolicyController::class, 'show']);
        Route::post('/update/{id}', [PrivacyPolicyController::class, 'update']);
        Route::post('/delete/{id}', [PrivacyPolicyController::class, 'delete']);
    });

});
