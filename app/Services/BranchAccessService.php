<?php

namespace App\Services;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class BranchAccessService
{
    /**
     * Lấy danh sách branch IDs mà user có quyền truy cập
     * Sử dụng config để xác định cách lấy branch IDs theo role
     */
    public static function getAccessibleBranchIds(?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [];
        }

        // Kiểm tra roles có quyền xem tất cả branches
        $allBranchesRoles = config('roles.all_branches_roles', []);
        if ($user->hasAnyRole($allBranchesRoles)) {
            return Branch::pluck('id')->toArray();
        }

        // Lấy config cho từng role
        $branchAccessConfig = config('roles.branch_access_config', []);
        
        // Lặp qua các roles của user để tìm cách lấy branch IDs
        foreach ($user->roles as $role) {
            $roleName = $role->name;
            
            if (!isset($branchAccessConfig[$roleName])) {
                continue;
            }

            $accessType = $branchAccessConfig[$roleName];

            switch ($accessType) {
                case 'all_branches':
                    return Branch::pluck('id')->toArray();
                    
                case 'user_branches':
                    // Lấy từ relationship many-to-many
                    // Cần select rõ bảng để tránh conflict với pivot table có cùng tên cột 'id'
                    return $user->branches()->select('branches.id')->pluck('id')->toArray();
                    
                case 'user_branch_id':
                    // Lấy từ field branch_id của user
                    if ($user->branch_id) {
                        return [$user->branch_id];
                    }
                    break;
                    
                default:
                    // Có thể mở rộng thêm các loại khác nếu cần
                    break;
            }
        }
        
        return [];
    }
    
    /**
     * Kiểm tra user có quyền với branch không
     */
    public static function hasAccessToBranch(int $branchId, ?User $user = null): bool
    {
        $accessibleBranchIds = self::getAccessibleBranchIds($user);
        return in_array($branchId, $accessibleBranchIds);
    }
    
    /**
     * Kiểm tra user có quyền xem tất cả branches không
     */
    public static function canViewAllBranches(?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        $allBranchesRoles = config('roles.all_branches_roles', []);
        return $user->hasAnyRole($allBranchesRoles);
    }
    
    /**
     * Apply branch filter vào query builder
     */
    public static function applyBranchFilter($query, string $branchColumn = 'branch_id', ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            $query->whereRaw('1 = 0'); // Không cho xem gì
            return;
        }
        
        // Nếu user có quyền xem tất cả branches, không cần filter
        if (self::canViewAllBranches($user)) {
            return;
        }
        
        $branchIds = self::getAccessibleBranchIds($user);
        
        if (empty($branchIds)) {
            $query->whereRaw('1 = 0'); // Không có branch nào
            return;
        }
        
        $query->whereIn($branchColumn, $branchIds);
    }
}

