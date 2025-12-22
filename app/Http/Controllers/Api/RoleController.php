<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Models\Role;
use App\Http\Requests\Api\Role\StoreRoleRequest;
use App\Http\Requests\Api\Role\UpdateRoleRequest;
use App\Http\Requests\Api\Role\GetRolesRequest;
use App\Http\Requests\Api\Role\MultiDeleteRoleRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\RoleResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }


    public function index(GetRolesRequest $request)
    {
        try {
            $data = $this->roleService->getAllRoles($request);
            return response()->json([
                'success' => true,
                'data' => RoleResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'prev_page' => $data['prev_page'],
                ],
                'message' => 'Lấy danh sách vai trò thành công',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách vai trò',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function store(StoreRoleRequest $request)
    {
        try {
            $role = $this->roleService->createRole($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo vai trò thành công.',
                'data' => new RoleResource($role)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi tạo vai trò.'
            ], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $role = $this->roleService->getRoleById($id);
            return response()->json([
                'success' => true,
                'data' => new RoleResource($role),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy vai trò với ID {$id}."
            ], 404);
        }
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $updatedRole = $this->roleService->updateRole($role, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật vai trò thành công.',
                'data' => new RoleResource($updatedRole)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy vai trò với ID {$id}.",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi cập nhật vai trò.'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            // Kiểm tra quyền xóa (middleware đã check, nhưng để đảm bảo an toàn)
            if (!auth()->user()->can('roles.delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa vai trò.'
                ], 403);
            }

            $role = Role::findOrFail($id);
            $this->roleService->deleteRole($role);
            return response()->json([
                'success' => true,
                'message' => 'Xóa vai trò thành công.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Vai trò với ID {$id} không tồn tại.",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function multiDelete(MultiDeleteRoleRequest $request)
    {
        try {
            // Kiểm tra quyền xóa (middleware đã check, nhưng để đảm bảo an toàn)
            if (!auth()->user()->can('roles.delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa vai trò.'
                ], 403);
            }

            $roleIds = $request->validated()['role_ids'];
            $result = $this->roleService->deleteMultipleRoles($roleIds);

            $successCount = $result['success_count'] ?? 0;
            $failedCount = count($result['failed_roles'] ?? []);

            $message = '';
            if ($successCount > 0) {
                $message .= "Xóa thành công: {$successCount} vai trò.";
            }
            if ($failedCount > 0) {
                $message .= " Thất bại: {$failedCount} vai trò.";
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message ?: 'Không xóa được vai trò nào.',
                'details' => $result
            ], $successCount > 0 ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra.'
            ], 500);
        }
    }
}
