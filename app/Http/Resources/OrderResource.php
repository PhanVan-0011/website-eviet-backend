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
            'order_date'       => $this->order_date,
            'total_amount'     => floatval($this->total_amount),
            'status'           => $this->status,
            'client_name'      => $this->client_name,
            'client_phone'     => $this->client_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_fee'     => $this->shipping_fee,
            'cancelled_at'     => $this->cancelled_at,
            'user'             => new UserResource($this->whenLoaded('user')),
            'order_details'    => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
            'payment'          => new PaymentResource($this->whenLoaded('payment')),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'applied_promotions' => $this->whenLoaded('appliedPromotions', function () {
                return $this->appliedPromotions->map(fn($promo) => [
                    'code' => $promo->code,
                    'discount_amount' => floatval($promo->pivot->discount_amount),
                ]);
            }),
        ];
    }
}
