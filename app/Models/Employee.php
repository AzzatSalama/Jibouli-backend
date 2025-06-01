<?php

namespace App\Models;

use App\Models\Task;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee'; // Specify table name

    protected $fillable = [
        'employee_name',
        'employee_phone',
        'status',
        'user_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_employee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}