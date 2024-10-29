<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganNasabah extends Model
{
    use HasFactory;
    protected $table = 'golongannasabah';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE'
    ];
    public $timestamps = false;

    public function setUpdatedAt($value)
    {
        return NULL;
    }

    public function setCreatedAt($value)
    {
        return NULL;
    }
}
