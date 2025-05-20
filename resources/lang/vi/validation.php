<?php

return [
    'required' => ':attribute không được để trống.',
    'email' => ':attribute phải là địa chỉ email hợp lệ.',
    'unique' => ':attribute đã được sử dụng.',
    'min' => [
        'string' => ':attribute phải có ít nhất :min ký tự.',
    ],
    'max' => [
        'string' => ':attribute không được vượt quá :max ký tự.',
    ],
    'confirmed' => ':attribute xác nhận không khớp.',

    'attributes' => [
        'email' => 'Email',
        'phone' => 'Số điện thoại',
        'password' => 'Mật khẩu',
        'name' => 'Họ tên',
        'address' => 'Địa chỉ',
        'gender' => 'Giới tính',
    ],
];
?>