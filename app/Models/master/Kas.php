<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// "userid"        => $userid,
// "faktur"        => $faktur,
// "tgl"           => date("Y-m-d"),
// "notransaksi"   => $notransaksi,
// "coa"           => $rekening,
// "keterangan"    => $keterangan,
// "debet"         => $debet,
// "kredit"        => $kredit,
// "imageid"        => $imageid,
// "datetime"      => date("Y-m-d H:i:s") 

// <Column field="id" header="FAKTUR"></Column>
// <Column field="discountPercentage" header="TANGGAL"></Column>
// <Column field="discountPercentage" header="REKENING"></Column>
// <Column field="price" header="DEBET"></Column>
// <Column field="price" header="KREDIT"></Column>
// <Column field="title" header="KETERANGAN"></Column>
// <Column

class Kas extends Model
{
    use HasFactory;
    protected $table = 'jurnal';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'Faktur',
        'Tgl',
        'Rekening',
        'Debet',
        'Kredit',
        'Keterangan'
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
