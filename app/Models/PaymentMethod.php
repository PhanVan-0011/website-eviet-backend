<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'description',
        'logo_url',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
