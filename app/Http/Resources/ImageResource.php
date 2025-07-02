<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
