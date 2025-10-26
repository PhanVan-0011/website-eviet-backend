<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
         //Cập nhật bảng combos
        Schema::table('combos', function (Blueprint $table) {
            // Thêm cột combo_code nếu chưa tồn tại
            if (!Schema::hasColumn('combos', 'combo_code')) {
                $table->string('combo_code', 50)->nullable()->unique()->after('slug')->comment('Mã combo duy nhất (tùy chọn, có thể tự sinh)');
            }
            // Thêm cột applies_to_all_branches nếu chưa tồn tại
            if (!Schema::hasColumn('combos', 'applies_to_all_branches')) {
                $table->boolean('applies_to_all_branches')->default(false)->after('is_active')->comment('Áp dụng cho tất cả chi nhánh hay chỉ chi nhánh được chọn');
            }
            // Xóa cột slug nếu tồn tại
            if (Schema::hasColumn('combos', 'slug')) {
                 // Cần xóa index unique trước khi xóa cột
                try {
                    $indexes = DB::select("SHOW INDEX FROM combos WHERE Key_name = 'combos_slug_unique'");
                    if (!empty($indexes)) {
                        $table->dropUnique('combos_slug_unique');
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not drop unique index for slug during up migration: ' . $e->getMessage());
                }
                $table->dropColumn('slug');
            }
        });

        //Tạo bảng branch_combo nếu chưa tồn tại
        if (!Schema::hasTable('branch_combo')) {
            Schema::create('branch_combo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade');
                $table->boolean('is_active')->default(true)->comment('Cho phép bán combo này tại chi nhánh này hay không');
                $table->timestamps();

                // Đảm bảo không có cặp branch_id và combo_id trùng lặp
                $table->unique(['branch_id', 'combo_id'], 'branch_combo_unique');
            });
        }
    }

 /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Xóa bảng branch_combo trước (vì có khóa ngoại)
        Schema::dropIfExists('branch_combo');

        // 2. Xóa các cột đã thêm vào bảng combos (vẫn giữ kiểm tra tồn tại)
        Schema::table('combos', function (Blueprint $table) {
             if (Schema::hasColumn('combos', 'applies_to_all_branches')) {
                $table->dropColumn('applies_to_all_branches');
            }
             if (Schema::hasColumn('combos', 'combo_code')) {
                 // SỬA CẢNH BÁO: Dùng cách an toàn hơn để kiểm tra và xóa index
                try {
                    // Lấy danh sách indexes của bảng
                    $indexes = DB::select("SHOW INDEX FROM combos WHERE Key_name = 'combos_combo_code_unique'");
                    if (!empty($indexes)) {
                        $table->dropUnique('combos_combo_code_unique');
                    }
                } catch (\Exception $e) {
                    // Bỏ qua lỗi nếu index không tồn tại hoặc có vấn đề khác
                }
                $table->dropColumn('combo_code');
            }
        });
    }
};
