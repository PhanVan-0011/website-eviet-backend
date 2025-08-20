<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => (float) $this->price, // Ép kiểu để đảm bảo là số
            'slug'        => $this->slug,

            'image_urls'  => $this->whenLoaded('image', function () {
                return $this->image ? [new ImageResource($this->image)] : [];
            }),

            'start_date' => optional($this->start_date)->format('Y-m-d H:i:s'),
            'end_date'   => optional($this->end_date)->format('Y-m-d H:i:s'),
            'is_active'   => (bool) $this->is_active, // Ép kiểu để đảm bảo là boolean
            'created_at'  => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at'  => optional($this->updated_at)->format('Y-m-d H:i:s'),
            'items_count' => $this->whenCounted('items'),
            'items'       => ComboItemResource::collection($this->whenLoaded('items')),

            'promotions'  => $this->whenLoaded('promotions', function () {
                return $this->promotions->map(fn($promo) => [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'code' => $promo->code,
                ]);
            }),
        ];
    }
}
