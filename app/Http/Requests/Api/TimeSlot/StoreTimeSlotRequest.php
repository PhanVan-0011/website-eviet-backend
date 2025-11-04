<?php

namespace App\Http\Requests\Api\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeSlotRequest extends FormRequest
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
        Đã hiểu. Chúng ta sẽ tạo 3 file Request cho module TimeSlot, và sẽ dùng file MultiDeleteProductRequest của bạn làm mẫu cho MultiDeleteTimeSlotRequest (truyền ID qua URL).

Dưới đây là code cho 3 file Request bạn cần:

1. StoreTimeSlotRequest
Đây là file để validate khi tạo mới một khung giờ.

Tạo file: app/Http/Requests/Api/TimeSlot/StoreTimeSlotRequest.php

PHP

<?php

namespace App\Http\Requests\Api\TimeSlot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // (Giả sử admin đã đăng nhập)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:order_time_slots,name',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'delivery_time' => 'required|date_format:H:i:s',
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
