<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockKode extends Model
{
    use HasFactory;
    protected $table = 'stock_kode';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'BARCODE',
        'KETERANGAN',
        'STATUS'
    ];
    public $keyType = 'string';
    public $timestamps = false;
}
