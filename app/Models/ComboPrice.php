<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboPrice extends Model
{
    use HasFactory;
    protected $fillable = [
        'combo_id',
        'branch_id',
        'price_type',
        'price',
        'start_date',
        'end_date',
    ];
    // Mối quan hệ: Một mức giá thuộc về một combo
    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    // Mối quan hệ: Một mức giá thuộc về một chi nhánh
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
