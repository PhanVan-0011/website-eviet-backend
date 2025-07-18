<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Lấy danh sách phương thức thanh toán đang hoạt động
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->query('context') === 'select_list') {

            $this->authorize('payment_methods.select_list');
            $methods = PaymentMethod::where('is_active', true)
                ->select('id', 'name', 'code')
                ->get();

            return response()->json($methods);
        } else {
            $this->authorize('payment_methods.view');
            $methods = PaymentMethod::where('is_active', true)->get();
            return response()->json([
                'success' => true,
                'data' => PaymentMethodResource::collection($methods)
            ]);
        }
    }
}
