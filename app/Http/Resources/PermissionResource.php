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
         $parts = explode('.', $this->name, 2);

        return [
            'id' => $this->id,
            'name' => $this->name, // Tên đầy đủ, ví dụ: 'products.create'
            'group' => $parts[0]?? 'other', // Thêm 'group' để Service có thể dùng groupBy()
            'action' => $parts[1] ?? 'manage' // Nếu không có action, mặc định là 'manage'
        ];
    }
}
