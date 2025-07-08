<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ComboItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\OrderDetail;


class Combo extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'slug',
        'start_date',
        'end_date',
        'is_active'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];
    /**
     * Các thuộc tính ảo sẽ được thêm vào khi model được chuyển đổi thành mảng/JSON.
     *
     * @var array
     */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($combo) {
            // Nếu slug chưa được đặt, tạo nó từ name
            if (empty($combo->slug)) {
                $combo->slug = Str::slug($combo->name);
            }

            // Kiểm tra và đảm bảo slug là duy nhất
            $originalSlug = $combo->slug;
            $counter = 1;
            while (static::where('slug', $combo->slug)->exists()) {
                $combo->slug = $originalSlug . '-' . $counter++;
            }
        });

        static::updating(function ($combo) {
            // Nếu name thay đổi, tạo lại slug
            if ($combo->isDirty('name')) {
                $combo->slug = Str::slug($combo->name);
                
                // Kiểm tra và đảm bảo slug là duy nhất khi cập nhật
                $originalSlug = $combo->slug;
                $counter = 1;
                // Bỏ qua chính nó khi kiểm tra
                while (static::where('slug', $combo->slug)->where('id', '!=', $combo->id)->exists()) {
                    $combo->slug = $originalSlug . '-' . $counter++;
                }
            }
        });
    }
    public function items()
    {
        return $this->hasMany(ComboItem::class);
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'combo_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_combos')->withTimestamps();
    }

     public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'combo_id');
    }

    /**
     * Mối quan hệ đa hình: Lấy ảnh của combo.h.
     */
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Scope để chỉ lấy các combo đang hoạt động.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope để lấy các combo đang trong thời gian áp dụng.r
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->where('start_date', '<=', $now)
                ->orWhereNull('start_date');
        })->where(function ($q) use ($now) {
            $q->where('end_date', '>=', $now)
                ->orWhereNull('end_date');
        });
    }
}
