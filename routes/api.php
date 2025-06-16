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
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\PromotionController;



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
Route::prefix('sliders')->group(function () {
    Route::get('/', [SliderController::class, 'index']);
    Route::post('/', [SliderController::class, 'store']);
    Route::delete('/multi-delete', [SliderController::class, 'multiDelete']);
    Route::get('/{id}', [SliderController::class, 'show']);
    Route::post('/{id}', [SliderController::class, 'update']);
    Route::delete('/{id}', [SliderController::class, 'destroy']);
});
//Route combos
Route::middleware('auth:sanctum')->prefix('combos')->group(function () {
    Route::get('/', [ComboController::class, 'index']);
    Route::delete('/multi-delete', [ComboController::class, 'multiDelete']);
    Route::post('/', [ComboController::class, 'store']);
    Route::get('/{id}', [ComboController::class, 'show']);
    Route::post('/{id}', [ComboController::class, 'update']);
    Route::delete('/{id}', [ComboController::class, 'destroy']);
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
    Route::post('/{id}', [ProductController::class, 'update']);
    Route::delete('/multi-delete', [ProductController::class, 'multiDelete']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});
//Post
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{id}', [PostController::class, 'show']);
    Route::post('/', [PostController::class, 'store']);
    Route::post('/{id}', [PostController::class, 'update']);
    Route::delete('/multi-delete', [PostController::class, 'multiDelete']);
    Route::delete('/{id}', [PostController::class, 'destroy']);
});
// Promotions
Route::prefix('promotions')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/{promotion}', [PromotionController::class, 'show']);
    Route::put('/{promotion}', [PromotionController::class, 'update']);
    Route::delete('/multi-delete', [PromotionController::class, 'multiDelete']);
    Route::delete('/{promotion}', [PromotionController::class, 'destroy']);
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
// Orders
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/multi-cancel', [OrderController::class, 'multiCancel']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus']);
});

// Route hiển thị hình ảnh
Route::get('images/{path}', [ImageController::class, 'show'])->where('path', '.*');
