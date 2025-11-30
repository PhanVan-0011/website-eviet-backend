<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Xác định xem đây là chi tiết hay danh sách
        // Nếu trả về content thì là chi tiết (dùng /main), nếu không thì là danh sách (dùng /thumb)
        // Kiểm tra xem có nên trả về content hay không (chi tiết sẽ luôn có content)
        $shouldIncludeContent = $this->content !== null && !empty(trim($this->content));
        $imageSize = $shouldIncludeContent ? 'main' : 'thumb';

        // Xử lý hình ảnh: lấy featured image hoặc ảnh đầu tiên
        $imageUrl = null;
        if ($this->relationLoaded('featuredImage') && $this->featuredImage) {
            $directory = dirname($this->featuredImage->image_url);
            $fileName = basename($this->featuredImage->image_url);
            $imageUrl = asset("{$directory}/{$imageSize}/{$fileName}");
        } elseif ($this->relationLoaded('images') && $this->images && $this->images->isNotEmpty()) {
            $firstImage = $this->images->first();
            $directory = dirname($firstImage->image_url);
            $fileName = basename($firstImage->image_url);
            $imageUrl = asset("{$directory}/{$imageSize}/{$fileName}");
        }

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $imageUrl,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];

        // Nếu có content (chi tiết bài viết), thêm vào
        if ($shouldIncludeContent) {
            $data['content'] = $this->content;
        }

        return $data;
    }
}

