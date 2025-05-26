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
            'status' => $this->status,
            'parent_id' => $this->parent_id,
            //chỉ tải dữ liệu parent nếu quan hệ đã được tải.
            'parent' => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            //định dạng tập hợp các danh mục con (children).
            'children' => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
