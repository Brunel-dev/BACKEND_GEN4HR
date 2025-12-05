<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalaryPayment extends Model {
    protected $fillable = ['employee_id', 'amount', 'period_month', 'status'];
    protected $casts = ['period_month' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
}
