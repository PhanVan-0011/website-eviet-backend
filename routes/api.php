<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;



//Route Rgister
Route::post('/register', [AuthController::class, 'register']);
//Route Login
Route::post('/login', [AuthController::class, 'login']);

//Route Slider
Route::prefix('sliders')->group(function () {
    Route::post('/', [SliderController::class, 'store']);        // Add
    Route::put('/{id}', [SliderController::class, 'update']);    // Update
    Route::delete('/{id}', [SliderController::class, 'destroy']); // Delete
    Route::get('/{id}', [SliderController::class, 'detail']);// Detail
    Route::get('', [SliderController::class, 'index']);  //List     
});
