<?php

namespace App\Models\teller;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiAnggota extends Model
{
    use HasFactory;
    protected $table = 'mutasianggota';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'ID',
        'CabangEntry',
        'Faktur',
        'Tgl',
        'Kode',
        'KodeTransaksi',
        'GolonganAnggota',
        'DK',
        'Kas',
        'Keterangan',
        'Jumlah',
        'Debet',
        'Kredit',
        'DebetPokok',
        'KreditPokok',
        'DebetWajib',
        'KreditWajib',
        'UserName',
        'RekeningTabungan',
        'RekeningPB',
        'DateTime'
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
