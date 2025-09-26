<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Vô hiệu hóa foreign key checks để tránh lỗi khi seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Sử dụng cách gọi đúng
        
        // Xóa tất cả các bản ghi hiện có
        Branch::truncate();

        // Tạo dữ liệu giả
        $branches = [
            [
                'code' => 'KOLON01',
                'name' => 'Chi nhánh KOLON 01',
                'address' => '123 Đường 30 Tháng 4, P. Chánh Nghĩa, TP. Thủ Dầu Một, Bình Dương',
                'phone_number' => '0987654321',
                'email' => 'kolon1@eviet.com',
                'active' => true,
            ],
            [
                'code' => 'KOLON02',
                'name' => 'Chi nhánh KOLON 02',
                'address' => '456 Đường ĐT743, P. An Phú, TP. Dĩ An, Bình Dương',
                'phone_number' => '0912345678',
                'email' => 'kolon2@eviet.com',
                'active' => true,
            ],
            [
                'code' => 'KOLON03',
                'name' => 'Chi nhánh KOLON 03',
                'address' => '789 Đường Nguyễn Trãi, P. Lái Thiêu, TP. Thuận An, Bình Dương',
                'phone_number' => '0901122334',
                'email' => 'kolon3@eviet.com',
                'active' => false,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }

        // Kích hoạt lại foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Sử dụng cách gọi đúng
    }
}
