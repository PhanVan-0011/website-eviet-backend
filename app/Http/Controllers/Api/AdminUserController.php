<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminUserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Api\Admin\StoreAdminUserRequest;
use App\Http\Requests\Api\Admin\UpdateAdminUserRequest;
use App\Http\Requests\Api\Admin\MultiDeleteAdminUserRequest;
use App\Http\Resources\AdminUserResource;

class AdminUserController extends Controller
{
    protected AdminUserService $userService;

    public function __construct(AdminUserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Lấy danh sách nhân viên.
     */
    public function index(Request $request)
    {
        try {
            $result = $this->userService->getAdminUsers($request);

            return response()->json([
                'success' => true,
                'data' => AdminUserResource::collection($result['data']),
                'page' => $result['page'],
                'total' => $result['total'],
                'last_page' => $result['last_page'],
                'next_page' => $result['next_page'],
                'prev_page' => $result['prev_page'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách nhân viên.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Lấy danh sách nhân viên trong thùng rác.
     */
    public function trash(Request $request)
    {
        try {
            $result = $this->userService->getTrashedAdminUsers($request);

            // Cấu trúc response được cập nhật để giống với hàm index
            return response()->json([
                'success' => true,
                'data' => AdminUserResource::collection($result['data']),
                'page' => $result['page'],
                'total' => $result['total'],
                'last_page' => $result['last_page'],
                'next_page' => $result['next_page'],
                'prev_page' => $result['prev_page'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách nhân viên trong thùng rác.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Tạo mới một nhân viên.
     */
    public function store(StoreAdminUserRequest $request)
    {
        try {
            $user = $this->userService->createAdminUser($request->validated());
            return (new AdminUserResource($user->load('roles')))
                ->additional([
                    'success' => true,
                    'message' => 'Tạo tài khoản quản trị thành công.',
                ])
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi khi tạo tài khoản quản trị: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Đã có lỗi xảy ra trong quá trình tạo tài khoản.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết của một nhân viên.
     */
    public function show(int $id)
    {
        try {
            $user = $this->userService->findAdminUserById($id);
            return new AdminUserResource($user);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy tài khoản quản trị với ID {$id}."
            ], 404);
        }
    }

    /**
     * Cập nhật thông tin một nhân viên.
     */
    public function update(UpdateAdminUserRequest $request, int $id)
    {
        try {
            $user = $this->userService->findAdminUserById($id);
            $updatedUser = $this->userService->updateAdminUser($user, $request->validated());

            return (new AdminUserResource($updatedUser))
                ->additional([
                    'success' => true,
                    'message' => 'Cập nhật tài khoản thành công.'
                ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => "Không tìm thấy người dùng với ID {$id}."], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa một nhân viên.
     */
    public function destroy(int $id)
    {
        try {
            $user = $this->userService->findAdminUserById($id);
            $this->userService->deleteAdminUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển nhân viên vào thùng rác.'
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy người dùng với ID {$id}."
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Xóa nhiều nhân viên.
     */
    public function multiDelete(MultiDeleteAdminUserRequest $request)
    {
        try {
            $result = $this->userService->multiDeleteAdminUsers($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã chuyển {$result['deleted_count']} nhân viên vào thùng rác.",
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Phục hồi một nhân viên đã xóa.
     */
    public function restore(int $id)
    {
        try {
            $user = $this->userService->restoreAdminUser($id);
            return response()->json([
                'success' => true,
                'message' => "Đã phục hồi thành công nhân viên: {$user->name}.",
                'data' => new AdminUserResource($user)
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy nhân viên đã xóa với ID {$id} trong thùng rác."
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra.'
            ], 500);
        }
    }
    /**
     * Xóa vĩnh viễn một nhân viên.
     */
    public function forceDelete(int $id)
    {
        try {
            $this->userService->forceDeleteAdminUser($id);
            return response()->json(['success' => true, 'message' => 'Đã xóa vĩnh viễn nhân viên khỏi hệ thống.']);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => "Không tìm thấy nhân viên đã xóa với ID {$id} trong thùng rác."], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
