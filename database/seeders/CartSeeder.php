<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Combo;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy dữ liệu mẫu (giả sử đã có sẵn)
        $user = User::query()->inRandomOrder()->first();
        $product1 = Product::query()->inRandomOrder()->first();
        $product2 = Product::query()->skip(1)->inRandomOrder()->first();
        $combo    = Combo::query()->inRandomOrder()->first();

        if (!$product1 || !$product2 || !$combo) {
            $this->command->warn('Cần có sẵn Product và Combo để seed Cart.');
            return;
        }

        $cart = Cart::create([
            'user_id'        => $user?->id,
            'guest_token'    => $user ? null : (string) Str::uuid(),
            'status'         => 'active',
            'items_count'    => 0,
            'items_quantity' => 0,
            'subtotal'       => 0,
        ]);

        $items = [
            [
                'product_id'     => $product1->id,
                'combo_id'       => null,
                'unit_of_measure' => $product1->base_unit ?? 'cái',
                'name_snapshot'  => $product1->name ?? 'SP 1',
                'sku_snapshot'   => $product1->product_code ?? $product1->code ?? null,
                'attributes'     => ['size' => 'M', 'sugar' => 'less'],
                'quantity'       => 2,
                'unit_price'     => 20000, // Giá mặc định cho sản phẩm 1
                'notes'          => 'Ít đá',
            ],
            [
                'product_id'     => $product2->id,
                'combo_id'       => null,
                'unit_of_measure' => $product2->base_unit ?? 'cái',
                'name_snapshot'  => $product2->name ?? 'SP 2',
                'sku_snapshot'   => $product2->product_code ?? $product2->code ?? null,
                'attributes'     => null,
                'quantity'       => 1,
                'unit_price'     => 6000, // Giá mặc định cho sản phẩm 2
                'notes'          => null,
            ],
            [
                'product_id'     => null,
                'combo_id'       => $combo->id,
                'unit_of_measure' => 'combo',
                'name_snapshot'  => $combo->name ?? 'Combo',
                'sku_snapshot'   => $combo->slug ?? null,
                'attributes'     => null,
                'quantity'       => 1,
                'unit_price'     => 1500000, // Giá mặc định cho combo
                'notes'          => 'Ăn sáng nhanh',
            ],
        ];

        $subtotal = 0;
        foreach ($items as $data) {
            // Cast attributes về JSON tự động (CartItem::$casts['attributes'=>'array'])
            $item = new CartItem($data);
            $cart->items()->save($item);
            $subtotal += $item->unit_price * $item->quantity;
        }

        $cart->update([
            'items_count'    => $cart->items()->count(),
            'items_quantity' => (int) $cart->items()->sum('quantity'),
            'subtotal'       => $subtotal,
        ]);

        $this->command->info("Seeded Cart #{$cart->id} với subtotal = {$subtotal}");
    }
}
