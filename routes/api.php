<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;


Route::prefix('sliders')->group(function () {
    Route::post('/', [SliderController::class, 'store']);        // Thêm
    Route::put('/{id}', [SliderController::class, 'update']);    // Sửa
    Route::delete('/{id}', [SliderController::class, 'destroy']); // Xóa
    Route::get('/{id}', [SliderController::class, 'show']);       // Chi tiết
});
