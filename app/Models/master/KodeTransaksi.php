<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeTransaksi extends Model
{
    use HasFactory;
    protected $table = 'kodetransaksi';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Keterangan',
        'DK',
        'Kas',
        'Rekening'
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
