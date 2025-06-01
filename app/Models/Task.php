<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'task';

    protected $casts = [
        'task_status' => 'string',
    ];

    protected $fillable = [
        'order_id',
        'assigned_employee_id',
        'task_status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}