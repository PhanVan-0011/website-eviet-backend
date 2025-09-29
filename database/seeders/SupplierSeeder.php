<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
        Supplier::truncate();

        // Lấy ID người dùng đầu tiên (ví dụ: Super Admin)
        $firstUserId = User::orderBy('id')->first()->id ?? 1;

        // Lấy ID của các nhóm
        $groupIds = SupplierGroup::pluck('id')->toArray();

        $suppliers = [
            [
                'code' => 'NCC001',
                'name' => 'CTY TMDV QUỐC HUẤN',
                'group_id' => $groupIds[0] ?? null,
                'phone_number' => '0901234567',
                'email' => 'quochuan@gmail.com',
                'tax_code' => '0312345678',
                'is_active' => true,
                'user_id' => $firstUserId,
            ],
            [
                'code' => 'NCC002',
                'name' => 'GẠO ANH NGUYÊN',
                'group_id' => $groupIds[1] ?? null,
                'phone_number' => '0919876543',
                'email' => 'gaoanhnguyen@gmail.com',
                'tax_code' => '0398765432',
                'is_active' => true,
                'user_id' => $firstUserId,
            ],
            [
                'code' => 'NCC003',
                'name' => 'THỰC PHẨM THANH HẰNG',
                'group_id' => $groupIds[2] ?? null,
                'phone_number' => '0977445566',
                'email' => 'thanhhang@gmail.com',
                'tax_code' => '0355443322',
                'is_active' => false,
                'user_id' => $firstUserId,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
