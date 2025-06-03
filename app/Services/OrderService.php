<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;

class OrderService
{
    /**
     * Tạo một đơn hàng mới
     */
    public function createOrder(array $data, User $user)
    {
        // $user = Auth::guard('sanctum')->user();
        // if (!$user) {
        //     throw new \Exception('Người dùng chưa đăng nhập.');
        // }
        //Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        return DB::transaction(function () use ($data, $user) {
            $order = Order::create([
                'order_date' => now(),
                'total_amount' => 0, // Sẽ tính sau
                'status' => $data['status'] ?? 'pending',
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'shipping_address' => $data['shipping_address'],
                'shipping_fee' => 0.00,
                'cancelled_at' => null,
                'user_id' => $user->id,
            ]);

            // Thêm chi tiết đơn hàng
            foreach ($data['order_details'] as $detailData) {
                $product = Product::findOrFail($detailData['product_id']);
                $unitPrice = $product->sale_price;
                if (!$product) {
                    $errors[] = "Sản phẩm không tồn tại.";
                    continue;
                }
                if ($product->stock_quantity < $detailData['quantity']) {
                     $errors[] = $product->name;
                }
                $product->decrement('stock_quantity', $detailData['quantity']);
                //Tạo chi tiết đơn hàng
                $orderDetail = OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $detailData['quantity'],
                    'unit_price' => $unitPrice,
                ]);                   
            }
            // Cập nhật total_amount
            $this->updateTotalAmount($order);
            // Load quan hệ user trước khi trả về
            $order->load('user', 'orderDetails.product');
            return new OrderResource($order);
        });
    }

    /**
     * Cập nhật thông tin đơn hàng
     */
    public function updateOrder(Order $order, array $data)
    {
        return DB::transaction(function () use ($order, $data) {
            $newStatus = $data['status'] ?? $order->status;
            $cancelledAt = ($newStatus === 'delivered' || $newStatus === 'pending') ? null : $order->cancelled_at;

            $order->update([
                'status' => $newStatus,
                'client_name' => $data['client_name'] ?? $order->client_name,
                'client_phone' => $data['client_phone'] ?? $order->client_phone,
                'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                'shipping_fee' => 0.00,
                'cancelled_at' => $cancelledAt,
            ]);

            if (isset($data['order_details'])) {
                $existingQuantities = $order->orderDetails->pluck('quantity', 'product_id')->toArray();
                OrderDetail::where('order_id', $order->id)->delete();

                foreach ($data['order_details'] as $detailData) {
                    $product = Product::findOrFail($detailData['product_id']);
                    $unitPrice = $product->sale_price;
                    if ($unitPrice === null) {
                        throw new \Exception("Giá sale_price của sản phẩm ID {$product->id} không được định nghĩa.");
                    }
                    $newQuantity = $detailData['quantity'];
                    $oldQuantity = $existingQuantities[$detailData['product_id']] ?? 0;
                    $quantityChange = $newQuantity - $oldQuantity;

                    if ($product->stock_quantity < $quantityChange) {
                        throw new \Exception("Số lượng tồn kho không đủ cho sản phẩm ID {$product->id}.");
                    }
                    if ($quantityChange > 0) {
                        $product->decrement('stock_quantity', $quantityChange);
                    } elseif ($quantityChange < 0) {
                        $product->increment('stock_quantity', abs($quantityChange));
                    }
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $newQuantity,
                        'unit_price' => $unitPrice,
                    ]);
                }
                $this->updateTotalAmount($order);
            }

            $order->load('user', 'orderDetails.product');

            return new OrderResource($order);
        });
    }

    /**
     * Cập nhật total_amount của đơn hàng
     */
    protected function updateTotalAmount(Order $order)
    {
        $totalAmount = $order->orderDetails->sum(function ($detail) {
            return $detail->quantity * $detail->unit_price;
        });
        $order->update(['total_amount' => $totalAmount + $order->shipping_fee]);
    }
}
