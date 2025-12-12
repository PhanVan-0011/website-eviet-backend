<?php

namespace App\Http\Requests\Api\PickupLocation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePickupLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pickupLocationId = $this->route('id'); // Lấy ID từ route parameter

        return [
            'branch_id' => 'sometimes|integer|exists:branches,id',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Tên phải duy nhất trong cùng 1 chi nhánh, trừ bản thân nó
                Rule::unique('pickup_locations', 'name')
                    ->where(function ($query) {
                        return $query->where('branch_id', $this->input('branch_id', $this->pickupLocation->branch_id ?? null));
                    })
                    ->ignore($pickupLocationId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.exists' => 'Chi nhánh không hợp lệ.',
            'name.required' => 'Tên điểm nhận hàng là bắt buộc.',
            'name.unique' => 'Tên điểm nhận hàng đã tồn tại trong chi nhánh này.',
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
