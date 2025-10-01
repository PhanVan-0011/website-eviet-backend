# Cấu trúc Seeder Database

## Tổng quan

Hệ thống seeder đã được sửa lại để phù hợp với cấu trúc database mới, sử dụng các bảng riêng biệt để quản lý giá và tồn kho.

## Thứ tự thực hiện Seeder

### 1. Seeder cơ bản (không phụ thuộc)

-   `RolesAndPermissionsSeeder` - Tạo vai trò và quyền
-   `PaymentMethodSeeder` - Tạo phương thức thanh toán
-   `OtpVerificationSeeder` - Tạo mã OTP mẫu

### 2. Seeder cho người dùng và chi nhánh

-   `UserSeeder` - Tạo người dùng
-   `BranchSeeder` - Tạo chi nhánh

### 3. Seeder cho nhà cung cấp

-   `SupplierGroupSeeder` - Tạo nhóm nhà cung cấp
-   `SupplierSeeder` - Tạo nhà cung cấp

### 4. Seeder cho danh mục và sản phẩm

-   `CategoriesSeeder` - Tạo danh mục
-   `ProductSeeder` - Tạo sản phẩm (chỉ thông tin cơ bản)
-   `ProductPriceSeeder` - Tạo giá sản phẩm theo chi nhánh
-   `BranchProductStockSeeder` - Tạo tồn kho sản phẩm theo chi nhánh
-   `ProductAttributeSeeder` - Tạo thuộc tính sản phẩm

### 5. Seeder cho combo

-   `ComboSeeder` - Tạo combo (chỉ thông tin cơ bản)
-   `ComboPriceSeeder` - Tạo giá combo theo chi nhánh
-   `ComboItemSeeder` - Tạo sản phẩm trong combo

### 6. Seeder cho slider và bài viết

-   `SliderSeeder` - Tạo slider
-   `PostSeeder` - Tạo bài viết

### 7. Seeder cho khuyến mãi

-   `PromotionSeeder` - Tạo khuyến mãi
-   `PromotionRelationSeeder` - Tạo quan hệ khuyến mãi

### 8. Seeder cho đơn hàng

-   `OrderSeeder` - Tạo đơn hàng (cần có sản phẩm trước)

### 9. Seeder cho giỏ hàng

-   `CartSeeder` - Tạo giỏ hàng (cần có sản phẩm và combo trước)

### 10. Seeder demo cuối cùng

-   `DashboardDemoSeeder` - Tạo dữ liệu demo cho dashboard

## Cấu trúc Database Mới

### Bảng Products

-   **Cột còn lại:** `id`, `name`, `description`, `status`, `product_code`, `created_at`, `updated_at`
-   **Cột đã xóa:** `original_price`, `sale_price`, `stock_quantity`, `size`, `image_url`, `category_id`

### Bảng Combos

-   **Cột còn lại:** `id`, `name`, `description`, `slug`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`
-   **Cột đã xóa:** `price`, `image_url`

### Bảng Branches

-   **Cột còn lại:** `id`, `code`, `name`, `address`, `phone_number`, `email`, `active`, `created_at`, `updated_at`
-   **Cột đã đổi tên:** `is_active` → `active`

### Bảng mới được sử dụng

-   **`product_prices`** - Quản lý giá sản phẩm theo chi nhánh và loại giá
-   **`combo_prices`** - Quản lý giá combo theo chi nhánh và loại giá
-   **`branch_product_stocks`** - Quản lý tồn kho sản phẩm theo chi nhánh
-   **`images`** - Quản lý ảnh cho tất cả các model (polymorphic)

## Cách chạy Seeder

```bash
# Chạy tất cả seeder
php artisan db:seed

# Chạy seeder cụ thể
php artisan db:seed --class=ProductSeeder

# Reset và chạy lại tất cả
php artisan migrate:fresh --seed
```

## Lưu ý

-   Tất cả seeder đã được kiểm tra và sửa lỗi
-   Thứ tự seeder đã được tối ưu để tránh lỗi foreign key
-   Các seeder sử dụng giá trị cố định thay vì truy cập cột đã bị xóa
-   **Factory đã được sửa:** `ProductFactory` và `OrderDetailFactory` đã được cập nhật để không sử dụng các cột đã bị xóa
