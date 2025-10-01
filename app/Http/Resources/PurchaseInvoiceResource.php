<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
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
            'invoice_code' => $this->invoice_code,
            'supplier_id' => $this->supplier_id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'user' => new UserResource($this->whenLoaded('user')),
            
            'invoice_date' => $this->invoice_date,
            'total_quantity' => $this->total_quantity,
            'total_items' => $this->total_items,
            
            'subtotal_amount' =>$this->subtotal_amount,
            'discount_amount' =>$this->discount_amount,
            'total_amount' =>$this->total_amount,
            'paid_amount' => $this->paid_amount,
            'amount_owed' => $this->amount_owed,
            
            'notes' => $this->notes,
            'status' => $this->status,
            'details' => PurchaseInvoiceDetailResource::collection($this->whenLoaded('details')),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
