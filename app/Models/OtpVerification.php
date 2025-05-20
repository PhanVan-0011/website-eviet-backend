<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone_number',
        'otp_code',
        'used',
        'expire_at',
        'purpose',
        'user_id',
    ];
    protected $casts = [
        'expire_at' => 'datetime',
        'used' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
