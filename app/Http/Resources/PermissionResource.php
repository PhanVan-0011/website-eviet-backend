<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Tách tên quyền thành nhóm và hành động dễ gom nhóm.
        // vd 'products.create' sẽ được tách thành:
        // - group: 'products'
        // - action: 'create'
        [$group, $action] = explode('.', $this->name, 2);
        
        return [
            'id' => $this->id,
            'name' => $this->name, // Tên đầy đủ, ví dụ: 'products.create'
            'group' => $group,    // Tên nhóm, ví dụ: 'products'
            'action' => $action    // Tên hành động, ví dụ: 'create'
        ];
    }
}
