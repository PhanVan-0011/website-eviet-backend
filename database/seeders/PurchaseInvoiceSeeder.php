<?php

namespace Database\Seeders;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\BranchProductStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
            
            // Xóa dữ liệu cũ (nên nằm ngoài transaction nếu muốn truncate thành công)
            PurchaseInvoice::truncate();
            PurchaseInvoiceDetail::truncate();
            
            // --- BẮT ĐẦU TRANSACTION AN TOÀN ---
            DB::transaction(function () {
                // Lấy dữ liệu cần thiết (Cần đảm bảo các Seeder này đã chạy trước đó!)
                $supplier = Supplier::first();
                $branch = Branch::first();
                $user = User::where('email', 'superadmin@example.com')->first();
                $products = Product::take(2)->get();

                // Kiểm tra dữ liệu mẫu (đảm bảo tồn tại)
                if (!$supplier || !$branch || !$user || $products->count() < 2) {
                    $this->command->warn('Không đủ dữ liệu mẫu (Supplier, Branch, User, hoặc Products). Vui lòng chạy các Seeder liên quan trước.');
                    // Nếu thiếu dữ liệu, commit transaction rỗng và return.
                    return;
                }

                $details = [
                    [
                        'product_id' => $products[0]->id,
                        'quantity' => 50,
                        'unit_price' => 10000.00,
                    ],
                    [
                        'product_id' => $products[1]->id,
                        'quantity' => 100,
                        'unit_price' => 5000.00,
                    ],
                ];
                
                $subtotal = 1000000.00;
                $discount = 50000.00;
                $totalAmount = $subtotal - $discount; 
                $paidAmount = 700000.00;
                $amountOwed = $totalAmount - $paidAmount; 

                // --- 2. TẠO HÓA ĐƠN CHÍNH (Kích hoạt Observer) ---
                $invoice = PurchaseInvoice::create([
                    'invoice_code' => 'HDN' . now()->format('YmdHi'),
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'invoice_date' => now(),
                    'total_quantity' => 150,
                    'total_items' => 2,
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'amount_owed' => $amountOwed,
                    'notes' => 'Hóa đơn nhập hàng mẫu phát sinh công nợ',
                    'status' => 'received',
                ]);

                // --- 3. LƯU CHI TIẾT & CẬP NHẬT TỒN KHO ---
                $totalStockChange = 0;
                foreach ($details as $detail) {
                    // Lưu chi tiết hóa đơn
                    $invoice->details()->create([
                        'product_id' => $detail['product_id'],
                        'quantity' => $detail['quantity'],
                        'unit_price' => $detail['unit_price'],
                        'subtotal' => $detail['quantity'] * $detail['unit_price'],
                    ]);

                    // Cập nhật tồn kho 
                    BranchProductStock::updateOrCreate(
                        ['branch_id' => $branch->id, 'product_id' => $detail['product_id']],
                        ['quantity' => DB::raw('quantity + ' . $detail['quantity'])]
                    );
                    $totalStockChange += $detail['quantity'];
                }
                
                // In ra kết quả để xác nhận
                $this->command->info("Đã tạo thành công Hóa đơn nhập: {$invoice->invoice_code}");
                $this->command->info("Kiểm tra DB: Supplier ID {$supplier->id} và tồn kho Branch ID {$branch->id}.");
                
            }); // DB::commit() tự động được gọi ở đây

        } catch (\Throwable $e) {
            // Lỗi đã được xử lý tự động Rollback bởi DB::transaction()
            $this->command->error('Lỗi khi chạy PurchaseInvoiceSeeder: ' . $e->getMessage());
            // Ném lỗi để thông báo ra console
            throw $e; 
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
