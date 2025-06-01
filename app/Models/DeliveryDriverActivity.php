<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDriverActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_person_id',
        'order_id',
        'accepted_at',
        'delivered_canceled_at',
        'status',
    ];

    public function deliveryPerson()
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
