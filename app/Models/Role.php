<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
class Role extends SpatieRole
{
    // Scope constants
    const SCOPE_ALL_BRANCHES = 'all_branches';        // Tự động chọn tất cả branches
    const SCOPE_SINGLE_BRANCH = 'single_branch';      // Chỉ được chọn 1 branch
    const SCOPE_MULTIPLE_BRANCHES = 'multiple_branches'; // Chọn nhiều branches (ít nhất 1)

    protected $fillable = [
        'name',
        'guard_name',
        'display_name', 
    ];

    /**
     * Lấy branch scope của role
     * Sử dụng config để map role với scope, dễ mở rộng khi thêm role mới
     * 
     * @return string
     */
    public function getBranchScope(): string
    {
        $branchAccessConfig = config('roles.branch_access_config', []);
        $allBranchesRoles = config('roles.all_branches_roles', []);
        
        $roleName = $this->name;
        
        // Kiểm tra nếu role có quyền xem tất cả branches
        if (in_array($roleName, $allBranchesRoles)) {
            return self::SCOPE_ALL_BRANCHES;
        }
        
        // Map từ config
        $accessType = $branchAccessConfig[$roleName] ?? null;
        
        return match($accessType) {
            'all_branches' => self::SCOPE_ALL_BRANCHES,
            'user_branch_id' => self::SCOPE_SINGLE_BRANCH,
            'user_branches' => self::SCOPE_MULTIPLE_BRANCHES,
            default => self::SCOPE_SINGLE_BRANCH, // Default là single branch
        };
    }
}
