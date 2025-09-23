<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Định dạng dữ liệu trả về cho danh mục
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
            'icon' => $this->whenLoaded('icon', function () {
                return $this->icon ? asset($this->icon->image_url) : null;
            }),
            'status' => $this->status,
            'parent_id' => $this->parent_id,

            'parent' => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),

            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'products' => ProductResource::collection($this->whenLoaded('products')),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            'promotions' => $this->whenLoaded('promotions', function () {
                return $this->promotions->map(fn($promo) => [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'code' => $promo->code,
                ]);
            }),
        ];
    }
}
