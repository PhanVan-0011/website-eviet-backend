<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Services\AuthUserService;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use App\Http\Requests\Api\Auth\LoginApp\LoginAppRequest;




class AuthController extends Controller
{
    protected $authUserService;
    protected $otpService;

    public function __construct(AuthUserService $authUserService, OtpService $otpService)
    {
        $this->authUserService = $authUserService;
        $this->otpService = $otpService;
    }
    
    /**
     * Đăng nhập người dùng admin.
     *
     * @param \App\Http\Requests\Api\Auth\LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    //Login
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authUserService->login($request->login, $request->password);

            if ($result instanceof User) {
                $token = $result->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Đăng nhập thành công',
                    'data' => [
                        'user' => new UserResource($result),
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ],
                ], 200);
            }

            // Nếu không phải là User, xử lý từng lỗi
            $isLocked = $result === 'locked';
            $isEmail = filter_var($request->login, FILTER_VALIDATE_EMAIL);

            return response()->json([
                'success' => false,
                'message' => $isLocked
                    ? 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
                    : ($isEmail
                        ? 'Email hoặc mật khẩu không đúng'
                        : 'Số điện thoại hoặc mật khẩu không đúng'),
            ], 401);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi đăng xuất.',
            ], 500);
        }
    }
    

    /**
     * Đăng nhập bằng SĐT và Mật khẩu.
     * URL: POST /api/auth/login
     */
     public function loginApp(LoginAppRequest $request)
    {
        $user = $this->authUserService->login($request->validated()['phone'], $request->validated()['password']);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Số điện thoại hoặc mật khẩu không chính xác.'], 401);
        }
        
        if ($user === 'locked') {
             return response()->json(['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
