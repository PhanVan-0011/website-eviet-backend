<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
    ];

    // Mối quan hệ: Một nhóm có nhiều nhà cung cấp
    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'group_id');
    }
}
