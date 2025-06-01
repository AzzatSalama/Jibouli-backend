<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionOnOrder extends Model
{
    use HasFactory;

    protected $table = 'action_on_order';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'action',
        'details',
        'action_performed_at',
    ];

    protected $casts = [
        'action_performed_at' => 'datetime',
    ];
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}