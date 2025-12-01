<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Lấy danh sách chi nhánh mà user này quản lý/thuộc về
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id')
                    ->withPivot('role_in_branch', 'is_primary')
                    ->withTimestamps();
    }

    /**
     * Kiểm tra user có quyền truy cập branchId không
     */
    public function hasBranchAccess($branchId): bool
    {
        // Super Admin có quyền với mọi chi nhánh
        if ($this->hasRole('super-admin')) {
            return true;
        }
        return $this->branches()->where('branches.id', $branchId)->exists();
    }
}
