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
            
            // --- Trạng thái & Phương thức ---
            'status'           => $this->status,
            'order_method'     => $this->order_method, // delivery, takeaway, dine_in
            'pickup_time'      => $this->pickup_time ? $this->pickup_time->format('Y-m-d H:i:s') : null,
            
            // --- Thông tin khách & Giao nhận ---
            'client_name'      => $this->client_name,
            'client_phone'     => $this->client_phone,
            'notes'            => $this->notes, // Ghi chú đơn hàng
            'shipping_address' => $this->shipping_address,
            
            // --- Tài chính (Ép kiểu float để Frontend dễ tính toán) ---
            'total_amount'     => (float) $this->total_amount,
            'shipping_fee'     => (float) $this->shipping_fee,
            'discount_amount'  => (float) $this->discount_amount,
            'grand_total'      => (float) $this->grand_total,
            
            // --- Quan hệ 
            'branch'           => new BranchResource($this->whenLoaded('branch')),
            'user'             => new UserResource($this->whenLoaded('user')),
            'payment'          => new PaymentResource($this->whenLoaded('payment')),
            
            // Chi tiết món ăn
            'order_details'    => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
        
            'cancelled_at'     => $this->cancelled_at ? $this->cancelled_at->format('Y-m-d H:i:s') : null,
            'created_at'       => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Khuyến mãi đã áp dụng
            'applied_promotions' => $this->whenLoaded('appliedPromotions', function () {
                return $this->appliedPromotions->map(fn($promo) => [
                    'code' => $promo->code,
                    'discount_amount' => (float) $promo->pivot->discount_amount,
                ]);
            }),
        ];
    }
}
