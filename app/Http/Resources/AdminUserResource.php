<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Role;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Lấy role đầu tiên (vì 1 user chỉ có 1 role)
        $role = $this->whenLoaded('roles') ? $this->roles->first() : null;
        $scope = $role ? $this->getRoleScope($role) : null;

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            
            'is_active' => $this->is_active,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->format('d-m-Y') : null,
            'address' => $this->address,
            'image_url' => $this->whenLoaded('image', function () {
                return $this->image ? new ImageResource($this->image) : null;
            }),

            'is_verified' => $this->is_verified,
            'last_login_at' => $this->last_login_at ? $this->last_login_at->toIso8601String() : null,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toIso8601String() : null,
            'phone_verified_at' => $this->phone_verified_at ? $this->phone_verified_at->toIso8601String() : null,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,
            
            // Trả về role (singular) thay vì roles
            'role' => $role ? new RoleResource($role) : null,
        ];

        // Chỉ trả về branch data phù hợp với scope
        if ($scope === Role::SCOPE_SINGLE_BRANCH) {
            // Single branch: chỉ cần branch_id và branch
            $data['branch_id'] = $this->branch_id;
            $data['branch'] = $this->whenLoaded('branch', function () {
                return $this->branch ? new BranchResource($this->branch) : null;
            });
        } elseif ($scope === Role::SCOPE_MULTIPLE_BRANCHES) {
            // Multiple branches: chỉ cần branches
            $data['branches'] = $this->whenLoaded('branches', function () {
                return BranchResource::collection($this->branches);
            });
        }
        // all_branches (super-admin, accountant): không cần branch data

        return $data;
    }

    /**
     * Lấy branch scope từ role name
     * 
     * @param \App\Models\Role $role
     * @return string
     */
    private function getRoleScope($role): string
    {
        return match($role->name) {
            'super-admin' => Role::SCOPE_ALL_BRANCHES,
            'accountant' => Role::SCOPE_ALL_BRANCHES,
            'sales-staff' => Role::SCOPE_SINGLE_BRANCH,
            'branch-admin' => Role::SCOPE_MULTIPLE_BRANCHES,
            default => Role::SCOPE_SINGLE_BRANCH,
        };
    }
}
