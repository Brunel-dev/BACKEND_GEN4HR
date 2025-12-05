<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Task extends Model {
    protected $fillable = [
        'company_id', 'assigned_to', 'title', 'description',
        'status', 'due_date'
    ];
    protected $casts = ['due_date' => 'datetime:Y-m-d H:i:s']; // ou 'time' si tu veux juste l'heure
    public function company() { return $this->belongsTo(Company::class); }
    public function assignedTo() { return $this->belongsTo(Employee::class, 'assigned_to'); }
}
