<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Models\Role;
use App\Http\Requests\Api\Role\StoreRoleRequest;
use App\Http\Requests\Api\Role\UpdateRoleRequest;
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

    public function index()
    {
        $roles = Role::where('name', '!=', 'super-admin')->with('permissions')->get();
        return RoleResource::collection($roles)->response();
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
            Log::error('Lỗi Controller khi tạo vai trò:', ['message' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi tạo vai trò.'
        ], 500);
        }
    }

    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role){
        try {
            // dd([
            //     'user' => auth()->user()->email,
            //     'permissions' => auth()->user()->getAllPermissions()->pluck('name')
            // ]);
            $updatedRole = $this->roleService->updateRole($role, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật vai trò thành công.',
                'data' => new RoleResource($updatedRole)]);
        } catch (\Exception $e) {
            Log::error("Lỗi Controller khi cập nhật vai trò ID: {$role->id}", ['message' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi cập nhật vai trò.'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $role = Role::findOrFail($id);
            $this->roleService->deleteRole($role);
            return response()->json([
                'success' => true,
                'message' => 'Xóa vai trò thành công.']);
        }catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Vai trò với ID {$id} không tồn tại.",
            ], 404);

        } catch (\Exception $e) {
            Log::error("Lỗi Controller khi xóa vai trò ID: {$id}", ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Xóa nhiều vai trò được chọn.
     */
    public function multiDelete(MultiDeleteRoleRequest $request)
    {
        try {
            $result = $this->roleService->deleteMultipleRoles($request->validated()['role_ids']);
            $message = "Xóa thành công: {$result['success_count']} vai trò.";
            if (count($result['failed_roles']) > 0) {
                $message .= " Thất bại: " . count($result['failed_roles']) . " vai trò.";
            }

            return response()->json(['success' => true, 'message' => $message, 'details' => $result]);
        } catch (\Exception $e) {
            Log::error("Lỗi nghiêm trọng khi xóa nhiều vai trò", ['message' => $e->getMessage(), 'ids' => $request->input('role_ids')]);
            return response()->json(['success' => false, 'message' => 'Đã có lỗi hệ thống xảy ra.'], 500);
        }
    }
}
