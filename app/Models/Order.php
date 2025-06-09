<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'order';

    protected $casts = [
        'status' => 'string',
    ];

    protected $fillable = [
        'client_id',
        'user_id',
        'delivery_person_id',
        'status',
        'request',
        'client_notes',
        'user_notes',
        'delivery_person_notes',
        'partner_id',
        // 'accepted_at',
        'delivered_canceled_at',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveryPerson()
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id');
    }

    public function task()
    {
        return $this->hasOne(Task::class);
    }

    public function cancellationCause()
    {
        return $this->hasOne(CanceledOrdersCause::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function actionsOnOrder()
    {
        return $this->hasMany(ActionOnOrder::class);
    }
}
