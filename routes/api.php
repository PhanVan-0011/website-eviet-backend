<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\Client\RegistrationController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\SupplierGroupController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\Client\ClientSliderController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PickupLocationController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\Client\ProductController as ClientProductController;
use App\Http\Controllers\Api\Client\PromotionController as ClientPromotionController;
use App\Http\Controllers\Api\Client\ComboController as ClientComboController;
use App\Http\Controllers\Api\Client\PostController as ClientPostController;
use App\Http\Controllers\Api\Client\SearchController as ClientSearchController;
use App\Http\Controllers\Api\Client\CategoryController as ClientCategoryController;



// ===  ROUTE PUBLIC ===
Route::prefix('public')->group(function () {
    Route::get('/search', [ClientSearchController::class, 'search']);
    Route::get('/sliders', [ClientSliderController::class, 'index']);
    // slider hiển thị trang chủ
    Route::get('/sliders/without-linkable', [ClientSliderController::class, 'withoutLinkable']);
    Route::get('/categories', [ClientCategoryController::class, 'index']);

    Route::prefix('products')->group(function () {
        Route::get('/', [ClientProductController::class, 'index']);
        Route::get('/best-sellers', [ClientProductController::class, 'bestSellers']);
        Route::get('/recommendations', [ClientProductController::class, 'recommendations']);
        Route::get('/{id}', [ClientProductController::class, 'show']);
    });
    Route::prefix('promotions')->group(function () {
        Route::get('/', [ClientPromotionController::class, 'index']);
        Route::get('/{id}', [ClientPromotionController::class, 'show']);
    });
    Route::prefix('combos')->group(function () {
        Route::get('/', [ClientComboController::class, 'index']);
        Route::get('/{id}', [ClientComboController::class, 'show']);
    });
    Route::prefix('posts')->group(function () {
        Route::get('/', [ClientPostController::class, 'index']);
        Route::get('/{slug}', [ClientPostController::class, 'show']);
    });
});
// --- Client Authentication ---
Route::prefix('auth')->group(function () {
    Route::post('/register/initiate', [RegistrationController::class, 'initiate'])->middleware('throttle:3,1');
    Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp']);
    Route::post('/register/complete', [RegistrationController::class, 'complete']);
    //LOGIN
    Route::post('/loginApp', [AuthController::class, 'loginApp']);
});
//ForgotPassWord
// Route::prefix('password/forgot')->group(function () {
//     Route::post('/initiate', [ForgotPasswordController::class, 'initiate'])->middleware('throttle:3,1');
//     Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
//     Route::post('/complete', [ForgotPasswordController::class, 'complete']);
// });
//LOGIN ADMIN
Route::post('/login', [AuthController::class, 'login']);

// --- Public Utilities ---
Route::get('images/{path}', [ImageController::class, 'show'])->where('path', '.*');
Route::post('upload-image', [ImageController::class, 'uploadGeneric']);
Route::delete('delete-image', [ImageController::class, 'deleteImage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //User Register and Login by otp
    Route::prefix('me')->group(function () {
        Route::get('/', [ClientProfileController::class, 'show']);
    });

    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile/update', [AuthController::class, 'update_profile']);
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->middleware('check.permission:orders.view,orders.update,payment_methods.view');
    Route::get('/menu', [MenuController::class, 'getMenu']);
});


//Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // --- Purchase Invoices ---
    Route::prefix('purchase-invoices')->middleware('check.permission:purchase-invoices.view')->group(function () {
        Route::get('/', [PurchaseInvoiceController::class, 'index']);
        Route::get('/{id}', [PurchaseInvoiceController::class, 'show']);
        Route::put('/{id}/cancel', [PurchaseInvoiceController::class, 'cancel']);
        Route::post('/', [PurchaseInvoiceController::class, 'store'])->middleware('check.permission:purchase-invoices.create');
        Route::put('/{id}', [PurchaseInvoiceController::class, 'update'])->middleware('check.permission:purchase-invoices.update');
        Route::delete('/multi-delete', [PurchaseInvoiceController::class, 'multiDelete'])->middleware('check.permission:purchase-invoices.delete');
        Route::delete('/{id}', [PurchaseInvoiceController::class, 'destroy'])->middleware('check.permission:purchase-invoices.delete');
    });
    // --- Supplier Groups ---
    Route::prefix('supplier-groups')->middleware('check.permission:supplier-groups.view,suppliers.create,suppliers.update')->group(function () {
        Route::get('/', [SupplierGroupController::class, 'index']);
        Route::get('/{id}', [SupplierGroupController::class, 'show']);
        Route::post('/', [SupplierGroupController::class, 'store'])->middleware('check.permission:supplier-groups.create');
        Route::put('/{id}', [SupplierGroupController::class, 'update'])->middleware('check.permission:supplier-groups.update');
        Route::delete('/multi-delete', [SupplierGroupController::class, 'multiDelete'])->middleware('check.permission:supplier-groups.delete');
        Route::delete('/{id}', [SupplierGroupController::class, 'destroy'])->middleware('check.permission:supplier-groups.delete');
    });

    // --- Suppliers ---
    Route::prefix('suppliers')->middleware('check.permission:suppliers.view,purchase-invoices.create,purchase-invoices.update')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::get('/{supplierId}/purchase-history', [PurchaseInvoiceController::class, 'getHistoryBySupplier']);
        Route::post('/', [SupplierController::class, 'store'])->middleware('check.permission:suppliers.create');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('check.permission:suppliers.update');
        Route::delete('/multi-delete', [SupplierController::class, 'multiDelete'])->middleware('check.permission:suppliers.delete');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('check.permission:suppliers.delete');
    });
    // --- Branch ---
    Route::prefix('branches')->middleware('check.permission:branches.view,admin-users.create,admin-users.update')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::get('/{id}', [BranchController::class, 'show']);
        Route::post('/', [BranchController::class, 'store'])->middleware('check.permission:branches.create');
        Route::post('/{id}', [BranchController::class, 'update'])->middleware('check.permission:branches.update');
        Route::delete('/multi-delete', [BranchController::class, 'multiDelete'])->middleware('check.permission:branches.delete');
        Route::delete('/{id}', [BranchController::class, 'destroy'])->middleware('check.permission:branches.delete');
    });
    // --- Pickup Location (Nơi nhận hàng) ---
    Route::prefix('pickup-locations')->group(function () {
        Route::get('/', [PickupLocationController::class, 'index']);
        Route::post('/', [PickupLocationController::class, 'store']);
        Route::get('/{id}', [PickupLocationController::class, 'show']);
        Route::put('/{id}', [PickupLocationController::class, 'update']);
        Route::delete('/multi-delete', [PickupLocationController::class, 'multiDelete']);
        Route::delete('/{id}', [PickupLocationController::class, 'destroy']);
    });
    // --- Time Slots (Khung Giờ Bán Hàng) ---
    Route::prefix('time-slots')->middleware('check.permission:timeslots.manage')->group(function () {
        Route::get('/', [TimeSlotController::class, 'index']);
        Route::put('/{id}', [TimeSlotController::class, 'update']);
        Route::get('/{id}', [TimeSlotController::class, 'show']);
        Route::post('/', [TimeSlotController::class, 'store']);
        Route::delete('/multi-delete', [TimeSlotController::class, 'multiDelete']);
        Route::delete('/{id}', [TimeSlotController::class, 'destroy']);
    });

    // ---Slider---
    Route::prefix('sliders')->middleware('check.permission:sliders.view')->group(function () {
        Route::get('/', [SliderController::class, 'index']);
        Route::get('/{id}', [SliderController::class, 'show']);
        Route::post('/', [SliderController::class, 'store'])->middleware('check.permission:sliders.create');
        Route::post('/{id}', [SliderController::class, 'update'])->middleware('check.permission:sliders.update');
        Route::delete('/multi-delete', [SliderController::class, 'multiDelete'])->middleware('check.permission:sliders.delete');
        Route::delete('/{id}', [SliderController::class, 'destroy'])->middleware('check.permission:sliders.delete');
    });

    // ---Combo---
    Route::prefix('combos')->middleware('check.permission:combos.view,orders.create,orders.update,promotions.create,promotions.update,sliders.create,sliders.update')->group(function () {
        Route::get('/', [ComboController::class, 'index']);
        Route::get('/{id}', [ComboController::class, 'show']);
        Route::post('/', [ComboController::class, 'store'])->middleware('check.permission:combos.create');
        Route::post('/{id}', [ComboController::class, 'update'])->middleware('check.permission:combos.update');
        Route::delete('/multi-delete', [ComboController::class, 'multiDelete'])->middleware('check.permission:combos.delete');
        Route::delete('/{id}', [ComboController::class, 'destroy'])->middleware('check.permission:combos.delete');
    });

    // ---Category---
    Route::prefix('categories')->middleware('check.permission:categories.view,promotions.create,promotions.update,products.view,products.create,products.update,posts.create,posts.update')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        // Lấy danh mục theo loại (product hoặc post) - dùng cho dropdown
        Route::get('/for-type', [CategoryController::class, 'getForType']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store'])->middleware('check.permission:categories.create');
        Route::post('/{id}', [CategoryController::class, 'update'])->middleware('check.permission:categories.update');
        Route::delete('/multi-delete', [CategoryController::class, 'multiDelete'])->middleware('check.permission:categories.delete');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('check.permission:categories.delete');
    });

    // ---Product---
    Route::prefix('products')->middleware('check.permission:products.view,orders.create,orders.update,promotions.create,promotions.update,sliders.create,sliders.update,combos.create,combos.update,purchase-invoices.create,purchase-invoices.update')->group(function () {
        Route::get('/search-for-purchase', [ProductController::class, 'searchForPurchase']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store'])->middleware('check.permission:products.create');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware('check.permission:products.update');
        Route::delete('/multi-delete', [ProductController::class, 'multiDelete'])->middleware('check.permission:products.delete');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('check.permission:products.delete');
    });
    // ---Post---
    Route::prefix('posts')->middleware('check.permission:posts.view')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::post('/', [PostController::class, 'store'])->middleware('check.permission:posts.create');
        Route::post('/{id}', [PostController::class, 'update'])->middleware('check.permission:posts.update');
        Route::delete('/multi-delete', [PostController::class, 'multiDelete'])->middleware('check.permission:posts.delete');
        Route::delete('/{id}', [PostController::class, 'destroy'])->middleware('check.permission:posts.delete');
    });

    // ---Promotion---
    Route::prefix('promotions')->middleware('check.permission:promotions.view,sliders.create,sliders.update')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::get('/{promotion}', [PromotionController::class, 'show']);
        Route::post('/', [PromotionController::class, 'store'])->middleware('check.permission:promotions.create');
        Route::post('/{promotion}', [PromotionController::class, 'update'])->middleware('check.permission:promotions.update');
        Route::delete('/multi-delete', [PromotionController::class, 'multiDelete'])->middleware('check.permission:promotions.delete');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->middleware('check.permission:promotions.delete');
    });

    // ---Users (Khách hàng)---
    Route::prefix('users')->middleware('check.permission:users.view')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store'])->middleware('check.permission:users.create');
        Route::post('/{id}', [UserController::class, 'update'])->middleware('check.permission:users.update');
        Route::delete('/multi-delete', [UserController::class, 'multiDelete'])->middleware('check.permission:users.delete');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('check.permission:users.delete');
    });
    Route::prefix('admins')->middleware('check.permission:admin-users.view,purchase-invoices.create,purchase-invoices.update')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/', [AdminUserController::class, 'store'])->middleware('check.permission:admin-users.create')->name('admins.store');
        Route::get('/trash', [AdminUserController::class, 'trash'])->name('admins.trash');
        Route::delete('/multi-delete', [AdminUserController::class, 'multiDelete'])->middleware('check.permission:admin-users.delete')->name('admins.multi-delete');
        Route::get('/{id}', [AdminUserController::class, 'show'])->name('admins.show');
        Route::post('/{id}', [AdminUserController::class, 'update'])->middleware('check.permission:admin-users.update')->name('admins.update');
        Route::delete('/{id}', [AdminUserController::class, 'destroy'])->middleware('check.permission:admin-users.delete')->name('admins.destroy');
        Route::post('/{id}/restore', [AdminUserController::class, 'restore'])->middleware('check.permission:admin-users.update')->name('admins.restore');
        Route::delete('/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->middleware('check.permission:admin-users.delete')->name('admins.force-delete');
    });

    // ---Order---
    Route::prefix('orders')->middleware('check.permission:orders.view')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store'])->middleware('check.permission:orders.create');
        // Note: orders.update được xử lý thông qua updateStatus (duyệt đơn) và có thể thêm route update thông tin đơn sau
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('check.permission:orders.update_status');
        Route::put('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('check.permission:orders.update_payment');
        Route::post('/multi-cancel', [OrderController::class, 'multiCancel'])->middleware('check.permission:orders.cancel');
    });

    // ---Role & Permission---
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('check.permission:roles.view,admin-users.create,admin-users.update')->name('roles.index');
        Route::get('/{role}', [RoleController::class, 'show'])->middleware('check.permission:roles.view')->name('roles.show');
        Route::post('/', [RoleController::class, 'store'])->middleware('check.permission:roles.create')->name('roles.store');
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('check.permission:roles.update')->name('roles.update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('check.permission:roles.delete')->name('roles.destroy');
        Route::delete('/multi-delete', [RoleController::class, 'multiDelete'])->middleware('check.permission:roles.delete')->name('roles.multi-delete');
    });
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('check.permission:roles.view');
    Route::post('assign-role/{id}', [PermissionController::class, 'assignRolesToUser'])->middleware('check.permission:roles.update');
    Route::post('assign-permission/{id}', [PermissionController::class, 'assignPermissionsToUser'])->middleware('check.permission:roles.update');


    //---Dashboard---
    Route::get('/dashboard', [DashboardController::class, 'getStatistics'])->middleware('check.permission:dashboard.view');
});
