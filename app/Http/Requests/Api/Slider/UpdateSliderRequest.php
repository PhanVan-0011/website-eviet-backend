<?php

namespace App\Http\Requests\Api\Slider;;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Slider;

class UpdateSliderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $linkableType = $this->input('linkable_type');

        // Ánh xạ 'type' sang tên bảng tương ứng trong database
        $tableName = match ($linkableType) {
            'product' => 'products',
            'combo' => 'combos',
            'post' => 'posts',
            default => null,
        };

        $sliderId = $this->route('id');
        
        $linkableIdRules = [
            'sometimes',
            'nullable',
            'required_with:linkable_type',
            'integer',
        ];
        
        // Chỉ thêm rule exists nếu có linkable_type hợp lệ
        if ($tableName) {
            $linkableIdRules[] = Rule::exists($tableName, 'id');
        }
        
        return [
            'title' => 'sometimes|required|string|max:200',
            'description' => 'sometimes|nullable|string|max:255',
            'image_url' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'display_order' => [
                'sometimes',
                'required',
                'integer',
                // Bỏ qua slider hiện tại khi kiểm tra unique
                Rule::unique('sliders')->ignore($sliderId),
            ],
            'is_active' => 'sometimes|boolean',

            'linkable_type' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['product', 'combo', 'post']),
            ],
            'linkable_id' => $linkableIdRules,
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'Tiêu đề là bắt buộc.',
            'title.max' => 'Tiêu đề không được vượt quá 200 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',

            'image_url.image' => 'File tải lên phải là hình ảnh.',
            'image_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước ảnh không được vượt quá 2MB.',

            'display_order.required' => 'Thứ tự hiển thị là bắt buộc.',
            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'display_order.unique' => 'Thứ tự hiển thị không được trùng nhau.',

            'linkable_type.in' => 'Loại liên kết không hợp lệ. Chỉ chấp nhận: product, combo, post.',
            'linkable_id.required_with' => 'ID đối tượng liên kết là bắt buộc khi có loại liên kết.',
            'linkable_id.integer' => 'ID đối tượng liên kết phải là số nguyên.',
            'linkable_id.exists' => 'Đối tượng liên kết không tồn tại trong hệ thống.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
