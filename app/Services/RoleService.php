<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Role;

class RoleService
{
    /**
     * Tạo một vai trò mới và gán các quyền hạn.
     */
    public function createRole(array $data): Role
    {
        try {
            return DB::transaction(function () use ($data) {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'api' // Bắt buộc nếu dùng Sanctum!
                ]);
                if (!empty($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }
                return $role->load('permissions');
            });
        } catch (Exception $e) {
            Log::error('Lỗi Service khi tạo vai trò:', ['message' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Cập nhật một vai trò và đồng bộ hóa các quyền hạn.
     */
    public function updateRole(Role $role, array $data): Role
    {
        try {
            return DB::transaction(function () use ($role, $data) {
                //$role->update(['name' => $data['name']]);
                if (isset($data['name'])) {
                    $role->update(['name' => $data['name']]);
                }
                if (isset($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }
                return $role->load('permissions');
            });
        } catch (Exception $e) {
            Log::error("Lỗi Service khi cập nhật vai trò ID: {$role->id}", ['message' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Xóa một vai trò một cách an toàn.
     */
    public function deleteRole(Role $role): void
    {
        if ($role->name === 'super-admin') {
            throw new Exception("Không thể xóa vai trò Super Admin.");
        }
        if ($role->users()->count() > 0) {
            throw new Exception("Không thể xóa vai trò vì đang có người dùng được gán.");
        }
        $role->delete();
    }

    /**
     * Xóa nhiều vai trò một cách an toàn.
     */
    public function deleteMultipleRoles(array $roleIds): array
    {
        $deletedCount = 0;
        $failedRoles = [];

        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            try {
                //Xóa một vai trò đã được viết sẵn
                $this->deleteRole($role);
                $deletedCount++;
            } catch (Exception $e) {
                // Nếu có lỗi, ghi nhận lại để báo cáo
                $failedRoles[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return [
            'success_count' => $deletedCount,
            'failed_roles' => $failedRoles,
        ];
    }
}
