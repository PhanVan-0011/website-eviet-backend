<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Định dạng dữ liệu trả về cho sản phẩm
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'original_price' => $this->original_price,
            'sale_price' => $this->sale_price,
            'stock_quantity' => $this->stock_quantity,
            'status' => $this->status,
            'image_url' => $this->image_url,
            'size' => $this->size, // Thêm trường size
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
