<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


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
    // done
    public function index(Request $request)
    {
        try {
            return $this->userService->getAllUsers($request);
        } catch (\Exception $e) {

            error_log('Lỗi khi lấy danh sách người dùng: ' . $e->getMessage());
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
            return new UserResource($user);
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
    public function store(UserRequest $request)
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
    public function update(UserRequest $request, $id)
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());
            return new UserResource($user);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc số điện thoại đã tồn tại'
            ], 409);
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
    public function multiDelete(Request $request)
    {
        try {


            $deletedCount = $this->userService->multiDelete($request->ids);

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
