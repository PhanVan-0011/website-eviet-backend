<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;
use App\Models\User;
use App\Services\SmsService;
use App\Models\otpVerification;
use Illuminate\Support\Facades\Log;
use App\Services\AuthUserService;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    protected $authUserService;

    public function __construct(AuthUserService $authUserService)
    {
        $this->authUserService = $authUserService;
    }
     /**
     * Đăng ký người dùng.
     *
     * @param \App\Http\Requests\Api\Auth\RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authUserService->register($request->validated());
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (QueryException $e) {
            Log::error('Error during registration: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc số điện thoại đã tồn tại',
            ], 409);
        } catch (\Throwable $e) {
            Log::error('Unexpected error during registration: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.',
            ], 500);
        }
    }
    /**
     * Đăng nhập người dùng.
     *
     * @param \App\Http\Requests\Api\Auth\LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    //Login
    public function login(LoginRequest $request)
    {
        try {
            $user = $this->authUserService->login($request->login, $request->password);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => filter_var($request->login, FILTER_VALIDATE_EMAIL)
                        ? 'Email hoặc mật khẩu không đúng'
                        : 'Số điện thoại hoặc mật khẩu không đúng',
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error during login: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.',
            ], 500);
        }
    }
     /**
     * Đăng xuất người dùng.
     *
     * @param \App\Http\Requests\Api\Auth\LogoutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(LogoutRequest $request)
    {
        try {
            $isLoggedOut = $this->authUserService->logout($request);

            return response()->json([
                'success' => true,
                'message' => $isLoggedOut ? 'Đăng xuất thành công' : 'Đã đăng xuất hoặc không có token hợp lệ',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (QueryException $e) {
            Log::error('Database error during logout: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Error during logout: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi đăng xuất.',
            ], 500);
        }
    }
    /**
     * Lấy thông tin chi tiết của người dùng hiện tại.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $this->authUserService->getUserProfile($request->user());
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng.',
                    'data' => null,
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin người dùng thành công',
                'data' => new UserResource($user),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (QueryException $e) {
            Log::error('Database error fetching user info: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Error fetching user info: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy thông tin người dùng.',
            ], 500);
        }
    }
    /**
     * Cập nhật thông tin hồ sơ người dùng.
     *
     * @param \App\Http\Requests\Api\Auth\UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_profile(UpdateProfileRequest $request)
    {
        try {
            $user = $this->authUserService->updateProfile($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công',
                'data' => new UserResource($user),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (QueryException $e) {
            Log::error('Database error updating user profile: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng kiểm tra dữ liệu gửi lên.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Error updating user profile: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi cập nhật thông tin người dùng.',
            ], 500);
        }
    }
    /**
     * Lấy danh sách người dùng với phân trang và tìm kiếm.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request)
    {
        try {
            $users = $this->authUserService->getUsers($request);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách người dùng thành công.',
                'data' => UserResource::collection($users->items()),
                'pagination' => [
                    'page' => $users->currentPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                    'next_page' => $users->nextPageUrl() ? $users->currentPage() + 1 : null,
                    'pre_page' => $users->previousPageUrl() ? $users->currentPage() - 1 : null,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching user list: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy danh sách người dùng.',
            ], 500);
        }
    }

    /**
     * Xóa một người dùng theo ID.
     *
     * @param int $id ID của người dùng
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUser($id)
    {
        try {
            $isDeleted = $this->authUserService->deleteUser($id);

            if (!$isDeleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không tồn tại.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa người dùng thành công.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error deleting user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa người dùng.',
            ], 500);
        }
    }

    /**
     * Đặt lại mật khẩu bằng số điện thoại - Bước 1: Gửi OTP xác minh.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestResetPasswordByPhone(Request $request)
    {
        try {
            $result = $this->authUserService->requestResetPassword($request->phone);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => ['phone' => $result['phone']],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error sending OTP for password reset: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.',
            ], 500);
        }
    }

    /**
     * Đặt lại mật khẩu bằng số điện thoại - Bước 2: Xác minh OTP và đặt lại mật khẩu.
     *
     * @param \App\Http\Requests\Api\Auth\ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPasswordByPhone(ResetPasswordRequest $request)
    {
        try {
            $result = $this->authUserService->resetPassword(
                $request->phone,
                $request->otp_code,
                $request->new_password
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (QueryException $e) {
            Log::error('Database error resetting password: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Error resetting password: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.',
            ], 500);
        }
    }
}
