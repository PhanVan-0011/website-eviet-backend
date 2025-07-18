<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Promotion;
use App\Models\Slider;
use App\Models\Post;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Order;
use Spatie\Permission\Models\Role;

class SelectListController extends Controller
{

    private function formatImages($images)
    {
        if (!$images || $images->isEmpty()) {
            return []; 
        }

        return $images->map(function ($image) {
            $basePath = $image->image_url;
            if (!$basePath) {
                return null;
            }
            
            $directory = dirname($basePath);
            $fileName = basename($basePath);
            return [
                'id' => $image->id,
                'is_featured' => $image->is_featured,
                'thumb_url' => "{$directory}/thumb/{$fileName}",
                'main_url' => "{$directory}/main/{$fileName}",
                'base_path' => $basePath,
                'created_at' => $image->created_at ? $image->created_at->format('Y-m-d H:i:s') : null,
            ];
        })->filter()->values(); 
    }

    /**
     * Trả về danh sách sản phẩm rút gọn.
     */
    public function products()
    {
        $products = Product::where('status', 1) 
                       ->with('images') 
                       ->latest()
                       ->get();
        
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'product_code' => $product->product_code, 
                'image_urls' => $this->formatImages($product->images)
            ];
        });

        return response()->json($data);
    }

    /**
     * Trả về danh sách danh mục rút gọn (không có ảnh).
     */
    public function categories()
    {
        $data = Category::where('status', 1)
                        ->select('id', 'name')
                        ->latest()
                        ->get();

       return response()->json($data);
    }

    /**
     * Trả về danh sách combo rút gọn.
     */
    public function combos()
    {
        $combos = Combo::where('is_active', 1)
                       ->with('images')
                       ->latest()
                       ->get();

        $data = $combos->map(function ($combo) {
            return [
                'id' => $combo->id,
                'name' => $combo->name,
                'image_urls' => $this->formatImages($combo->images),
            ];
        });
        
        return response()->json($data);
    }

    /**
     * Trả về danh sách khuyến mãi rút gọn.
     */
    public function promotions()
    {
        $data = Promotion::where('is_active', 1)
                         ->select('id', 'name', 'code', 'application_type') 
                         ->orderBy('name')
                         ->get();

        return response()->json($data);
    }

    /**
     * Trả về danh sách slider rút gọn.
     */
    public function sliders()
    {
        $sliders = Slider::where('is_active', 1) 
                       ->with('image')
                       ->latest()
                       ->get();

        $data = $sliders->map(function ($slider) {
            $imageUrl = null;
            if ($slider->image && $slider->image->image_url) {
                $basePath = $slider->image->image_url;
                $directory = dirname($basePath);
                $fileName = basename($basePath);
                $thumbPath = "{$directory}/thumb/{$fileName}";
                $imageUrl = asset('storage/' . $thumbPath);
            }

            return [
                'id' => $slider->id,
                'title' => $slider->title, 
                'image_url' => $imageUrl,
            ];
        });

        return response()->json($data);
    }
    
    /**
     * Trả về danh sách bài viết rút gọn.
     */
    public function posts()
    {
        $posts = Post::where('status', 1)
                     ->with('images')
                     ->latest()
                     ->get();

        $data = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'featured_image' => $this->formatImages($post->images),
            ];
        });

        return response()->json($data);
    }

    /**
     * Trả về danh sách phương thức thanh toán rút gọn.
     */
    public function paymentMethods()
    {
        $data = PaymentMethod::where('is_active', 1)
                             ->select('id', 'name', 'code')
                             ->orderBy('name')
                             ->get();

        return response()->json($data);
    }

    /**
     * Trả về danh sách người dùng (nhân viên) rút gọn.
     */
    public function users()
    {
        $users = User::whereDoesntHave('roles')
                    ->with('image')
                    ->orderBy('name')
                    ->get();

        $data = $users->map(function ($user) {
            $imageObject = null;
            if ($user->image && $user->image->image_url) {
                $basePath = $user->image->image_url;
                $directory = dirname($basePath);
                $fileName = basename($basePath);
                
                $imageObject = [
                    'id' => $user->image->id,
                    'is_featured' => $user->image->is_featured,
                    'thumb_url' => "{$directory}/thumb/{$fileName}",
                    'main_url' => "{$directory}/main/{$fileName}",
                ];
            }
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'image_url' => $imageObject,
            ];
        });

        return response()->json($data);
    }

    /**
     * Trả về danh sách các vai trò.
     */
    public function roles()
    {
        $data = Role::where('name', '!=', 'super-admin')
                    ->select('id', 'display_name as name')
                    ->orderBy('name')
                    ->get();

        return response()->json($data);
    }
     /**
     * Trả về danh sách đơn hàng rút gọn.
     */
    public function orders()
    {
        $data = Order::select('id', 'order_code')
                     ->latest()
                     ->get();

        return response()->json($data);
    }
}
