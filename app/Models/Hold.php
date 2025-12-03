<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'quantity', 'status', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function scopeExpiredAndActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '<=', now());
    }



    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
