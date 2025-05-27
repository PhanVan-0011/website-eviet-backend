<?php

namespace App\Http\Requests;

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
            'password' => 'required|string|min:8',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
        ];

        // Nếu là update thì bỏ unique cho email và phone
        if ($this->isMethod('PUT')) {
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $this->route('id');
            $rules['phone'] = 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone,' . $this->route('id');
            $rules['password'] = 'nullable|string|min:8';
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
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'gender.in' => 'Giới tính không hợp lệ',
            'date_of_birth.date' => 'Ngày sinh không đúng định dạng',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi ràng buộc',
            'errors' => $validator->errors(),
        ], 422));
    }
}
