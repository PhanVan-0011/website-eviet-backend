<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'unit_of_measure' => $this->unit_of_measure,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
             'item_discount' => (float) $this->item_discount,
            'subtotal' => (float) $this->subtotal,


        ];
    }
}
