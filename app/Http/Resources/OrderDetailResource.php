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
        // 1. Xử lý tên hiển thị (Ưu tiên Product -> Combo -> Fallback)
        $itemName = 'Sản phẩm/Combo đã xóa';
        if ($this->product) {
            $itemName = $this->product->name;
        } elseif ($this->combo) {
            $itemName = $this->combo->name;
        }

        // 2. Xử lý hình ảnh (Lấy từ Product hoặc Combo)
        $imageUrl = null;
        if ($this->product && $this->product->featuredImage) {
            $imageUrl = (new ImageResource($this->product->featuredImage))->toArray($request)['thumb_url'] ?? null;
        } elseif ($this->combo && $this->combo->image) {
            $imageUrl = (new ImageResource($this->combo->image))->toArray($request)['thumb_url'] ?? null;
        }

        // 3. Lấy Attributes (Topping/Size) từ cột JSON
        // Model OrderDetail cần có protected $casts = ['attributes_snapshot' => 'array']
        $attributes = $this->attributes_snapshot;

        return [
            'id'              => $this->id,
            'order_id'        => $this->order_id,
            
            // Định danh & Phân loại
            'product_id'      => $this->product_id,
            'combo_id'        => $this->combo_id,
            'is_combo'        => !empty($this->combo_id),
            
            // Thông tin hiển thị
            'item_name'       => $itemName,
            'image_url'       => $imageUrl,

            // Số lượng & Giá
            'unit_of_measure' => $this->unit_of_measure,
            'quantity'        => (int) $this->quantity,
            'unit_price'      => (float) $this->unit_price, // Đơn giá (đã gồm topping)
            'subtotal'        => (float) $this->subtotal,   // Thành tiền (đơn giá * SL)
            'discount_amount' => (float) $this->discount_amount,

            'selected_attributes' => $attributes ?? [],

            'product'         => new ProductResource($this->whenLoaded('product')),
            'combo'           => new ComboResource($this->whenLoaded('combo')),
            
            'created_at'      => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at'      => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
