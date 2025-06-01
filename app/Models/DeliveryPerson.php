<?php

namespace App\Models;

use App\Models\Order;
use App\Models\CanceledOrdersCause;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryPerson extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_person';

    protected $fillable = [
        'delivery_person_name',
        'delivery_phone',
        'is_available',
        'balance',
        'longitude',
        'latitude',
        'user_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function canceledOrders()
    {
        return $this->hasOneThrough(
            CanceledOrdersCause::class,
            Order::class
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}