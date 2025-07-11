<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'display_name' => $this->display_name,
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'permissions' => $this->when(
                $request->routeIs('roles.*'),
                PermissionResource::collection($this->whenLoaded('permissions'))
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->created_at->toIso8601String(),
        ];
    }
}
