<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone',
        'otp_code',
        'used',
        'expire_at',
        'purpose',
    ];
    protected $casts = [
        'expire_at' => 'datetime',
        'used' => 'boolean',
    ];
}
