<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Order\StoreOrderRequest;  // Fix: Update namespace
use App\Http\Requests\Api\Order\UpdateOrderRequest;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function index()
    {
        $orders = Order::with(['user', 'orderDetails.product'])->get();

        return OrderResource::collection($orders);
    }
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->validated(),$request->user());
        return new OrderResource($order);
    }
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $updatedOrder = $this->orderService->updateOrder($order, $request->validated());
        return new OrderResource($updatedOrder);
    }
}
