<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;


class SliderController extends Controller
{
   // Lấy chi tiết slider
   public function show($id)
   {
       try {
           $slider = Slider::findOrFail($id);
           return response()->json(['success' => true, 'data' => $slider]);
       } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
           return response()->json(['success' => false, 'message' => 'Slider không tồn tại'], 404);
       } catch (\Exception $e) {
           return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
       }
   }
   // Thêm slider mới
   public function store(Request $request)
   {
       try {
           $validated = $request->validate([
               'title' => 'required|string|max:200',
               'description' => 'nullable|string|max:255',
               'image_url' => 'required|string|max:255',
               'link_url' => 'nullable|string|max:255',
               'display_order' => 'integer',
               'is_active' => 'boolean',
               'link_type' => ['required', Rule::in(['promotion', 'post', 'product'])],
           ]);

           $slider = Slider::create($validated);

           return response()->json(['success' => true, 'data' => $slider], 201);
       } catch (\Illuminate\Validation\ValidationException $e) {
           return response()->json(['success' => false, 'errors' => $e->errors()], 422);
       } catch (QueryException $e) {
           return response()->json(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu'], 500);
       } catch (\Exception $e) {
           return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
       }
   }
    // Sửa slider
    public function update(Request $request, $id)
    {
        try {
            $slider = Slider::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:200',
                'description' => 'nullable|string|max:255',
                'image_url' => 'sometimes|required|string|max:255',
                'link_url' => 'nullable|string|max:255',
                'display_order' => 'integer',
                'is_active' => 'boolean',
                'link_type' => [Rule::in(['promotion', 'post', 'product'])],
            ]);

            $slider->update($validated);

            return response()->json(['success' => true, 'data' => $slider]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Slider không tồn tại'], 404);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
        }
    }

    // Xóa slider
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
