<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone',
            'password' => 'required|string|min:6',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'required|string|max:255|regex:/^[\p{L}\p{N}\s,.-]+$/u',
            'is_active' => 'nullable|boolean',
            // Co thể null hoặc là 0 hoặc 1 int hoặc string (true hoặc false)
        ];

        // Nếu là update thì bỏ unique cho email và phone
        if ($this->isMethod('PUT')) {
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $this->route('id');
            $rules['phone'] = 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone,' . $this->route('id');
            $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên không được để trống',
            'name.max' => 'Tên không được vượt quá 255 ký tự',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.regex' => 'Số điện thoại không đúng định dạng',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'gender.in' => 'Giới tính không hợp lệ',
            'date_of_birth.date' => 'Ngày sinh không đúng định dạng',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'address.regex' => 'Địa chỉ chỉ được chứa chữ cái, số, dấu cách, dấu phẩy, dấu chấm và dấu gạch ngang.',
            'is_active.boolean' => 'Trạng thái không hợp lệ',
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
