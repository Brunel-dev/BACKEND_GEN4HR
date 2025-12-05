<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Role extends Model {
    protected $fillable = ['company_id', 'name'];
    public function company() { return $this->belongsTo(Company::class); }
    public function employees() { return $this->hasMany(Employee::class); }
}
