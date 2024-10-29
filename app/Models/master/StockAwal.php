<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAwal extends Model
{
    use HasFactory;
    protected $table = 'kartustock';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'STATUS',
        'FAKTUR',
        'TGL',
        'GUDANG',
        'KODE',
        'QTY',
        'DEBET',
        'KREDIT',
        'HARGA',
        'HP',
        'KETERANGAN',
        'DATETIME',
        'USERNAME',
        'URUT',
        'PPN'
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
