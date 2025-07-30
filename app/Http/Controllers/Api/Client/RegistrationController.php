<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\AuthUserService;
use App\Services\OtpService;
use App\Http\Requests\Api\Auth\Register\InitiateRegistrationRequest;
use App\Http\Requests\Api\Auth\Register\VerifyRegistrationOtpRequest;
use App\Http\Requests\Api\Auth\Register\CompleteRegistrationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    protected $authUserService;
    protected $otpService;

    public function __construct(AuthUserService $authUserService, OtpService $otpService)
    {
        $this->authUserService = $authUserService;
        $this->otpService = $otpService;
    }

    /**
     * Bước 1: Kiểm tra SĐT, gửi OTP và trả về token tạm thời.
     * URL: POST /api/auth/register/initiate
     */
    public function initiate(InitiateRegistrationRequest $request)
    {
        try {
            $phone = $request->validated()['phone'];
            $this->otpService->generateAndSendOtp($phone, 'register');

            // Tạo token tạm thời và lưu SĐT vào cache
            $token = Str::random(40);
            Cache::put('registration_token:' . $token, $phone, now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi.',
                'registration_token' => $token,
                'token_type' => 'Bearer'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi đăng ký: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể bắt đầu quá trình đăng ký.'], 500);
        }
    }

    /**
     * Bước 2: Xác thực OTP và trả về token hoàn tất.
     * URL: POST /api/auth/register/verify-otp
     */
    public function verifyOtp(VerifyRegistrationOtpRequest $request)
    {
        $registrationToken = $request->bearerToken();
        $phone = Cache::get('registration_token:' . $registrationToken);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phiên đăng ký không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $otp = $request->validated()['otp'];
        $isValid = $this->otpService->verifyOtp($phone, $otp, 'register');

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'], 422);
        }

        // Xóa token cũ và tạo token mới cho bước hoàn tất
        Cache::forget('registration_token:' . $registrationToken);
        $completionToken = Str::random(40);
        Cache::put('completion_token:' . $completionToken, $phone, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'message' => 'Xác thực OTP thành công.',
            'completion_token' => $completionToken,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Bước 3: Hoàn tất hồ sơ và tạo tài khoản.
     * URL: POST /api/auth/register/complete
     */
    public function complete(CompleteRegistrationRequest $request)
    {
        $completionToken = $request->bearerToken();
        $phone = Cache::get('completion_token:' . $completionToken);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phiên đăng ký không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $data = $request->validated();
        $data['phone'] = $phone; // Thêm SĐT vào dữ liệu để tạo người dùng

        $this->authUserService->createUser($data);
        Cache::forget('completion_token:' . $completionToken); // Xóa token sau khi hoàn tất

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công! Vui lòng chuyển đến trang đăng nhập.',
        ], 201);
    }
}