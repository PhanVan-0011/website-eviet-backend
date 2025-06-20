<?php
// File: database/seeders/RolesAndPermissionsSeeder.php
// (ĐÃ SỬA LỖI: Thêm 'guard_name' => 'api' để tương thích với Sanctum)

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
        // Xóa cache của package để đảm bảo các quyền mới được nhận diện
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // **QUAN TRỌNG:** Chỉ định guard cho API để tương thích với Sanctum
        $guardName = 'api'; 

        // --- TẠO CÁC QUYỀN HẠN (PERMISSIONS) ---
        $permissions = [
            'orders.view', 'orders.create', 'orders.update', 'orders.update_status', 'orders.cancel', 'orders.update_payment',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.manage', 'combos.manage',
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete',
            'sliders.manage', 'posts.manage',
            'users.manage', 'roles.manage'
        ];
        
        // Tạo các permission trong CSDL với guard 'api'
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        }

        // --- TẠO CÁC VAI TRÒ (ROLES) VÀ GÁN QUYỀN ---

        // 1. Vai trò: Nhân viên Hỗ trợ
        $supportRole = Role::create(['name' => 'support-staff', 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        $supportRole->givePermissionTo(['orders.view']);

        // 2. Vai trò: Biên tập viên
        $editorRole = Role::create(['name' => 'content-editor', 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        $editorRole->givePermissionTo(['sliders.manage', 'posts.manage']);
        
        // 3. Vai trò: Quản lý Kho/Sản phẩm
        $productManagerRole = Role::create(['name' => 'product-manager', 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        $productManagerRole->givePermissionTo([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.manage', 'combos.manage'
        ]);

        // 4. Vai trò: Quản lý Bán hàng
        $salesManagerRole = Role::create(['name' => 'sales-manager', 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        $salesManagerRole->givePermissionTo([
            'orders.view', 'orders.create', 'orders.update', 'orders.update_status', 'orders.cancel', 'orders.update_payment',
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete'
        ]);
        
        // 5. Vai trò: Super Admin (Có tất cả các quyền)
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => $guardName]); // <-- SỬA LỖI Ở ĐÂY
        // Chỉ gán các quyền thuộc guard 'api'
        $superAdminRole->givePermissionTo(Permission::where('guard_name', $guardName)->get());


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
