<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('promotion_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade');
            $table->timestamps();

            // Đảm bảo một combo không được thêm vào cùng một khuyến mãi hai lần
            $table->unique(['promotion_id', 'combo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_combos');
    }
};
