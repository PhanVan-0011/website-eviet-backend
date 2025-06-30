<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'address',
        'image_url',
        'is_active',
        'is_verified',
        'last_login_at',
        'date_of_birth',
        'email_verified_at',
        'phone_verified_at',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

     /**
     * 2. Thêm thuộc tính "ảo" image_full_url vào model.
     *
     * @var array
     */
    protected $appends = ['image_full_url'];

    /**
     * 3. Tạo Accessor để tự động sinh ra URL đầy đủ cho ảnh.
     *
     * @return string|null
     */
    public function getImageFullUrlAttribute(): ?string
    {
        // Kiểm tra xem cột image_url có giá trị không
        if ($this->image_url) {
            // Giả sử bạn lưu ảnh trong public disk
            // và đã chạy 'php artisan storage:link'
            return asset('storage/' . $this->image_url);
        }
        
        // Nếu không có ảnh, trả về một ảnh đại diện mặc định
        // Dịch vụ ui-avatars.com sẽ tự tạo ảnh từ tên người dùng
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date', 
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];
    public function otpVerifications()
    {
        return $this->hasMany(OtpVerification::class, 'user_id');
    }
}
