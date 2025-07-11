<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'address' => 'required|string|max:255' ,
        ];
    }
    /**
     * Tùy chỉnh thông điệp lỗi cho các quy tắc validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên.',
            'name.string' => 'Tên phải là chuỗi ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',

            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',

            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10 chữ số.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',

            'gender.required' => 'Vui lòng chọn giới tính.',
            'gender.in' => 'Giới tính phải là male, female hoặc other.',

            'date_of_birth.required' => 'Vui lòng nhập ngày sinh.',
            'date_of_birth.date' => 'Ngày sinh phải là định dạng ngày hợp lệ.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại.',

            'address.required' => 'Vui lòng nhập địa chỉ.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
        ];
    }

    /**
     * Chuẩn hóa dữ liệu trước khi thực hiện validation.
     * Loại bỏ khoảng trắng thừa ở các trường name, email, phone.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => trim($this->input('email')),
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => trim($this->input('phone')),
            ]);
        }
    }

    /**
     * Tùy chỉnh dữ liệu đã validate trước khi trả về.
     * Chuyển các trường nullable (gender, date_of_birth) thành null nếu rỗng.
     *
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Chuyển chuỗi rỗng thành null cho các trường nullable
        if (empty($validated['gender'])) {
            $validated['gender'] = null;
        }

        if (empty($validated['date_of_birth'])) {
            $validated['date_of_birth'] = null;
        }

        return $validated;
    }

    /**
     * Tùy chỉnh phản hồi khi validation thất bại.
     * Trả về JSON với định dạng: success: false, message, errors.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
