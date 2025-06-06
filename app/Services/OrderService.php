<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function getAllOrders($request)
    {
        try {
            // Lấy các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $keyword = $request->input('keyword');

            $status = $request->input('status');
            $paymentMethod = $request->input('payment_method');
            $orderFrom = $request->input('order_from');
            $orderTo = $request->input('order_to');



            // Tạo query cơ bản
            $query = Order::query()
                ->with(['user', 'orderDetails.product', 'payment']);
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('client_name', 'like', "%{$keyword}%")
                        ->orWhere('client_phone', 'like', "%{$keyword}%")
                        ->orWhereHas('user', function ($uq) use ($keyword) {
                            $uq->where('email', 'like', "%{$keyword}%");
                        });
                });
            }

            // Lọc theo trạng thái đơn hàng
            if (!empty($status)) {
                $query->where('status', $status);
            }
            // Lọc theo phương thức thanh toán
            if (!empty($paymentMethod)) {
                $query->whereHas('payment', function ($q) use ($paymentMethod) {
                    $q->where('gateway', 'like', "%{$paymentMethod}%");
                });
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('id', 'desc');

            // Tính tổng số bản ghi
            $total = $query->count();

            // Phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $orders = $query->skip($offset)->take($perPage)->get();

            // Tính phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả kết quả
            return [
                'data' => $orders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách đơn hàng: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getOrderById(int $id)
    {
        $order = Order::with(['user', 'orderDetails.product', 'payment'])->find($id);

        if (!$order) {
            throw new \Exception("Đơn hàng không tồn tại.");
        }

        return $order;
    }
    /**
     * Tạo một đơn hàng mới
     */
    public function createOrder(array $data, User $user)
    {

        //Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        try {
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
                    if ($product->stock_quantity < $detailData['quantity']) {
                        throw new \Exception("Sản phẩm '{$product->name}' không đủ hàng trong kho.");
                    }
                    //trừ tồn kho khi tạo đơn hàng
                    $product->decrement('stock_quantity', $detailData['quantity']);
                    //Tạo chi tiết đơn hàng
                    $orderDetail = OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $detailData['quantity'],
                        'unit_price' => $product->sale_price,
                    ]);
                }
                // Cập nhật total_amount
                $this->updateTotalAmount($order);
                //Nếu có thông tin thanh toán, tạo payment record
                if (!empty($data['payment'])) {
                    $paymentData = $data['payment'];

                    Payment::create([
                        'gateway' => $paymentData['gateway'] ?? 'COD',
                        'status' => $paymentData['status'] ?? 'pending',
                        'amount' => $order->total_amount,
                        'transaction_id' => $paymentData['transaction_id'] ?? null,
                        'paid_at' => $paymentData['paid_at'] ?? null,
                        'callback_data' => $paymentData['callback_data'] ?? null,
                        'is_active' => true,
                        'order_id' => $order->id,
                    ]);
                }
                Log::info('Payment data:', $paymentData ?? []);
                // Load quan hệ user trước khi trả về
                $order = $order->fresh(['user', 'orderDetails.product', 'payment']);
                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            Log::error("Lỗi khi tạo đơn hàng: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin đơn hàng
     */
    public function updateOrder(Order $order, array $data)
    {
        try {
            return DB::transaction(function () use ($order, $data) {
                $newStatus = $data['status'] ?? $order->status;
                $cancelledAt = ($newStatus === 'delivered' || $newStatus === 'pending') ? null : $order->cancelled_at;
                //Cập nhật Order
                $order->update([
                    'status' => $newStatus,
                    'client_name' => $data['client_name'] ?? $order->client_name,
                    'client_phone' => $data['client_phone'] ?? $order->client_phone,
                    'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                    'shipping_fee' => 0.00,
                    'cancelled_at' => $cancelledAt,
                ]);
                //Kiểm tra và cập nhật Order_details
                if (isset($data['order_details'])) {
                    // Hoàn tồn kho cũ
                    foreach ($order->orderDetails as $oldDetail) {
                        $product = Product::findOrFail($oldDetail->product_id);
                        $product->increment('stock_quantity', $oldDetail->quantity);
                    }
                    // Xoá chi tiết cũ
                    OrderDetail::where('order_id', $order->id)->delete();

                    foreach ($data['order_details'] as $detailData) {
                        $product = Product::findOrFail($detailData['product_id']);
                        // Kiểm tra tồn kho
                        if ($product->stock_quantity < $detailData['quantity']) {
                            throw new \Exception("Số lượng tồn kho không đủ cho sản phẩm ID {$product->id}.");
                        }
                        $product->decrement('stock_quantity', $detailData['quantity']);
                        OrderDetail::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' =>  $detailData['quantity'],
                            'unit_price' => $product->sale_price,
                        ]);
                    }
                }
                //Cập nhất lại tổng tiền của đơn hàng
                $this->updateTotalAmount($order);
                // Cập nhật hoặc tạo mới thông tin thanh toán nếu có
                if (isset($data['payment'])) {
                    $paymentData = $data['payment'];
                    $payment = $order->payment;

                    if ($payment) {
                        $payment->update([
                            'gateway' => $paymentData['gateway'] ?? $payment->gateway,
                            'status' => $paymentData['status'] ?? $payment->status,
                            'transaction_id' => $paymentData['transaction_id'] ?? $payment->transaction_id,
                            'paid_at' => $paymentData['paid_at'] ?? $payment->paid_at,
                            'callback_data' => $paymentData['callback_data'] ?? $payment->callback_data,
                            'amount' => $order->total_amount,
                        ]);
                    } else {
                        Payment::create([
                            'gateway' => $paymentData['gateway'] ?? 'COD',
                            'status' => $paymentData['status'] ?? 'pending',
                            'transaction_id' => $paymentData['transaction_id'] ?? null,
                            'paid_at' => $paymentData['paid_at'] ?? null,
                            'callback_data' => $paymentData['callback_data'] ?? null,
                            'is_active' => true,
                            'amount' => $order->total_amount,
                            'order_id' => $order->id,
                        ]);
                    }
                }
                // Load lại quan hệ trước khi trả về
                $order = $order->fresh(['user', 'orderDetails.product', 'payment']);
                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            Log::error("Lỗi khi cập nhật đơn hàng: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Cập nhật total_amount của đơn hàng
     */
    protected function updateTotalAmount(Order $order)
    {
        // Load lại orderDetails từ database
        $order->load('orderDetails');
        $totalAmount = $order->orderDetails->sum(function ($detail) {
            return $detail->quantity * $detail->unit_price;
        });
        $order->update(['total_amount' => $totalAmount + $order->shipping_fee]);
    }
    public function deleteOrder(Order $order)
    {
        try {
            return DB::transaction(function () use ($order) {
                // Hoàn tồn kho trước khi xoá
                foreach ($order->orderDetails as $detail) {
                    $product = Product::find($detail->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $detail->quantity);
                    } else {
                        Log::warning("Không tìm thấy sản phẩm ID {$detail->product_id} khi hoàn tồn kho. Bỏ qua.");
                    }
                }

                // Xoá payment nếu có
                if ($order->payment) {
                    $order->payment->delete();
                }

                // Xoá chi tiết đơn hàng
                $order->orderDetails()->delete();

                // Xoá đơn hàng
                $order->delete();

                return response()->json(['message' => 'Đã xoá đơn hàng thành công.']);
            });
        } catch (\Exception $e) {
            Log::error("Lỗi khi xoá đơn hàng: " . $e->getMessage());
            throw $e;
        }
    }
    public function deleteMultipleOrders(array $ids)
    {
        try {
            return DB::transaction(function () use ($ids) {
                $orders = Order::with(['orderDetails', 'payment'])->whereIn('id', $ids)->get();

                foreach ($orders as $order) {
                    // Hoàn tồn kho
                    foreach ($order->orderDetails as $detail) {
                        $product = Product::findOrFail($detail->product_id);
                        $product->increment('stock_quantity', $detail->quantity);
                    }
                    // Xoá payment nếu có
                    if ($order->payment) {
                        $order->payment->delete();
                    }
                    //Xoá chi tiết đơn hàng
                    $order->orderDetails()->delete();
                    // Xoá đơn hàng
                    $order->delete();
                    Log::info("Đã xoá đơn hàng ID {$order->id} thành công.");
                }

                return response()->json(['message' => 'Đã xoá các đơn hàng thành công.']);
            });
        } catch (\Exception $e) {
            Log::error("Lỗi khi xoá nhiều đơn hàng: " . $e->getMessage());
            throw $e;
        }
    }
}
