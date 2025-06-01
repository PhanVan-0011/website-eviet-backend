<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\OrderController;


// Route::prefix('auth')->group(function () {
//     Route::post('register', [AuthController::class, 'register']);
//     Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
//     Route::post('resend-otp', [AuthController::class, 'resendOtp']);
// });

// Auth - Public
Route::controller(AuthController::class)->group(function () {
    // Route tạo user không cần auth
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/reset-password-by-phone', 'resetPasswordByPhone');
});
//Route Slider
Route::prefix('sliders')->controller(SliderController::class)->group(function () {
    Route::post('/', 'store');       // Add
    Route::put('/{id}', 'update');   // Update
    Route::delete('/{id}', 'destroy'); // Delete
    Route::get('/{id}', 'detail');   // Detail
    Route::get('/', 'index');        // List
});
//Categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/multi-delete', [CategoryController::class, 'multiDelete']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});
//Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/multi-delete', [ProductController::class, 'multiDelete']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});
//Post
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{id}', [PostController::class, 'show']);
    Route::post('/', [PostController::class, 'store']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::delete('/{id}', [PostController::class, 'destroy']);
    Route::post('/multi-delete', [PostController::class, 'multiDelete']);
});


// Auth - Protected
Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function () {
    Route::post('/logout', 'logout');
    Route::get('/users/me', 'me');
    Route::put('/users/update_profile', 'update_profile');
    Route::get('/users/getUsers', 'getUsers');
    Route::delete('/users/deleteUser/{id}', 'deleteUser');
});
// User Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/multi-delete', [UserController::class, 'multiDelete']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});
// Orders Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
});
