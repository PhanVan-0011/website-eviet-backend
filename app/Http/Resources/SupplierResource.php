<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'group_id' => $this->group_id,
            'group' => new SupplierGroupResource($this->whenLoaded('group')),
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'email' => $this->email,
            'tax_code' => $this->tax_code,
            'notes' => $this->notes,
            'total_purchase_amount' => $this->total_purchase_amount,
            'balance_due' => $this->balance_due,
            'active' => $this->is_active,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
