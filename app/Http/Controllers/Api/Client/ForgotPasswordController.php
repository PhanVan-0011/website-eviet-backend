<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\AuthUserService;
use App\Services\OtpService;
use App\Http\Requests\Api\Auth\ForgotPassWord\ForgotPasswordInitiateRequest;
use App\Http\Requests\Api\Auth\ForgotPassWord\ForgotPasswordVerifyOtpRequest;
use App\Http\Requests\Api\Auth\ForgotPassWord\ForgotPasswordCompleteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected $authUserService;
    protected $otpService;

    public function __construct(AuthUserService $authUserService, OtpService $otpService)
    {
        $this->authUserService = $authUserService;
        $this->otpService = $otpService;
    }

    /**
     * Bước 1: Nhận SĐT, kiểm tra, gửi OTP và trả về token tạm thời.
     * URL: POST /api/password/forgot/initiate
     */
    public function initiate(ForgotPasswordInitiateRequest $request): JsonResponse
    {
        try {
            $phone = $request->validated()['phone'];
            $this->otpService->generateAndSendOtp($phone, 'reset_password');

            // Tạo token tạm thời và lưu SĐT vào cache
            $token = Str::random(40);
            Cache::put('reset_token:' . $token, $phone, now()->addMinutes(10)); // Token có hiệu lực 10 phút

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi.',
                'reset_token' => $token,
                'token_type' => 'Bearer'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi khởi tại nhập số điện thoại khi quên mật khẩu: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể bắt đầu quá trình đặt lại mật khẩu.'], 500);
        }
    }

    /**
     * Bước 2: Xác thực OTP và trả về token hoàn tất.
     * URL: POST /api/password/forgot/verify-otp
     */
    public function verifyOtp(ForgotPasswordVerifyOtpRequest $request): JsonResponse
    {
        $resetToken = $request->bearerToken();
        $phone = Cache::get('reset_token:' . $resetToken);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $otp = $request->validated()['otp'];
        $isValid = $this->otpService->verifyOtp($phone, $otp, 'reset_password');

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'], 422);
        }

        // Xóa token cũ và tạo token mới cho bước hoàn tất
        Cache::forget('reset_token:' . $resetToken);
        $completionToken = Str::random(40);
        Cache::put('completion_token_reset:' . $completionToken, $phone, now()->addMinutes(4));

        return response()->json([
            'success' => true,
            'message' => 'Xác thực OTP thành công.',
            'completion_token' => $completionToken,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Bước 3: Hoàn tất việc đặt lại mật khẩu.
     * URL: POST /api/password/forgot/complete
     */
   public function complete(ForgotPasswordCompleteRequest $request): JsonResponse
    {
        $completionToken = $request->bearerToken();
        $phone = Cache::get('completion_token_reset:' . $completionToken);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phiên làm việc không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $data = $request->validated();
        $data['phone'] = $phone; // Thêm SĐT vào dữ liệu

        $this->authUserService->resetPassword($data);
        Cache::forget('completion_token_reset:' . $completionToken); // Xóa token sau khi hoàn tất

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập lại.',
        ], 200);
    }
}
