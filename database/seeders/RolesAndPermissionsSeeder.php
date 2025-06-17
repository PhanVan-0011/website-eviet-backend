<?php
// File: database/seeders/RolesAndPermissionsSeeder.php
// (ĐÃ SỬA LỖI: Loại bỏ cột 'display_name' không tồn tại)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Đảm bảo đã import model User

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- TẠO CÁC QUYỀN HẠN (PERMISSIONS) ---
        $permissions = [
            'orders.view', 'orders.create', 'orders.update', 'orders.update_status', 'orders.cancel', 'orders.update_payment',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.manage', 'combos.manage',
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete',
            'sliders.manage', 'posts.manage',
            'users.manage', 'roles.manage'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // --- TẠO CÁC VAI TRÒ (ROLES) VÀ GÁN QUYỀN ---
        // SỬA LỖI: Chỉ sử dụng cột 'name' để tạo vai trò.
        // Tên hiển thị sẽ được xử lý ở tầng giao diện nếu cần.

        // 1. Vai trò: Nhân viên Hỗ trợ
        $supportRole = Role::create(['name' => 'support-staff']);
        $supportRole->givePermissionTo(['orders.view']);

        // 2. Vai trò: Biên tập viên
        $editorRole = Role::create(['name' => 'content-editor']);
        $editorRole->givePermissionTo(['sliders.manage', 'posts.manage']);
        
        // 3. Vai trò: Quản lý Kho/Sản phẩm
        $productManagerRole = Role::create(['name' => 'product-manager']);
        $productManagerRole->givePermissionTo([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.manage', 'combos.manage'
        ]);

        // 4. Vai trò: Quản lý Bán hàng
        $salesManagerRole = Role::create(['name' => 'sales-manager']);
        $salesManagerRole->givePermissionTo([
            'orders.view', 'orders.create', 'orders.update', 'orders.update_status', 'orders.cancel', 'orders.update_payment',
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete'
        ]);
        
        // 5. Vai trò: Super Admin (Có tất cả các quyền)
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());


        // --- TẠO TÀI KHOẢN MẪU VÀ GÁN VAI TRÒ ---
        
        // Tạo tài khoản Super Admin
        $superAdminUser = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123') // Nhớ đổi mật khẩu này
        ]);
        $superAdminUser->assignRole($superAdminRole);

        // Tạo tài khoản Quản lý Bán hàng
        $salesUser = User::factory()->create([
            'name' => 'Sales Manager',
            'email' => 'sales@example.com',
            'password' => bcrypt('password123')
        ]);
        $salesUser->assignRole($salesManagerRole);
    }
}
