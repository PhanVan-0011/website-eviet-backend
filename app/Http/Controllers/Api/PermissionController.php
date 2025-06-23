<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

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
            ],200);

        } catch (Exception $e) {
            Log::error("Lỗi khi lấy danh sách quyền hạn: ", ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra.'
            ], 500);
        }
    }
}
