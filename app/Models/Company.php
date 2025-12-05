<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model {
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'name'];
}
