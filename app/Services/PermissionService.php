<?php

namespace App\Services;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Http\Resources\PermissionResource;
use Illuminate\Support\Collection;
use Exception;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    protected AdminUserService $adminUserService;

    public function __construct(AdminUserService $adminUserService)
    {
        $this->adminUserService = $adminUserService;
    }
    public function getGroupedPermissions(): Collection
    {
        try {
            // Lấy tất cả permissions từ CSDL, sắp xếp theo tên để đảm bảo thứ tự
            $permissions = Permission::orderBy('name')->get();

            //Sử dụng Resource để định dạng lại từng permission
            $formattedCollection = collect(PermissionResource::collection($permissions)->resolve());

            $grouped = $formattedCollection->groupBy('group');

            //Dọn dẹp lại dữ liệu để loại bỏ key 'group' không cần thiết trong kết quả cuối cùng
            $cleanedGrouped = $grouped->map(function ($groupItems) {
                return $groupItems->map(function ($item) {
                    // Tạo một mảng mới chỉ chứa các key cần thiết
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'display_name' => $item['display_name'],
                        'action' => $item['action']
                    ];
                })->values(); // Dùng ->values() để reset keys của mảng thành [0, 1, 2...]
            });

            // 4. Sắp xếp các nhóm theo thứ tự alphabet
            return $cleanedGrouped->sortKeys();
        } catch (Exception $e) {
            Log::error("Lỗi Service khi lấy danh sách quyền hạn:", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Gán vai trò cho user (thay thế toàn bộ vai trò cũ).
     */
    public function assignRolesToUser(int $userId, array $roles)
    {
        $user = \App\Models\User::findOrFail($userId);
        $user->syncRoles($roles);
        
        // Reload user với roles để lấy role đầu tiên
        $user = $user->fresh('roles');
        
        // Xử lý branch assignment dựa trên role được gán
        // Lấy role đầu tiên (hệ thống chỉ hỗ trợ 1 role per user)
        $role = $user->roles->first();
        if ($role) {
            $this->adminUserService->handleBranchAssignmentForRole($user, $role);
            // Reload lại để có thông tin branches mới nhất
            $user = $user->fresh(['roles', 'branches']);
        } else {
            // Nếu không có role, xóa tất cả branches
            $this->adminUserService->handleBranchAssignmentForRole($user, null);
            $user = $user->fresh(['roles', 'branches']);
        }
        
        return $user;
    }

    /**
     * Gán quyền riêng cho user (thay thế toàn bộ quyền riêng cũ).
     */
    public function assignPermissionsToUser(int $userId, array $permissions)
    {
        $user = \App\Models\User::findOrFail($userId);
        $user->syncPermissions($permissions);
        return $user->fresh('permissions');
    }
}
