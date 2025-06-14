<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;
use App\Models\Combo;
use App\Models\Post;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ComboResource;
use App\Http\Resources\PostResource;

class SliderResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,

            'display_order' => $this->display_order,
            'is_active' => $this->is_active,

            'linkable_id' => $this->linkable_id,
            'linkable_type' => $this->linkable_type,
            'linkable_data' => $this->whenLoaded('linkable', function () {
                $linkable = $this->linkable;

                if ($linkable instanceof Product) {
                    // ProductResource
                    return new ProductResource($linkable);
                }
                if ($linkable instanceof Combo) {
                    //ComboResource
                    return new ComboResource($linkable);
                }
                if ($linkable instanceof Post) {
                    //PostResource
                    return new PostResource($linkable);
                }
                return null;
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
