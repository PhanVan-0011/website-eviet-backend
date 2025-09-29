<?php

namespace Database\Seeders;

use App\Models\SupplierGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tắt kiểm tra khóa ngoại để tránh lỗi khi TRUNCATE
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
        SupplierGroup::truncate();

        $groups = [
            ['name' => 'BÌNH DƯƠNG', 'description' => 'Các nhà cung cấp thịt, rau, củ, quả.'],
            ['name' => 'LONG AN', 'description' => 'Các nhà cung cấp nước ngọt, bia, sữa.'],
            ['name' => 'VŨNG TAU', 'description' => 'Các nhà cung cấp giấy, ống hút, hộp đựng.'],
        ];

        foreach ($groups as $group) {
            SupplierGroup::create($group);
        }

        // Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
