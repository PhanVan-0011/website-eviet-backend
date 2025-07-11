<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use Illuminate\Database\QueryException;
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
    
}
