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
            
            $fileName = Str::slug($slug) . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $basePath = "{$folder}/{$year}/{$month}/{$fileName}";
            $imageSizes = $this->sizes[$folder] ?? [];

            if (empty($imageSizes)) {
                Storage::disk('public')->put($basePath, file_get_contents($file));
                return $basePath;
            }

            //Sử dụng đối tượng đã được tiêm vào
           $image = $this->imageManager->read($file->getRealPath());

            foreach ($imageSizes as $sizeName => $dimensions) {
                $fullPath = "{$folder}/{$year}/{$month}/{$sizeName}/{$fileName}";
                
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
            $imageSizes = $this->sizes[$folder] ?? [];

            if (empty($imageSizes)) {
                 if (Storage::disk('public')->exists($basePath)) {
                    Storage::disk('public')->delete($basePath);
                }
                return;
            }

            foreach (array_keys($imageSizes) as $sizeName) {
                // Tách đường dẫn để lấy tên file và thư mục cha
                $directory = dirname($basePath);
                $fileName = basename($basePath);
                
                $fullPath = "{$directory}/{$sizeName}/{$fileName}";

                if (Storage::disk('public')->exists($fullPath)) {
                    Storage::disk('public')->delete($fullPath);
                }
            }
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa ảnh: " . $e->getMessage());
        }
    }
}