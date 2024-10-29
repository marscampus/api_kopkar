<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';
    protected $guard = ['id'];
    protected $fillable = ['nama_akses','access'];
    protected $hidden = ['created_at','updated_at'];
}