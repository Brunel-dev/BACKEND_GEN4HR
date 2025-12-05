<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['email', 'password', 'role', 'employee_id'];
    protected $hidden = ['password'];
    protected $casts = ['employee_id' => 'integer'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Méthode utilitaire
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
