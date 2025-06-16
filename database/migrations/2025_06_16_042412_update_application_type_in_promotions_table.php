<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         DB::statement("ALTER TABLE promotions MODIFY COLUMN application_type ENUM('orders', 'products', 'categories', 'combos') NOT NULL DEFAULT 'orders'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       // Quay trở lại định nghĩa cũ nếu cần rollback
        DB::statement("ALTER TABLE promotions MODIFY COLUMN application_type ENUM('all_orders', 'specific_products', 'specific_categories', 'specific_combos') NOT NULL DEFAULT 'all_orders'");
    }
};
