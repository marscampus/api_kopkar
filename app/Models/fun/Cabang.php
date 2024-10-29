<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;
    protected $table = 'cabang';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'KodeInduk',
        'Keterangan',
        'Alamat',
        'Kecamatan',
        'Kota',
        'KodePos',
        'Telepon',
        'Fax',
        'KodeLama',
        'SandiBank',
        'PimpinanUtama',
        'JabatanPimpinanUtama',
        'Pimpinan',
        'JabatanPimpinan',
        'SandiSPK',
        'KepalaCabang',
        'RekeningAKA',
        'RekeningAKP',
        'RekeningPendapatanAK',
        'RekeningBiayaAK',
        'Korwil',
        'RekeningKasHeadTeller',
        'RekeningKas'
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
