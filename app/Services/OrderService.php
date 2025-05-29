<?php

   namespace App\Services;

   use App\Models\Order;
   use App\Models\OrderDetail;
   use App\Models\User;
   use App\Models\Product;
   use Illuminate\Support\Facades\DB;

   class OrderService
   {
       /**
        * Tạo một đơn hàng mới
        */
       public function createOrder(array $data, User $user)
       {
           // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
           return DB::transaction(function () use ($data, $user) {
               $order = Order::create([
                   'order_date' => now(),
                   'total_amount' => 0, // Sẽ tính sau
                   'status' => $data['status'] ?? 'pending',
                   'client_name' => $data['client_name'],
                   'client_phone' => $data['client_phone'],
                   'shipping_address' => $data['shipping_address'],
                   'shipping_fee' => $data['shipping_fee'] ?? 0.00,
                   'cancelled_at' => null,
                   'user_id' => $user->id,
               ]);

               // Thêm chi tiết đơn hàng
               foreach ($data['order_details'] as $detailData) {
                   $product = Product::findOrFail($detailData['product_id']);
                   $orderDetail = OrderDetail::create([
                       'order_id' => $order->id,
                       'product_id' => $product->id,
                       'quantity' => $detailData['quantity'],
                       'unit_price' => $product->price,
                   ]);

                   // Cập nhật total_amount
                   $this->updateTotalAmount($order);
               }

               return $order;
           });
       }

       /**
        * Cập nhật thông tin đơn hàng
        */
       public function updateOrder(Order $order, array $data)
       {
           return DB::transaction(function () use ($order, $data) {
               $order->update([
                   'status' => $data['status'] ?? $order->status,
                   'client_name' => $data['client_name'] ?? $order->client_name,
                   'client_phone' => $data['client_phone'] ?? $order->client_phone,
                   'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                   'shipping_fee' => $data['shipping_fee'] ?? $order->shipping_fee,
               ]);

               // Xóa chi tiết cũ và thêm chi tiết mới
               if (isset($data['order_details'])) {
                   OrderDetail::where('order_id', $order->id)->delete();
                   foreach ($data['order_details'] as $detailData) {
                       $product = Product::findOrFail($detailData['product_id']);
                       OrderDetail::create([
                           'order_id' => $order->id,
                           'product_id' => $product->id,
                           'quantity' => $detailData['quantity'],
                           'unit_price' => $product->price,
                       ]);
                   }
                   $this->updateTotalAmount($order);
               }

               return $order;
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