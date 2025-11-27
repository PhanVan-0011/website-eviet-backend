<?php

namespace App\Http\Requests\Api\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBranchRequest extends FormRequest
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
                'code' => 'required|string|max:50|unique:branches,code',
                'name' => 'required|string|max:255|unique:branches,name',
                'address' => 'nullable|string|max:255',
                'phone_number' => [
                    'nullable','string','max:20','unique:branches,phone_number',
                    'regex:#^(0|\+84)[3-9][0-9]{8}$#',
                ],
                'email' => 'nullable|email|max:255|unique:branches,email',
                'active' => 'boolean',
                'time_slot_ids' => 'nullable|array',
                'time_slot_ids.*' => 'required|integer|exists:order_time_slots,id'
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
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.string' => 'Mã chi nhánh phải là chuỗi.',
            'code.max' => 'Mã chi nhánh không được vượt quá 50 ký tự.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'name.string' => 'Tên chi nhánh phải là chuỗi.',
            'name.max' => 'Tên chi nhánh không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên chi nhánh này đã tồn tại.',
            
            'address.string' => 'Địa chỉ phải là chuỗi.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            
            'phone_number.string' => 'Số điện thoại phải là chuỗi.',
            'phone_number.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'phone_number.unique' => 'Số điện thoại chi nhánh này đã tồn tại.',
            'phone_number.regex' => 'Số điện thoại không đúng định dạng.',

            'email.email' => 'Email phải đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email chi nhánh này đã tồn tại.',
            'email.email' => 'Email không đúng định dạng.',
            
            'active.boolean' => 'Trạng thái hoạt động phải là đúng hoặc sai.',
            
            'time_slot_ids.array' => 'Định dạng danh sách khung giờ không hợp lệ.',
            'time_slot_ids.*.exists' => 'Một trong các khung giờ được chọn không tồn tại.'
        ];
    }
    
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
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
