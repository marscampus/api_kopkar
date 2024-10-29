<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ao extends Model
{
    use HasFactory;
    protected $table = 'ao';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Nama',
        'Alamat',
        'Telepon',
        'Kota',
        'Provinsi'
    ];
    public $timestamps = false;
    protected $keyType = 'string';

    public function setUpdatedAt($value)
    {
        return NULL;
    }


    public function setCreatedAt($value)
    {
        return NULL;
    }
}
