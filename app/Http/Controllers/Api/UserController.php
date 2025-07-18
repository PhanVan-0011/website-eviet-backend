<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Api\User\MultiDeleteUserRequest;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;


class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        error_log("=== UserController được khởi tạo ===");
        $this->userService = $userService;
    }

    /**
     * Lấy danh sách tất cả users
     */
    public function index(Request $request)
    {
        try {
            $result = $this->userService->getAllUsers($request);
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách người dùng thành công.',
                'data' => UserResource::collection($result['data']),
                'pagination' => [
                    'page' => $result['page'],
                    'total' => $result['total'],
                    'last_page' => $result['last_page'],
                    'next_page' => $result['next_page'],
                    'pre_page' => $result['pre_page'],
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách người dùng'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một user
     */
    public function show($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin người dùng thành công',
                'data' => new UserResource($user)
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin người dùng'
            ], 500);
        }
    }

    /**
     * Tạo mới user
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo người dùng thành công',
                'data' => new UserResource($user)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo người dùng'
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin user
     */
    public function update(UpdateUserRequest $request, $id)
    {
        error_log(json_encode($request->all()));
        try {
            $user = $this->userService->updateUser($request->validated(), $id);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin người dùng thành công',
                'data' => new UserResource($user)
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật người dùng'
            ], 500);
        }
    }

    /**
     * Xóa user
     */
    public function destroy($id)
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa người dùng thành công'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa người dùng'
            ], 500);
        }
    }

    /**
     * Xóa nhiều user cùng lúc
     */
    public function multiDelete(MultiDeleteUserRequest $request)
    {
        try {

            $deletedCount = $this->userService->multiDelete($request->validated()['ids']);

            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} người dùng"
            ]);
        } catch (ModelNotFoundException $e) {
            error_log('Lỗi 404: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa người dùng'
            ], 500);
        }
    }
}
