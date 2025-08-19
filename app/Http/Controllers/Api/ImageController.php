<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\ImageService;

class ImageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Hiển thị hình ảnh từ storage
     *
     * @param string $path
     * @return BinaryFileResponse
     */
    public function show(string $path)
    {
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }

    public function uploadGeneric(Request $request)
    {
        try {
            if (!$request->hasFile('image')) {
                return response()->json(['success' => false, 'message' => 'Không có file ảnh'], 400);
            }
            $file = $request->file('image');
            // Kiểm tra định dạng ảnh
            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                return response()->json(['success' => false, 'message' => 'File phải là ảnh jpeg, png, gif, webp'], 400);
            }
            // Kiểm tra dung lượng dưới 2MB
            if ($file->getSize() > 2 * 1024 * 1024) {
                return response()->json(['success' => false, 'message' => 'Dung lượng ảnh phải nhỏ hơn 2MB'], 400);
            }
            $folder = $request->input('folder', 'posts'); // mặc định là posts
            $slug = $request->input('slug', pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME));

            // kiểm tra folder có main 
            $allowedFolders = ['posts', 'products', 'users', 'sliders', 'combos'];

            $basePath = $this->imageService->store($request->file('image'), $folder, $slug);
            if (!$basePath) {
                return response()->json(['success' => false, 'message' => 'Lưu ảnh thất bại'], 500);
            }

            if (in_array($folder, $allowedFolders, true)) {

                // Hoặc dùng regex đảm bảo format chính xác
                $mainPath = preg_replace('/^(.*?\/)\/?([^\/]+)$/', '$1main/$2', $basePath);

                return response()->json([
                    'success' => true,
                    'url' => $mainPath,
                ], 200);
            }

            // Nếu không nằm trong allowedFolders: trả về đường dẫn gốc
            return response()->json([
                'success' => true,
                'url' => $basePath,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa ảnh từ storage
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request)
    {
        try {
            $imagePath = $request->input('image_path');
            $folder = $request->input('folder');

            // Kiểm tra dữ liệu đầu vào
            if (!$imagePath || !$folder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin đường dẫn ảnh hoặc thư mục'
                ], 400);
            }

            // Kiểm tra thư mục có được phép xóa không
            $allowedFolders = ['posts', 'products', 'users', 'sliders', 'combos', 'promotions'];
            if (!in_array($folder, $allowedFolders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục không được phép xóa'
                ], 403);
            }

            // Xử lý đường dẫn ảnh nếu có chứa '/main/'
            $basePath = $imagePath;
            if (strpos($imagePath, '/main/') !== false) {
                $basePath = preg_replace('/\/main\//', '/', $imagePath);
            }

            // Gọi service để xóa ảnh
            $this->imageService->delete($basePath, $folder);

            return response()->json([
                'success' => true,
                'message' => 'Xóa ảnh thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa ảnh: ' . $e->getMessage()
            ], 500);
        }
    }
}
