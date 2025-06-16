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
            'price'       => $this->price,
            'slug'        => $this->slug,
            'image_url'   => $this->image_url,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'items_count' => $this->items ? $this->items->count() : 0,
            'items'       => ComboItemResource::collection($this->whenLoaded('items')),
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
