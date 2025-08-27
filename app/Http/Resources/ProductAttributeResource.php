<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            // Lọc ra các value đang active và sắp xếp theo thứ tự
            'values' => AttributeValueResource::collection(
                $this->whenLoaded('values', function () {
                    return $this->values->where('is_active', true)->sortBy('display_order');
                })
            ),
        ];
    }
}
