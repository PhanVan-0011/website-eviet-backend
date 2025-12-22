<?php

/**
 * CONFIG QUẢN LÝ ROLES VÀ PERMISSIONS
 * 
 * File này tập trung quản lý tất cả cấu hình liên quan đến roles và branch access.
 * Khi cần thêm role mới hoặc thay đổi quyền, chỉ cần sửa file này.
 * 
 * CÁCH THÊM ROLE MỚI:
 * 
 * 1. Thêm role vào database (thông qua seeder hoặc admin panel)
 * 
 * 2. Nếu role có quyền xem TẤT CẢ branches:
 *    - Thêm tên role vào mảng 'all_branches_roles'
 *    - Thêm mapping vào 'branch_access_config' với value 'all_branches'
 * 
 * 3. Nếu role chỉ xem branches được gán (many-to-many):
 *    - Thêm mapping vào 'branch_access_config' với value 'user_branches'
 *    - Đảm bảo User model có relationship branches() (belongsToMany)
 * 
 * 4. Nếu role chỉ xem 1 branch (single branch từ field branch_id):
 *    - Thêm mapping vào 'branch_access_config' với value 'user_branch_id'
 *    - Đảm bảo User model có field branch_id
 * 
 * Ví dụ thêm role 'manager' chỉ xem 1 branch:
 *    'branch_access_config' => [
 *        ...
 *        'manager' => 'user_branch_id',
 *    ],
 * 
 * Sau đó chạy lại, hệ thống sẽ tự động áp dụng logic mới!
 */

return [
    /**
     * Danh sách các roles có quyền xem tất cả branches (không cần filter)
     * Các roles này sẽ không bị filter bởi BranchAccessService::applyBranchFilter()
     * 
     * Khi thêm role mới có quyền này:
     * 1. Thêm tên role vào mảng này
     * 2. Thêm mapping vào 'branch_access_config' với value 'all_branches'
     */
    'all_branches_roles' => [
        'super-admin',
        'accountant',
    ],

    /**
     * Định nghĩa cách lấy accessible branch IDs cho từng role
     * 
     * Các loại access type:
     * - 'all_branches': Tự động lấy tất cả branch IDs từ database (xem tất cả)
     * - 'user_branches': Lấy từ relationship branches() của User (many-to-many với pivot table)
     * - 'user_branch_id': Lấy từ field branch_id của User (single branch)
     * 
     * LƯU Ý: Nếu role có trong 'all_branches_roles', phải set value là 'all_branches'
     */
    'branch_access_config' => [
        'super-admin' => 'all_branches',
        'accountant' => 'all_branches',
        'branch-admin' => 'user_branches',
        'sales-staff' => 'user_branch_id',
    ],

    /**
     * Danh sách các roles được bảo vệ (không được phép tạo/sửa/xóa bởi user khác)
     * Thường dùng cho các roles hệ thống như super-admin
     */
    'protected_roles' => [
        'super-admin',
    ],
];

