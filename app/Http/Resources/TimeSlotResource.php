<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
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
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'delivery_time' => $this->delivery_time,
            'is_active' => (bool) $this->is_active,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            
            // Dữ liệu từ các mối quan hệ (sẽ chỉ hiển thị nếu được load)
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            
            // Chỉ trả về ID, không cần load cả object Product/Combo nặng nề
            'product_ids' => $this->whenLoaded('products', function () {
                return $this->products->pluck('id');
            }),
            
            'combo_ids' => $this->whenLoaded('combos', function () {
                return $this->combos->pluck('id');
            }),
        ];
    }
}
