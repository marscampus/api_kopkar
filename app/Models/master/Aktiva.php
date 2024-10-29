<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aktiva extends Model
{
    use HasFactory;
    // protected $table = 'aktiva2';
    protected $table = 'aktiva';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Nama',
        'TglPerolehan',
        'TglPenyusutan',
        'TarifPenyusutan',
        'HargaPerolehan',
        'Unit',
        'Golongan',
        'JenisPenyusutan',
        'Lama',
        'Residu',

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
