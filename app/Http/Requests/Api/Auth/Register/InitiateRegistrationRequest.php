<?php

namespace App\Http\Requests\Api\Auth\Register;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\User;
class InitiateRegistrationRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/']
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
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.string' => 'Số điện thoại phải là một chuỗi ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng. Vui lòng kiểm tra lại.',
        ];
    }
     public function passedValidation()
    {
        $user = User::withTrashed()->where('phone', $this->input('phone'))->first();
        if ($user) {
            $message = $user->trashed() ? 'Tài khoản của bạn đã bị vô hiệu hóa.' : 'Số điện thoại đã được đăng ký.';
            $action = $user->trashed() ? 'contact_support' : 'login';
            $statusCode = $user->trashed() ? 403 : 409;

            throw new HttpResponseException(
                response()->json(['success' => false, 'message' => $message, 'action' => $action], $statusCode)
            );
        }
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
