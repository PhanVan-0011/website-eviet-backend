<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->id, // ID của sản phẩm
            'name' => $this->name,
            'product_code' => $this->product_code,
            'description' => $this->description,
            
            // Lấy 'quantity' từ bảng trung gian (pivot)
            //'quantity' => $this->pivot->quantity, 
            'quantity' => $this->whenPivotLoaded('combo_items', function () {
                return $this->pivot->quantity;
            }), 
            
            
            // Tải ảnh đại diện của sản phẩm (nếu có)
            'image_url' => $this->whenLoaded('featuredImage', fn() => $this->featuredImage?->image_url), 
            'base_unit' => $this->base_unit,
            'base_store_price' => (float) $this->base_store_price, // Bổ sung giá
            'base_app_price' => (float) $this->base_app_price,   // Bổ sung giá

            // BỔ SUNG: Tải các thuộc tính của sản phẩm
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
        ];
    }
}

