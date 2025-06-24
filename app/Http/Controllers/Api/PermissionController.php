<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Permission\AssignRoleRequest;
use App\Http\Requests\Api\Permission\AssignPermissionRequest;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Lấy danh sách tất cả các quyền hạn, đã được gom nhóm.
     */
    public function index(): JsonResponse
    {
        try {
            $groupedPermissions = $this->permissionService->getGroupedPermissions();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách quyền hạn thành công.',
                'data' => $groupedPermissions
            ], 200);
        } catch (Exception $e) {
            Log::error("Lỗi khi lấy danh sách quyền hạn: ", ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra.'
            ], 500);
        }
    }

    /**
     * Gán vai trò cho user (thay thế toàn bộ vai trò cũ).
     */
    public function assignRolesToUser(AssignRoleRequest $request, $id)
    {
        try {
            $user = $this->permissionService->assignRolesToUser($id, $request->input('roles'));
            return response()->json([
                'success' => true,
                'message' => 'Gán vai trò cho người dùng thành công.',
                'roles' => $user->getRoleNames(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng.',
            ], 404);
        }
    }

    /**
     * Gán quyền riêng cho user (thay thế toàn bộ quyền riêng cũ).
     */
    public function assignPermissionsToUser(AssignPermissionRequest $request, $id)
    {
        try {
            $user = $this->permissionService->assignPermissionsToUser($id, $request->input('permissions'));
            return response()->json([
                'success' => true,
                'message' => 'Gán quyền riêng cho người dùng thành công.',
                'permissions' => $user->getPermissionNames(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng.',
            ], 404);
        }
    }
}
