<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'due_date',
        'status',
        'priority',
        'progress',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(Employee::class, 'assigned_by'); // ou User
    }

    public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class, 'task_assignment_id');
    }
}
