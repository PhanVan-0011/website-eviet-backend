<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'price_type' => $this->price_type,
            'unit_of_measure' => $this->unit_of_measure,
            'price' => (float) $this->price,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
