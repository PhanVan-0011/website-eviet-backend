<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Thanh toán khi nhận hàng (COD)',
                'code' => 'cod',
                'description' => 'Thanh toán tiền mặt trực tiếp cho nhân viên giao hàng.',
                'logo_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Cổng thanh toán VNPAY',
                'code' => 'vnpay',
                'description' => 'Thanh toán qua ứng dụng ngân hàng hỗ trợ VNPAY-QR.',
                'logo_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Ví điện tử MoMo',
                'code' => 'momo',
                'description' => 'Thanh toán an toàn qua ví MoMo.',
                'logo_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Ví điện tử ZaloPay',
                'code' => 'zalopay',
                'description' => 'Thanh toán an toàn qua ví ZaloPay',
                'logo_url' => null,
                'is_active' => true,
            ],
        ];
        foreach ($data as $item) {
            PaymentMethod::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
