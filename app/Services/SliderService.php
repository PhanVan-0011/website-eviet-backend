<?php

namespace App\Services;

use App\Models\Slider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class SliderService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function getAllSliders($request): array
    {
        try {

            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = trim((string) $request->input('keyword', ''));
            $isActive = $request->input('is_active'); // bool hoặc null
            $linkableType = $request->input('linkable_type');

            $query = Slider::query();
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
            if (!is_null($isActive)) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }
            if (!empty($linkableType)) {
                $modelClass = $this->mapLinkableTypeToModelClass($linkableType);
                if ($modelClass) {
                    $query->where('linkable_type', $modelClass);
                }
            }
            $query->orderBy('created_at', 'desc');
            $total = $query->count();

            $offset = ($currentPage - 1) * $perPage;
            $sliders = $query->with(['linkable', 'image'])->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $sliders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sliders: ' . $e->getMessage(), ['request' => $request->all()]);
            throw $e;
        }
    }

    public function getSliderById(int $id): Slider
    {
        try {

            return Slider::with(['linkable', 'image'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy slider với ID: {$id}");
            throw $e;
        }
    }

    public function createSlider(array $data): Slider
    {
        try {
            return DB::transaction(function () use ($data) {
                // Xử lý linkable_type và linkable_id
                if (isset($data['linkable_type']) && !empty($data['linkable_type'])) {
                    // Nếu có linkable_type, map sang model class
                    $data['linkable_type'] = $this->mapLinkableTypeToModelClass($data['linkable_type']);
                } else {
                    // Nếu không có linkable_type hoặc là null, set cả hai về null
                    $data['linkable_type'] = null;
                    $data['linkable_id'] = null;
                }

                $imageFile = $data['image_url'] ?? null;
                unset($data['image_url']);

                $slider = Slider::create($data);
                if ($imageFile) {
                    $basePath = $this->imageService->store($imageFile, 'sliders', $slider->title);
                    if ($basePath) {
                        $slider->image()->create([
                            'image_url'   => $basePath,
                            'is_featured' => 1
                        ]);
                    }
                }
                return $slider->load(['linkable', 'image']);
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo mới slider: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    public function updateSlider(int $id, array $data)
    {
        try {
            $slider = $this->getSliderById($id);
            return DB::transaction(function () use ($slider, $data) {
                $imageFile = $data['image_url'] ?? null;
                unset($data['image_url']);

                if ($imageFile) {
                    if ($oldImagePath = $slider->image?->image_url) {
                        $this->imageService->delete($oldImagePath, 'sliders');
                    }
                    $newBasePath = $this->imageService->store($imageFile, 'sliders', $data['title'] ?? $slider->title);
                    if ($newBasePath) {
                        $slider->image()->updateOrCreate(
                            ['imageable_id' => $slider->id],
                            ['image_url' => $newBasePath, 'is_featured' => 1]
                        );
                    }
                }
                if (array_key_exists('linkable_type', $data)) {
                    if (!empty($data['linkable_type'])) {
                        $data['linkable_type'] = $this->mapLinkableTypeToModelClass($data['linkable_type']);
                    } else {
                        // Nếu linkable_type được set về null, set cả linkable_id về null
                        $data['linkable_type'] = null;
                        $data['linkable_id'] = null;
                    }
                }
                unset($data['image_url']);
                $slider->update($data);
                return $slider->load(['linkable', 'image']);
            });
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy slider để cập nhật với ID: {$id}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật slider ID {$id}: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }
    public function delete(int $id): bool
    {
        try {
            $slider = $this->getSliderById($id);
            return DB::transaction(function () use ($slider) {
                if ($imagePath = $slider->image?->image_url) {
                    $this->imageService->delete($imagePath, 'sliders');
                }
                $slider->image()->delete();
                return $slider->delete();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy slider để xóa với ID: {$id}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa slider ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
    public function deleteMultiple(array $ids): int
    {
        try {
            return DB::transaction(function () use ($ids) {
                $sliders = Slider::with('image')->whereIn('id', $ids)->get();
                $deletedCount = 0;

                if ($sliders->count() !== count($ids)) {
                    throw new ModelNotFoundException('Một hoặc nhiều ID slider không tồn tại.');
                }

                foreach ($sliders as $slider) {
                    if ($image = $slider->image) {
                        $this->imageService->delete($image->image_url, 'sliders');
                    }
                    $slider->image()->delete();
                    if ($slider->delete()) {
                        $deletedCount++;
                    }
                }

                if ($deletedCount > 0) {
                    Log::info("{$deletedCount} slider đã được xóa thành công.");
                }

                return $deletedCount;
            });
        } catch (ModelNotFoundException $e) {
            Log::warning($e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhiều slider: " . $e->getMessage(), ['ids' => $ids]);
            throw $e;
        }
    }
    /**
     * Lấy danh sách sliders không có loại liên kết (linkable_type và linkable_id đều null)
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getSlidersWithoutLinkable($request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $isActive = $request->input('is_active'); // bool hoặc null

            $query = Slider::whereNull('linkable_type')
                ->whereNull('linkable_id');

            if (!is_null($isActive)) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }

            $query->orderBy('display_order', 'asc')
                ->orderBy('created_at', 'desc');

            $total = $query->count();
            $sliders = $query->with(['image'])
                ->skip(($currentPage - 1) * $perPage)
                ->take($perPage)
                ->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $sliders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sliders không có linkable: ' . $e->getMessage(), ['request' => $request->all()]);
            throw $e;
        }
    }

    private function mapLinkableTypeToModelClass(?string $type): ?string
    {
        if (!$type) return null;

        $map = [
            'product' => \App\Models\Product::class,
            'combo' => \App\Models\Combo::class,
            'post' => \App\Models\Post::class,
        ];

        return $map[$type] ?? null;
    }
}
