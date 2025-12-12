<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $itemName = 'Sản phẩm không xác định';
        if ($this->product) {
            $itemName = $this->product->name;
        } elseif ($this->combo) {
            $itemName = $this->combo->name;
        }

        $attributes = $this->attributes_snapshot;
        return [
            'id'         => $this->id,
            'order_id'   => $this->order_id,
            'product_id' => $this->product_id,
            'combo_id'   => $this->combo_id,
            'combo_name' => optional($this->combo)->name,
            'product'    => new ProductResource($this->whenLoaded('product')),
            'combo'      => new ComboResource($this->whenLoaded('combo')),
            'quantity'   => $this->quantity,
            'unit_price' => $this->unit_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,


            'id'              => $this->id,
            'order_id'        => $this->order_id,
            
            // Định danh
            'product_id'      => $this->product_id,
            'combo_id'        => $this->combo_id,
            'is_combo'        => !empty($this->combo_id),
            
            // Thông tin hiển thị
            'item_name'       => $itemName,

            // Số lượng & Giá
            'unit_of_measure' => $this->unit_of_measure,
            'quantity'        => (int) $this->quantity,
            'unit_price'      => (float) $this->unit_price,
            'subtotal'        => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,

            'selected_attributes' => $attributes ?? [],

            // Load chi tiết 
            'product'         => new ProductResource($this->whenLoaded('product')),
            'combo'           => new ComboResource($this->whenLoaded('combo')),
            
            'created_at'      => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,  

        ];
    }
}
