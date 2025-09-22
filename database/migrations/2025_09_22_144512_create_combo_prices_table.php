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
        Schema::create('combo_prices', function (Blueprint $table) {
           $table->id();
           $table->foreignId('combo_id')->constrained('combos');
           $table->foreignId('branch_id')->constrained('branches');
           $table->enum('price_type', ['app_price', 'promo_price',])->default('app_price');
           $table->decimal('price', 12, 2);
           $table->timestamp('start_date')->nullable();
           $table->timestamp('end_date')->nullable();
           $table->timestamps();
            
            $table->unique(['combo_id', 'branch_id', 'price_type', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_prices');
    }
};
