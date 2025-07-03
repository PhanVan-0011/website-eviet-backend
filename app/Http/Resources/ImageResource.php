<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Tách đường dẫn gốc thành thư mục và tên file
        $directory = dirname($this->image_url);
        $fileName = basename($this->image_url);

        return [
            'id' => $this->id,
            'is_featured' => $this->is_featured,
            
            
            // Trả về các đường dẫn tương đối, bắt đầu từ thư mục gốc của storage
            'thumb_url' => "{$directory}/thumb/{$fileName}",
            'main_url' => "{$directory}/main/{$fileName}",

            // Giữ lại đường dẫn gốc nếu cần
            'base_path' => $this->image_url, 
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
