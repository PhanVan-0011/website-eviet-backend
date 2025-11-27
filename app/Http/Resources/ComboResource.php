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
            'combo_code'  => $this->combo_code,
            'name'        => $this->name,
            'description' => $this->description,
            'base_store_price' => (float) $this->base_store_price,
            'base_app_price' => (float) $this->base_app_price,

            'image_urls'  => $this->whenLoaded('image', function () {
                return $this->image ? [new ImageResource($this->image)] : [];
            }),

            'start_date'  => optional($this->start_date)->format('Y-m-d H:i:s'),
            'end_date'    => optional($this->end_date)->format('Y-m-d H:i:s'),
            'is_active'   => (bool) $this->is_active, // Ép kiểu để đảm bảo là boolean

            'applies_to_all_branches' => (bool) $this->applies_to_all_branches,
            'branches'    => BranchResource::collection($this->whenLoaded('branches')),
            'is_flexible_time' => (bool) $this->is_flexible_time,
            
            'time_slots' => $this->when(
                !$this->is_flexible_time,
                fn() => TimeSlotResource::collection($this->whenLoaded('timeSlots'))
            ),
            
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
