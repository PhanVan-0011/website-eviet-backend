<?php

namespace App\Http\Resources;

use App\Models\Role;
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
        /** @var Role $role */
        $role = $this->resource;
        
        // Tính branch_scope từ role name
        $branchScope = match($role->name) {
            'super-admin' => Role::SCOPE_ALL_BRANCHES,
            'accountant' => Role::SCOPE_ALL_BRANCHES,
            'sales-staff' => Role::SCOPE_SINGLE_BRANCH,
            'branch-admin' => Role::SCOPE_MULTIPLE_BRANCHES,
            default => Role::SCOPE_SINGLE_BRANCH,
        };
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'branch_scope' => $branchScope, // Scope cho branch selection
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
