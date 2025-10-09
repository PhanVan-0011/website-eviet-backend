<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductUnitConversionResource extends JsonResource
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
            'product_id' => $this->product_id,
            'unit_name' => $this->unit_name,
            'unit_code' => $this->unit_code,
            'conversion_factor' => (float) $this->conversion_factor,

            'store_price' => (float) $this->store_price, 
            'app_price' => (float) $this->app_price, 
            
            'is_sales_unit' => (bool) $this->is_sales_unit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
