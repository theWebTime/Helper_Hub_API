<?php
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationController;
 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
 
// Public routes (no authentication required)
Route::post('register', [AuthenticationController::class, 'register'])->name('register');
Route::post('login', [AuthenticationController::class, 'login'])->name('login');

// Protected routes (authentication required)
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
    Route::get('profile', [AuthenticationController::class, 'profile'])->name('profile');
});