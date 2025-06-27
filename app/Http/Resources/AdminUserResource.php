<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            
            'is_active' => $this->is_active,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->format('d-m-Y') : null,
            'address' => $this->address,
            
            'is_verified' => $this->is_verified,
            'last_login_at' => $this->last_login_at ? $this->last_login_at->toIso8601String() : null,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toIso8601String() : null,
            'phone_verified_at' => $this->phone_verified_at ? $this->phone_verified_at->toIso8601String() : null,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,
            
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            //'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
