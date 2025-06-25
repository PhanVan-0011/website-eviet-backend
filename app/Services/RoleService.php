<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Role;

class RoleService
{
    /**
     * Lấy danh sách vai trò đã được lọc và phân trang thủ công.
     */
    public function getAllRoles(Request $request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Role::query()
                ->where('name', '!=', 'super-admin')
                  ->with(['permissions'])
                  ->withCount('users');

            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('display_name', 'like', "%{$keyword}%");
                });
            }

            // Lọc theo quyền hạn
            if ($request->filled('permission_name')) {
                $permissionName = $request->input('permission_name');
                $query->whereHas('permissions', function ($q) use ($permissionName) {
                    $q->where('name', $permissionName);
                });
            }

            // Sắp xếp theo tên
            $query->orderBy('name', 'asc');

            // Tính tổng số bản ghi
            $total = $query->count();

            // Phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $roles = $query->skip($offset)->take($perPage)->get();

            // Tính toán thông tin phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về kết quả dưới dạng mảng phẳng, đúng theo mẫu của bạn
            return [
                'data' => $roles,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi lấy danh sách vai trò: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getRoleById(int $id): Role
    {
        try {
            return Role::with('permissions')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy vai trò với ID {$id}.");
            throw $e;
        }
    }
    /**
     * Tạo một vai trò mới và gán các quyền hạn.
     */
    public function createRole(array $data): Role
    {
        try {
            return DB::transaction(function () use ($data) {
                $role = Role::create([
                    'name' => $data['name'],
                    'display_name' => $data['display_name'],
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
               $role->update($data);
                if (array_key_exists('permissions', $data)) {
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
        try {
            if ($role->name === 'super-admin') {
                throw new Exception("Không thể xóa vai trò Super Admin.");
            }
            if ($role->users()->count() > 0) {
                throw new Exception("Không thể xóa vai trò vì đang có người dùng được gán.");
            }
            $role->delete();
        } catch (Exception $e) {
            Log::error("Lỗi Service khi xóa vai trò ID: {$role->id}", [
                'message' => $e->getMessage(),
                'role' => $role->toArray()
            ]);
            throw $e;
        }
    }

    /**
     * Xóa nhiều vai trò một cách an toàn.
     */
    public function deleteMultipleRoles(array $roleIds): array
    {
        try {
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
        } catch (Exception $e) {
            Log::error("Lỗi Service khi xóa nhiều vai trò", [
                'role_ids' => $roleIds,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
