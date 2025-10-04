<?php

namespace App\Http\Requests\Api\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
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
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($this->route('id')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->ignore($this->route('id')),
            ],
            'address' => 'sometimes|nullable|string|max:255',
            'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('branches', 'phone_number')->ignore($this->route('id')),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('branches', 'email')->ignore($this->route('id')),
            ],
            'active' => 'sometimes|nullable|boolean',
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
            'name.unique' => 'Tên chi nhánh đã tồn tại.',

            'address.string' => 'Địa chỉ phải là chuỗi.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            
            'phone_number.string' => 'Số điện thoại phải là chuỗi.',
            'phone_number.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'phone_number.unique' => 'Số điện thoại đã tồn tại.',
            
            'email.email' => 'Email phải đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email đã tồn tại.',
            
            'active.boolean' => 'Trạng thái hoạt động phải là hoạt động hoặc không.',
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
