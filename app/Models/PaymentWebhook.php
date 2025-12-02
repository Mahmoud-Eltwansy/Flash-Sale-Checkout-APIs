<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;
    protected $primaryKey = 'idempotency_key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['idempotency_key', 'order_id', 'status', 'payload', 'response', 'processed_at'];

    protected $casts = [
        'processed_at' => 'datetime',
        'payload' => 'array'
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
