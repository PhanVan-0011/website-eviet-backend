<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'status'            => $this->status,
            'amount'            => number_format($this->amount, 2), // hoặc $this->amount trực tiếp
            'transaction_id'    => $this->transaction_id,
            //'callback_data'     => $this->callback_data ? json_decode($this->callback_data, true) : null, // nếu callback_data dạng JSON
            'paid_at'           => optional($this->paid_at)->toDateTimeString(),
            'order_id'          => $this->order_id,
            'method_payment_id' => $this->method_payment_id,
            'method'            => new PaymentMethodResource($this->whenLoaded('method')),
            'created_at'        => optional($this->created_at)->toDateTimeString(),
            'updated_at'        => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
