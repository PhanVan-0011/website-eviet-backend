<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductAttributeController; 
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
use App\Http\Controllers\Api\Client\ForgotPasswordController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\SupplierGroupController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\Client\ClientSliderController;
use App\Http\Controllers\Api\ProductUnitConversionController;
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
Route::prefix('password/forgot')->group(function () {
    Route::post('/initiate', [ForgotPasswordController::class, 'initiate'])->middleware('throttle:3,1');
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('/complete', [ForgotPasswordController::class, 'complete']);
});
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
});


//Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
     // --- Purchase Invoices (Hóa đơn nhập hàng) ---
    Route::prefix('purchase-invoices')->group(function () {
        Route::get('/', [PurchaseInvoiceController::class, 'index']);
        Route::get('/{id}', [PurchaseInvoiceController::class, 'show']);
        Route::post('/', [PurchaseInvoiceController::class, 'store']);
        Route::put('/{id}', [PurchaseInvoiceController::class, 'update']);
        Route::delete('/multi-delete', [PurchaseInvoiceController::class, 'multiDelete']);
        Route::delete('/{id}', [PurchaseInvoiceController::class, 'destroy']);
    });
     // --- Supplier Groups ---
    Route::prefix('supplier-groups')->group(function () {
        Route::get('/', [SupplierGroupController::class, 'index']);
        Route::get('/{id}', [SupplierGroupController::class, 'show']);
        Route::post('/', [SupplierGroupController::class, 'store']);
        Route::put('/{id}', [SupplierGroupController::class, 'update']);
        Route::delete('/multi-delete', [SupplierGroupController::class, 'multiDelete']);
        Route::delete('/{id}', [SupplierGroupController::class, 'destroy']);
    });

    // --- Suppliers ---
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::delete('/multi-delete', [SupplierController::class, 'multiDelete']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::get('/{supplierId}/purchase-history', [PurchaseInvoiceController::class, 'getHistoryBySupplier']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        
    });
    // ---Branch---
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::delete('/multi-delete', [BranchController::class, 'multiDelete']);
        Route::get('/{id}', [BranchController::class, 'show']);
        Route::post('/', [BranchController::class, 'store']);
        Route::post('/{id}', [BranchController::class, 'update']);
        Route::delete('/{id}', [BranchController::class, 'destroy']);
 
    });

    // ---Slider---
    Route::prefix('sliders')->middleware('check.permission:sliders.manage')->group(function () {
        Route::get('/', [SliderController::class, 'index']);
        Route::get('/{id}', [SliderController::class, 'show']);
        Route::post('/', [SliderController::class, 'store'])->middleware('check.permission:sliders.manage');
        Route::post('/{id}', [SliderController::class, 'update'])->middleware('check.permission:sliders.manage');
        Route::delete('/multi-delete', [SliderController::class, 'multiDelete'])->middleware('check.permission:sliders.manage');
        Route::delete('/{id}', [SliderController::class, 'destroy'])->middleware('check.permission:sliders.manage');
    });

    // ---Combo---
    Route::prefix('combos')->middleware('check.permission:combos.manage,orders.create,orders.update,promotions.create,promotions.update,sliders.manage')->group(function () {
        Route::get('/', [ComboController::class, 'index']);
        Route::get('/{id}', [ComboController::class, 'show']);
        Route::post('/', [ComboController::class, 'store'])->middleware('check.permission:combos.manage');
        Route::post('/{id}', [ComboController::class, 'update'])->middleware('check.permission:combos.manage');
        Route::delete('/multi-delete', [ComboController::class, 'multiDelete'])->middleware('check.permission:combos.manage');
        Route::delete('/{id}', [ComboController::class, 'destroy'])->middleware('check.permission:combos.manage');
    });

    // ---Category---
    Route::prefix('categories')->middleware('check.permission:categories.view,promotions.create, promotions.update,products.view,products.create,products.update,posts.manage')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store'])->middleware('check.permission:categories.manage');
        Route::post('/{id}', [CategoryController::class, 'update'])->middleware('check.permission:categories.manage');
        Route::delete('/multi-delete', [CategoryController::class, 'multiDelete'])->middleware('check.permission:categories.manage');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('check.permission:categories.manage');
    });

    // ---Product---
    Route::prefix('products')->middleware('check.permission:products.view,orders.create,orders.update,promotions.create,promotions.update,sliders.manage,combos.manage')->group(function () {
        Route::get('/search-for-purchase', [ProductController::class, 'searchForPurchase']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store'])->middleware('check.permission:products.create');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware('check.permission:products.update');
        Route::delete('/multi-delete', [ProductController::class, 'multiDelete'])->middleware('check.permission:products.delete');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('check.permission:products.delete');
    });
    // ---Product Unit Conversions (Đơn vị chuyển đổi) 
    Route::prefix('product-units')->middleware('check.permission:products.manage')->group(function () {
        Route::get('product/{product_id}', [ProductUnitConversionController::class, 'index']);
        Route::get('/{id}', [ProductUnitConversionController::class, 'show']);
        Route::post('/', [ProductUnitConversionController::class, 'store']);
        Route::put('{id}', [ProductUnitConversionController::class, 'update']);
        Route::delete('/{id}', [ProductUnitConversionController::class, 'destroy']);
    });
    // ---Product attributes---
    Route::prefix('product-attributes')->middleware('check.permission:products.manage')->group(function () {
        Route::get('/', [ProductAttributeController::class, 'index']);
        Route::get('/{id}', [ProductAttributeController::class, 'show']);
        Route::post('/', [ProductAttributeController::class, 'store']);
        Route::post('/{id}', [ProductAttributeController::class, 'update']);
        Route::delete('/multi-delete', [ProductAttributeController::class, 'multiDelete']);
        Route::delete('/{id}', [ProductAttributeController::class, 'destroy']);
        Route::delete('attribute-values/{id}', [ProductAttributeController::class, 'destroyValue']);
    });
     // ---Attributes values---
    Route::prefix('attribute-values')->middleware('check.permission:products.manage')->group(function () {
        Route::delete('/{id}', [ProductAttributeController::class, 'deleteAttributeValue']);
    });
    // ---Post---
    Route::prefix('posts')->middleware('check.permission:posts.manage')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::post('/', [PostController::class, 'store'])->middleware('check.permission:posts.manage');
        Route::post('/{id}', [PostController::class, 'update'])->middleware('check.permission:posts.manage');
        Route::delete('/multi-delete', [PostController::class, 'multiDelete'])->middleware('check.permission:posts.manage');
        Route::delete('/{id}', [PostController::class, 'destroy'])->middleware('check.permission:posts.manage');
        
    });

    // ---Promotion---
    Route::prefix('promotions')->middleware('check.permission:promotions.view,sliders.manage')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::post('/', [PromotionController::class, 'store'])->middleware('check.permission:promotions.create');
        Route::post('/{promotion}', [PromotionController::class, 'update'])->middleware('check.permission:promotions.update');
        Route::get('/{promotion}', [PromotionController::class, 'show']);
        Route::delete('/multi-delete', [PromotionController::class, 'multiDelete'])->middleware('check.permission:promotions.delete');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->middleware('check.permission:promotions.delete');
    });

    // ---Customer & Admin Users---
    Route::prefix('users')->middleware('check.permission:users.manage')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::post('/{id}', [UserController::class, 'update']);
        Route::delete('/multi-delete', [UserController::class, 'multiDelete']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    Route::prefix('admins')->middleware('check.permission:users.manage')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/', [AdminUserController::class, 'store'])->name('admins.store');
        Route::get('/trash', [AdminUserController::class, 'trash'])->name('admins.trash');
        Route::delete('/multi-delete', [AdminUserController::class, 'multiDelete'])->name('admins.multi-delete');
        Route::get('/{id}', [AdminUserController::class, 'show'])->name('admins.show');
        Route::post('/{id}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::delete('/{id}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
        Route::post('/{id}/restore', [AdminUserController::class, 'restore'])->name('admins.restore');
        Route::delete('/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->name('admins.force-delete');
    });

    // ---Order---
    Route::prefix('orders')->middleware('check.permission:orders.view')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store'])->middleware('check.permission:orders.create');
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('check.permission:orders.update_status');
        Route::put('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('check.permission:orders.update_payment');
        Route::post('/multi-cancel', [OrderController::class, 'multiCancel'])->middleware('check.permission:orders.cancel');
    });

    // ---Role & Permission---
    Route::prefix('roles')->middleware('check.permission:roles.manage')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/', [RoleController::class, 'store'])->name('roles.store');
        Route::delete('/multi-delete', [RoleController::class, 'multiDelete'])->name('roles.multi-delete');;
        Route::get('/{role}', [RoleController::class, 'show'])->name('roles.show');;
        Route::put('/{id}', [RoleController::class, 'update'])->name('roles.update');;
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('check.permission:roles.manage');
    Route::post('assign-role/{id}', [PermissionController::class, 'assignRolesToUser'])->middleware('check.permission:roles.manage');
    Route::post('assign-permission/{id}', [PermissionController::class, 'assignPermissionsToUser'])->middleware('check.permission:roles.manage');


    //---Dashboard---
    Route::get('/dashboard', [DashboardController::class, 'getStatistics'])->middleware('check.permission:dashboard.view');
});
