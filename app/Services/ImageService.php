<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * Cấu hình các kích thước ảnh cho từng loại model.
     * @var array
     */
    protected $sizes = [
        'products' => [
            'main' => [800, 800],
            'thumb' => [300, 300]
        ],
        'posts' => [
            'main' => [900, 600],
            'thumb' => [400, 267]
        ],
        'combos' => [
            'main' => [600, 600],
            'thumb' => [200, 200]
        ],
        'sliders' => [
            'main' => [1600, 900],
            'thumb' => [400, 225],
        ],
        'users' => [
            'main' => [200, 200],
            'thumb' => [50, 50]
        ],
        'promotions' => [
            'main' => [800, 400],
            'thumb' => [400, 200]
        ],
        'categories' => [
            'main' => [100, 100],
            //'thumb' => [50, 50],
        ],
    ];
    /**
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * Xử lý upload, resize và lưu trữ một file ảnh.
     */
    public function store(UploadedFile $file, string $folder, string $slug): ?string
    {
        try {
            $year = now()->format('Y');
            $month = now()->format('m');
            $extension = $file->getClientOriginalExtension();
            $baseFileName = Str::slug($slug) . '-' . uniqid();

            $baseDir = "{$folder}/{$year}/{$month}";
            $basePath = "{$baseDir}/main/{$baseFileName}.{$extension}";

            // Xử lý file SVG riêng biệt
            if ($extension === 'svg') {
                Storage::disk('public')->put($basePath, file_get_contents($file));
                return $basePath;
            }

            $imageSizes = $this->sizes[$folder] ?? [];

            // Lưu các phiên bản ảnh đã resize
            $image = $this->imageManager->read($file->getRealPath());

            foreach ($imageSizes as $sizeName => $dimensions) {
                $fullPath = "{$baseDir}/{$sizeName}/{$baseFileName}.{$extension}";
                $resizedImage = $image->scale($dimensions[0], $dimensions[1])->encode();
                Storage::disk('public')->put($fullPath, $resizedImage);
            }
            
            return $basePath;
        } catch (\Exception $e) {
            Log::error("Lỗi khi xử lý ảnh: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Xóa tất cả các phiên bản của một ảnh.
     */
    public function delete(?string $basePath, string $folder): void
    {
         if (!$basePath) {
            return;
        }

        try {
            $ext = pathinfo($basePath, PATHINFO_EXTENSION);
            $baseFileName = pathinfo($basePath, PATHINFO_FILENAME);
            $pathParts = explode('/', $basePath);
            $year = $pathParts[1];
            $month = $pathParts[2];
            $baseDir = "{$folder}/{$year}/{$month}";

            $imageSizes = $this->sizes[$folder] ?? [];

            // Xóa file SVG
            if ($ext === 'svg') {
                $svgPath = "{$baseDir}/main/{$baseFileName}.{$ext}";
                if (Storage::disk('public')->exists($svgPath)) {
                    Storage::disk('public')->delete($svgPath);
                }
                return;
            }
            
            // Xóa tất cả biến thể (main, thumb,...)
            foreach (array_keys($imageSizes) as $sizeName) {
                $fullPath = "{$baseDir}/{$sizeName}/{$baseFileName}.{$ext}";
                if (Storage::disk('public')->exists($fullPath)) {
                    Storage::disk('public')->delete($fullPath);
                }
            }
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa ảnh: " . $e->getMessage());
        }
    }
}
