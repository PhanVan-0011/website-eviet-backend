<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\OtpVerification;
use Carbon\Carbon;

class OtpVerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Xóa dữ liệu cũ để tránh trùng lặp
        OtpVerification::truncate();

        // Tạo một mã OTP mẫu còn hạn sử dụng
        OtpVerification::create([
            'phone' => '0905123456',
            'otp_code' => '123456',
            'purpose' => 'login',
            'expire_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        // Tạo một mã OTP mẫu đã hết hạn
        OtpVerification::create([
            'phone' => '0905111222',
            'otp_code' => '000000',
            'purpose' => 'login',
            'expire_at' => Carbon::now()->subMinute(),
            'used' => false,
        ]);

        // Tạo một mã OTP mẫu đã được sử dụng
        OtpVerification::create([
            'phone' => '0905333444',
            'otp_code' => '111111',
            'purpose' => 'login',
            'expire_at' => Carbon::now()->addMinutes(5),
            'used' => true,
        ]);
    }
}
