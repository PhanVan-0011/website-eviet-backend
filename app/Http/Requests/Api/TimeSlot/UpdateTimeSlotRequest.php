<?php

namespace App\Http\Requests\Api\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTimeSlotRequest extends FormRequest
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
        $timeSlotId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('order_time_slots', 'name')->ignore($timeSlotId)
            ],
            'start_time' => 'sometimes|required|date_format:H:i:s',
            'end_time' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
                // Chỉ kiểm tra 'after' nếu 'start_time' cũng được gửi
                Rule::when($this->filled('start_time'), 'after:start_time'),
            ],
            'delivery_time' => 'sometimes|required|date_format:H:i:s',
            'is_active' => 'sometimes|boolean',
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
            'name.required' => 'Tên khung giờ là bắt buộc.',
            'name.unique' => 'Tên khung giờ này đã tồn tại.',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc.',
            'start_time.date_format' => 'Giờ bắt đầu phải có định dạng HH:MM:SS.',
            'end_time.required' => 'Giờ kết thúc là bắt buộc.',
            'end_time.date_format' => 'Giờ kết thúc phải có định dạng HH:MM:SS.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'delivery_time.required' => 'Giờ giao hàng dự kiến là bắt buộc.',
            'delivery_time.date_format' => 'Giờ giao hàng phải có định dạng HH:MM:SS.',
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
