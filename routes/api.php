<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;


// Route::prefix('auth')->group(function () {
//     Route::post('register', [AuthController::class, 'register']);
//     Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
//     Route::post('resend-otp', [AuthController::class, 'resendOtp']);
// });

// Auth - Public
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/reset-password-by-phone','resetPasswordByPhone');

});
//Route Slider
Route::prefix('sliders')->controller(SliderController::class)->group(function () {
    Route::post('/', 'store');       // Add
    Route::put('/{id}', 'update');   // Update
    Route::delete('/{id}', 'destroy'); // Delete
    Route::get('/{id}', 'detail');   // Detail
    Route::get('/', 'index');        // List
});

// Auth - Protected
Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function () {
    Route::post('/logout', 'logout');
    Route::get('/users/me', 'me');
    Route::put('/user/update_profile', 'update_profile');
    Route::get('/users/getUsers', 'getUsers');
    Route::delete('/users/{id}', 'deleteUser');
});



