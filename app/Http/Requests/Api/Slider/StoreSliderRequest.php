<?php

namespace App\Http\Requests\Api\Slider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSliderRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:255',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'nullable|string|max:255',
            'display_order' => 'required|integer|unique:sliders,display_order',
            'is_active' => 'boolean',
            'link_type' => [
                'nullable',
                'required_with:link_url',
                Rule::in(['promotion', 'post', 'product']),
            ],
            'combo_id' => 'nullable|exists:combos,id',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'Tiêu đề là bắt buộc.',
            'title.max' => 'Tiêu đề không được vượt quá 200 ký tự.',

            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',

            'link_url.max' => 'Link liên kết không được vượt quá 255 ký tự.',

            'image_url.required' => 'Đường dẫn ảnh là bắt buộc.',
            'image_url.image' => 'File phải là hình ảnh.',
            'image_url.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',

            'display_order.required' => 'Thứ tự hiển thị là bắt buộc.',
            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'display_order.unique' => 'Thứ tự hiển thị không được trùng nhau.',

            'link_type.in' => 'Loại liên kết không hợp lệ.',

            'combo_id.exists' => 'Combo không tồn tại.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
}
