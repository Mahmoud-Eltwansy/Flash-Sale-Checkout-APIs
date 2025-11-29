<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['product_id', 'hold_id', 'quantity', 'total_price', 'status'];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }
}
