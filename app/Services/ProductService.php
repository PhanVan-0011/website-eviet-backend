<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductService
{
    /**
     * Lấy danh sách tất cả sản phẩm (có phân trang)
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws Exception
     */
    public function getAllProducts($perPage = 10)
    {
        try {
            return Product::with('category')->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error retrieving products: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Lấy thông tin chi tiết một sản phẩm
     *
     * @param int $id
     * @return Product|null
     * @throws Exception
     */
    public function getProductById($id)
    {
        try {
            $product = Product::with('category')->find($id);
            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }
            return $product;
        } catch (Exception $e) {
            Log::error('Error retrieving product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một sản phẩm
     *
     * @param array $data
     * @return Product
     * @throws Exception
     */
    public function createProduct(array $data)
    {
        try {
            // Kiểm tra giá nếu cả original_price và sale_price đều được cung cấp
            if (isset($data['original_price'], $data['sale_price']) && $data['sale_price'] > $data['original_price']) {
                throw new Exception('Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc');
            }
    
            $product = Product::create($data);
            $product->load('category');
            return $product;
        } catch (Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Cập nhật thông tin một sản phẩm
     *
     * @param int $id
     * @param array $data
     * @return Product
     * @throws Exception
     */
    public function updateProduct($id, array $data)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }
            $product->update($data);
            $product->load('category');
            return $product;
        } catch (Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một sản phẩm
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteProduct($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }
            return $product->delete();
        } catch (Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            throw $e;
        }
    }
}
