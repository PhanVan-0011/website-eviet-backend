<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles, SoftDeletes;

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
        'is_active',
        'is_verified',
        'last_login_at',
        'date_of_birth',
        'email_verified_at',
        'phone_verified_at',
        'branch_id',
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
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Lấy chi nhánh mà người dùng thuộc về (cho Sales Staff - 1 chi nhánh).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Quan hệ nhiều-nhiều với branches (cho Branch Admin - đa chi nhánh).
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
                    ->withTimestamps();
    }

    /**
     * Kiểm tra user có quyền truy cập branch không.
     * Sử dụng BranchAccessService để tránh duplicate logic
     */
    public function hasAccessToBranch($branchId): bool
    {
        return \App\Services\BranchAccessService::hasAccessToBranch($branchId, $this);
    }

    /**
     * Lấy danh sách branch IDs mà user có quyền truy cập.
     * Sử dụng BranchAccessService để tránh duplicate logic
     */
    public function getAccessibleBranchIds(): array
    {
        return \App\Services\BranchAccessService::getAccessibleBranchIds($this);
    }
}
