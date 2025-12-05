<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected $fillable = [
        'company_id', 'department_id', 'role_id',
        'first_name', 'last_name', 'dateIntegration',
        'email', 'salary_amount', 'status'
    ];
    protected $casts = ['dateIntegration' => 'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function role() { return $this->belongsTo(Role::class); }
    public function tasks() { return $this->hasMany(Task::class, 'assigned_to'); }
    public function payments() { return $this->hasMany(SalaryPayment::class); }
}
