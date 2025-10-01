<?php

namespace App\Services;

use App\Interfaces\SmsServiceInterface;
use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected $smsService;

    public function __construct(SmsServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }

    public function generateAndSendOtp(string $phone, string $purpose): OtpVerification
    {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpVerification = OtpVerification::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'purpose' => $purpose,
            'expire_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        // Log mã OTP để test trên front-end
        Log::info("OTP Code for testing: {$otpCode} - Phone: {$phone} - Purpose: {$purpose}");

        $message = "Ma xac thuc OTP cua ban la: " . $otpCode;
        if (!$this->smsService->send($phone, $message)) {
            throw new \Exception("Không thể gửi tin nhắn OTP vào lúc này.");
        }
        return $otpVerification;
    }

    public function verifyOtp(string $phone, string $otpCode, string $purpose): bool
    {
        Log::info("Verifying OTP: {$otpCode} - Phone: {$phone} - Purpose: {$purpose}");

        $otpRecord = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expire_at', '>', Carbon::now()) // Giữ lại kiểm tra thời gian
            ->latest()
            ->first();

        // Nếu không tìm thấy bản ghi hợp lệ
        if (!$otpRecord) {
            Log::warning("OTP verification failed: No valid record found for phone {$phone} and purpose {$purpose}");
            return false;
        }

        // SỬA LỖI: Chuyển đổi (cast) mã OTP trong DB thành chuỗi trước khi so sánh
        if ((string)$otpRecord->otp_code !== $otpCode) {
            Log::warning("OTP verification failed: Code mismatch. Expected: {$otpRecord->otp_code}, Received: {$otpCode}");
            return false;
        }

        // Nếu mọi thứ đều khớp, đánh dấu đã sử dụng và trả về true
        $otpRecord->used = true;
        $otpRecord->save();

        Log::info("OTP verification successful for phone {$phone} and purpose {$purpose}");

        return true;
    }
}
