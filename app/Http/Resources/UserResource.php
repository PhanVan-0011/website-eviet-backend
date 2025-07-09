<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth
                ? \Carbon\Carbon::parse($this->date_of_birth)->format('d/m/Y')
                : null,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'last_login_at' => $this->last_login_at
                ? \Carbon\Carbon::parse($this->last_login_at)->format('Y-m-d H:i:s')
                : null,
            'email_verified_at' => $this->email_verified_at
                ? \Carbon\Carbon::parse($this->email_verified_at)->format('Y-m-d H:i:s')
                : null,
            'phone_verified_at' => $this->phone_verified_at
                ? \Carbon\Carbon::parse($this->phone_verified_at)->format('Y-m-d H:i:s')
                : null,

            'image_url' => $this->whenLoaded('image', function () {
                return $this->image ? new ImageResource($this->image) : null;
            }),


            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions', function () {
                return $this->getAllPermissions();
            })),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
