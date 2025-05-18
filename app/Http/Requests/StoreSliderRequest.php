<?php

namespace App\Http\Requests;

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
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string|max:255',
            'image_url' => 'sometimes|required|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'display_order' => 'required|integer|unique:sliders,display_order',
            'is_active' => 'boolean',
            'link_type' => [Rule::in(['promotion', 'post', 'product'])],
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'Tiêu đề là bắt buộc.',
            'image_url.required' => 'Đường dẫn ảnh là bắt buộc.',
            'display_order.required' => 'Thứ tự hiển thị là bắt buộc.',
            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'display_order.unique' => 'Thứ tự hiển thị không được trùng nhau.',
            'link_type.in' => 'Loại liên kết không hợp lệ.',
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
