<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    /**
     * Lấy danh sách phương thức thanh toán đang hoạt động
     */
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::where('is_active', true)->get();
        return response()->json([
            'success' => true,
            'data' => PaymentMethodResource::collection($methods)
        ]);
    }
}
