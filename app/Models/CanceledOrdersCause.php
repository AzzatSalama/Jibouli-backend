<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanceledOrdersCause extends Model
{
    use HasFactory;

    protected $table = 'canceled_order_cause';

    protected $fillable = [
        'cause',
        'order_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}