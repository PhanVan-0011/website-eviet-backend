<?php

namespace App\Http\Requests\Api\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
    * Chuẩn bị dữ liệu (chuyển chuỗi '1,2,3' từ URL thành mảng).
    */
   protected function prepareForValidation(): void
    {
        // Sử dụng $this->query('ids') để lấy từ URL
        // Hoặc $this->input('ids') để lấy từ URL hoặc body
        $ids = $this->input('ids');

        if ($ids && is_string($ids)) {
            $this->merge([
                'ids' => array_filter(array_map('intval', explode(',', $ids))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:order_time_slots,id'
        ];
    }

     /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
     public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID khung giờ cần xóa.',
            'ids.array' => 'Định dạng danh sách ID không hợp lệ.',
            'ids.*.integer' => 'Mỗi ID trong danh sách phải là một số nguyên.',
            'ids.*.exists' => 'Một trong các ID khung giờ không tồn tại.', // Đổi 'sản phẩm' thành 'khung giờ'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
