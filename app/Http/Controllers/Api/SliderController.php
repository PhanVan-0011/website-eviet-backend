<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Slider\StoreSliderRequest;
use App\Http\Requests\Api\Slider\UpdateSliderRequest;
use App\Models\Slider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
class SliderController extends Controller
{
    // List slider
    public function index()
    {
        try {
            $slider = Slider::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get();
            return response()->json(['success' => true, 'data' => $slider], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false,  'message' => 'Slider không tồn tại'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
        }  
   }
    // Detail slider
    public function detail($id)
    {
        try {
            $slider = Slider::findOrFail($id);
            return response()->json(['success' => true, 'data' => $slider], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false,  'message' => 'Slider không tồn tại'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
        }  
   }
   // Add slider
   public function store(StoreSliderRequest $request)
   {
       try {
            $validated = $request->validated();
            $slider = Slider::create($validated);
            return response()->json(['success' => true, 'data' => $slider], 201);
       } catch (QueryException $e) {
           return response()->json(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu'], 500);
       } catch (\Exception $e) {
           Log::error('Lỗi khi tìm slider: ' . $e->getMessage());
           return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
       }
   }
    // Update slider
    public function update(UpdateSliderRequest $request, $id)
    {
        try {
            $slider = Slider::findOrFail($id);
            $validated = $request->validated();
            $slider->update($validated);
            return response()->json(['success' => true, 'data' => $slider]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Slider không tồn tại'], 404);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
        }
    }
    //Delete slider
    public function destroy($id)
    {
        try {
            $slider = Slider::findOrFail($id);
            $slider->delete();

            return response()->json(['success' => true, 'message' => 'Slider đã được xóa']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Slider không tồn tại'], 404);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
        }
    }

    

}
