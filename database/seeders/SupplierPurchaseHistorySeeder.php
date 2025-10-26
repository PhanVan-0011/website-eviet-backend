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
use Carbon\Carbon;

class SupplierPurchaseHistorySeeder extends Seeder
{
    /**
     * Chạy seeder để tạo lịch sử nhập hàng từ các nhà cung cấp
     * 
     * Seeder này tạo ra:
     * - Nhiều hóa đơn nhập hàng từ các nhà cung cấp khác nhau
     * - Chi tiết các hóa đơn với sản phẩm khác nhau
     * - Cập nhật tồn kho tương ứng
     */
    public function run(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Xóa dữ liệu cũ
            PurchaseInvoiceDetail::truncate();
            PurchaseInvoice::truncate();

            // BẮT ĐẦU TRANSACTION
            DB::transaction(function () {
                // Lấy dữ liệu cần thiết
                $suppliers = Supplier::get();  // Lấy tất cả suppliers
                $branches = Branch::get();     // Lấy tất cả branches
                $users = User::get();          // Lấy tất cả users
                $products = Product::get();    // Lấy tất cả products

                // Kiểm tra dữ liệu
                if ($suppliers->count() < 1 || $branches->count() < 1 || $users->count() < 1 || $products->count() < 2) {
                    $this->command->warn('⚠️ Không đủ dữ liệu mẫu! Vui lòng chạy các seeder: SupplierSeeder, BranchSeeder, UserSeeder, ProductSeeder trước.');
                    return;
                }

                $createdCount = 0;
                $invoiceDetails = [];

                // Tạo dữ liệu hóa đơn nhập hàng cho mỗi nhà cung cấp
                foreach ($suppliers as $supplierIndex => $supplier) {
                    // Mỗi nhà cung cấp có 10-20 hóa đơn (nhiều hơn)
                    $invoiceCount = rand(10, 20);

                    for ($i = 1; $i <= $invoiceCount; $i++) {
                        $branch = $branches->random();
                        $user = $users->random();

                        // Tính toán ngày hóa đơn ngẫu nhiên trong 3 tháng gần đây
                        $invoiceDate = Carbon::now()->subDays(rand(0, 90));

                        // Chọn số sản phẩm ngẫu nhiên (2-4 sản phẩm mỗi hóa đơn)
                        $selectedProducts = $products->random(rand(2, min(4, $products->count())));

                        // Tính toán subtotal từ chi tiết
                        $subtotal = 0;
                        $totalQuantity = 0;
                        $invoiceItems = [];

                        foreach ($selectedProducts as $product) {
                            $quantity = rand(10, 100);
                            // Sử dụng giá mặc định nếu không có price
                            $unitPrice = rand(10000, 100000);
                            $itemSubtotal = $quantity * $unitPrice;

                            $subtotal += $itemSubtotal;
                            $totalQuantity += $quantity;

                            $invoiceItems[] = [
                                'product_id' => $product->id,
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'subtotal' => $itemSubtotal,
                            ];
                        }

                        // Áp dụng chiết khấu (0-15%)
                        $discountPercent = rand(0, 15);
                        $discount = ($subtotal * $discountPercent) / 100;
                        $totalAmount = $subtotal - $discount;

                        // Trả tiền (50-100% của tổng tiền)
                        $paidPercent = rand(50, 100);
                        $paidAmount = ($totalAmount * $paidPercent) / 100;
                        $amountOwed = $totalAmount - $paidAmount;

                        // Xác định trạng thái
                        $statuses = ['received', 'received', 'received', 'draft', 'cancelled'];
                        $status = $statuses[array_rand($statuses)];

                        // Tạo hóa đơn chính
                        $invoice = PurchaseInvoice::create([
                            'invoice_code' => 'HDN' . $supplier->code . Carbon::now()->format('YmdHis') . $i,
                            'supplier_id' => $supplier->id,
                            'branch_id' => $branch->id,
                            'user_id' => $user->id,
                            'invoice_date' => $invoiceDate,
                            'total_quantity' => $totalQuantity,
                            'total_items' => count($invoiceItems),
                            'subtotal_amount' => round($subtotal, 2),
                            'discount_amount' => round($discount, 2),
                            'total_amount' => round($totalAmount, 2),
                            'paid_amount' => round($paidAmount, 2),
                            'amount_owed' => round($amountOwed, 2),
                            'notes' => "Hóa đơn nhập từ nhà cung cấp {$supplier->name}",
                            'status' => $status,
                        ]);

                        // Tạo chi tiết hóa đơn và cập nhật tồn kho
                        foreach ($invoiceItems as $detail) {
                            $invoice->details()->create([
                                'product_id' => $detail['product_id'],
                                'quantity' => $detail['quantity'],
                                'unit_price' => $detail['unit_price'],
                                'subtotal' => $detail['subtotal'],
                                'unit_of_measure' => 'cái',
                            ]);

                            // Cập nhật tồn kho
                            BranchProductStock::updateOrCreate(
                                [
                                    'branch_id' => $branch->id,
                                    'product_id' => $detail['product_id'],
                                ],
                                [
                                    'quantity' => DB::raw('COALESCE(quantity, 0) + ' . $detail['quantity'])
                                ]
                            );
                        }

                        $createdCount++;
                        $invoiceDetails[] = [
                            'code' => $invoice->invoice_code,
                            'supplier' => $supplier->name,
                            'date' => $invoiceDate->format('Y-m-d'),
                            'total' => $totalAmount,
                            'status' => $status,
                        ];
                    }
                }

                // Xuất ra thông tin
                $this->command->info("✅ Đã tạo thành công {$createdCount} hóa đơn nhập hàng!");
                $this->command->info("\n📋 Chi tiết hóa đơn:");
                $this->command->table(
                    ['Mã hóa đơn', 'Nhà cung cấp', 'Ngày', 'Tổng tiền', 'Trạng thái'],
                    $invoiceDetails
                );
            });
        } catch (\Throwable $e) {
            $this->command->error('❌ Lỗi khi chạy SupplierPurchaseHistorySeeder: ' . $e->getMessage());
            throw $e;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
