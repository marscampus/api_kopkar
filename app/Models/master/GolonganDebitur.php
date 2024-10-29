<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganDebitur extends Model
{
    use HasFactory;
    protected $table='golongandebitur';
    protected $primaryKey='Kode';
    protected $guarded=['Kode'];
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
