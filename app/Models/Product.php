<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price', 'total_stock', 'reserved_stock'];

    public function getAvailableStockAttribute()
    {
        return $this->total_stock - $this->reserved_stock;
    }

    // Relations
    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
