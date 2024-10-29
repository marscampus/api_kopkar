<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganSimpanan extends Model
{
    use HasFactory;
    protected $table = 'golongantabungan';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Keterangan',
        'Rekening',
        'RekeningBunga',
        'SukuBunga',
        'SaldoMinimum',
        'SetoranMinimum',
        'SaldoMinimumDapatBunga',
        'AdministrasiTutup',
        'AdministrasiBulanan',
        'AdminPasif',
        'AdministrasiTahunan',
        'PenjualanBukuTabungan',
        'RekeningCadanganBunga',
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
