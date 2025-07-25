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
use App\Models\User;

use App\Services\OtpService;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;

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
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc số điện thoại đã tồn tại',
            ], 409);
        } catch (\Throwable $e) {
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
     * Gửi mã OTP đến số điện thoại để đăng nhập/đăng ký.
     */
    public function sendOtp(SendOtpRequest $request)
    {
        try {
            $this->otpService->generateAndSendOtp($request->validated()['phone'], 'login');
            return response()->json(['success' => true, 'message' => 'Mã OTP đã được gửi đến số điện thoại của bạn.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send OTP Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể gửi mã OTP vào lúc này.'], 500);
        }
    }

    /**
     * Xác thực OTP và tiến hành đăng nhập hoặc đăng ký.
     */
    public function verifyOtpAndLogin(VerifyOtpRequest $request)
    {
        $validated = $request->validated();
        $isValid = $this->otpService->verifyOtp($validated['phone'], $validated['otp'], 'login');

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'], 422);
        }

        try {
            $user = $this->authUserService->findOrCreateUserAfterOtp($validated['phone']);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Xác thực thành công!',
                'data' => [
                    'user' => new \App\Http\Resources\UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }
}
