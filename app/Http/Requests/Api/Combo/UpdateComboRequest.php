<?php

namespace App\Http\Requests\Api\Combo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Combo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateComboRequest extends FormRequest
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
        //dd($this->route('id')); 
        $comboId = $this->route('id');
        return [
            'name'                  => ['sometimes', 'required', 'string', 'max:255', Rule::unique('combos', 'name')->ignore($comboId)],
            'slug'                  => ['sometimes', 'required', 'string', 'max:255', Rule::unique('combos', 'slug')->ignore($comboId)],
            'description'           => 'sometimes|nullable|string|max:255',
            'price'                 => 'sometimes|required|numeric|min:0',

            'image_url'             => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    // Cho phép null hoặc empty để xóa ảnh
                    if (is_null($value) || $value === '') {
                        return;
                    }

                    // Kiểm tra là UploadedFile và upload thành công
                    if (!($value instanceof \Illuminate\Http\UploadedFile) || !$value->isValid()) {
                        $fail('File tải lên không hợp lệ.');
                        return;
                    }

                    // Kiểm tra kích thước (2MB)
                    if ($value->getSize() > 2048 * 1024) {
                        $fail('Kích thước ảnh không được vượt quá 2MB.');
                        return;
                    }

                    // Kiểm tra có phải ảnh hợp lệ (JPEG, PNG, GIF)
                    $imageInfo = @getimagesize($value->getPathname());
                    if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
                        $fail('File phải là ảnh định dạng JPEG, PNG hoặc GIF.');
                        return;
                    }
                }
            ],
            'start_date'            => 'sometimes|nullable|date',
            'end_date'              => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_active'             => 'sometimes|boolean',
            'items'                 => 'sometimes|required|array|min:1',
            'items.*.product_id'    => 'required_with:items|integer|exists:products,id',
            'items.*.quantity'      => 'required_with:items|integer|min:1',
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Tên combo là bắt buộc.',
            'name.string' => 'Tên combo phải là chuỗi ký tự.',
            'name.max' => 'Tên combo không được vượt quá 200 ký tự.',
            'name.unique'   => 'Tên combo này đã tồn tại.',

            'description.string' => 'Mô tả phải là chuỗi.',
            'description.max' => 'Tên combo không được vượt quá 255 ký tự.',

            'price.required' => 'Giá combo là bắt buộc.',
            'price.numeric' => 'Giá combo phải là số.',
            'price.min' => 'Giá combo không được âm.',

            'slug.string' => 'Slug phải là chuỗi.',
            'slug.unique' => 'Slug này đã tồn tại.',

            // Các message cho image_url đã được xử lý trong custom validation

            'start_date.date' => 'Ngày bắt đầu phải đúng định dạng ngày.',
            'end_date.date' => 'Ngày kết thúc phải đúng định dạng ngày.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'is_active.boolean' => 'Trạng thái kích hoạt phải là true hoặc false.',

            'items.required' => 'Danh sách sản phẩm là bắt buộc.',
            'items.array' => 'Danh sách sản phẩm phải là một mảng.',
            'items.min' => 'Phải có ít nhất một sản phẩm trong combo.',

            'items.*.product_id.required' => 'ID sản phẩm là bắt buộc.',
            'items.*.product_id.integer' => 'ID sản phẩm phải là số nguyên.',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',

            'items.*.quantity.required' => 'Số lượng là bắt buộc.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
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
