<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    protected $fillable = [
        'task_assignment_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(TaskAssignment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }
}
