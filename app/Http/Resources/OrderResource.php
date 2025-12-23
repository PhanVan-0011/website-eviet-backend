<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_code'       => $this->order_code,
            'order_date'       => $this->order_date ? $this->order_date->format('Y-m-d H:i:s') : null,

            // --- Trạng thái & Cấu hình ---
            'status'           => $this->status,
            'order_method'     => $this->order_method, // delivery, takeaway
            'price_type'       => $this->price_type,   
            // --- Thông tin khách ---
            'client_name'      => $this->client_name,
            'client_phone'     => $this->client_phone,
            'notes'            => $this->notes,

            // --- Tài chính ---
            'total_amount'     => (float) $this->total_amount,
            'shipping_fee'     => (float) $this->shipping_fee,
            'discount_amount'  => (float) $this->discount_amount, // Giảm giá tổng đơn
            'grand_total'      => (float) $this->grand_total,

            // --- Quan hệ dữ liệu (Eager Loading) ---
            'branch'           => new BranchResource($this->whenLoaded('branch')),
            'user'             => new UserResource($this->whenLoaded('user')),
            'payment'          => new PaymentResource($this->whenLoaded('payment')),

            // Hiển thị chi tiết Ca và Điểm nhận
            'pickup_location'  => new PickupLocationResource($this->whenLoaded('pickupLocation')),
            'time_slot'        => new TimeSlotResource($this->whenLoaded('timeSlot')),

            // Chi tiết sản phẩm
            'order_details'    => OrderDetailResource::collection($this->whenLoaded('orderDetails')),

            'cancelled_at'     => $this->cancelled_at ? $this->cancelled_at->format('Y-m-d H:i:s') : null,
            'created_at'       => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i:s'),

            // Khuyến mãi (nếu có logic áp dụng voucher)
            'applied_promotions' => $this->whenLoaded('appliedPromotions', function () {
                return $this->appliedPromotions->map(fn($promo) => [
                    'code' => $promo->code,
                    'discount_amount' => (float) $promo->pivot->discount_amount,
                ]);
            }),
        ];
    }
}
