<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiTabungan extends Model
{
    use HasFactory;
    protected $table = 'mutasitabungan';
    protected $primarykey = 'ID';
    protected $fillable = [
        'ID',
        'CabangEntry',
        'Faktur',
        'Tgl',
        'Rekening',
        'KodeTransaksi',
        'DK',
        'RekeningJurnal',
        'Keterangan',
        'Jumlah',
        'Debet',
        'Kredit',
        'UserName',
        'DateTime',
        'StatusPrinter',
        'StatusPrinterBank',
        'UserAcc',
        'Denda',
        'StatusPrinterSlip'
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
