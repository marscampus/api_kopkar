<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganSimpananBerjangka extends Model
{
    use HasFactory;
    protected $table = 'golongandeposito';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Keterangan',
        'RekeningAkuntansi',
        'RekeningBunga',
        'RekeningPajakBunga',
        'RekeningJatuhTempo',
        'CadanganBunga',
        'RekeningPinalti',
        'Lama',
        'Bunga',
        'WajibPajak'
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
