<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;
    protected $table = 'stock';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KODE_TOKO',
        'NAMA',
        'JENIS',
        'GOLONGAN',
        'RAK',
        'GUDANG',
        'SUPPLIER',
        'EXPIRED',
        'TGL_MASUK',
        'FOTO',
        'BERAT',
        'DOS',
        'SATUAN',
        'SATUAN2',
        'SATUAN3',
        'ISI',
        'ISI2',
        'DISCOUNT',
        'PAJAK',
        'MIN',
        'MAX',
        'HB',
        'HB2',
        'HB3',
        'HJ',
        'HJ2',
        'HJ3',
        'HJ_TINGKAT1',
        'HJ_TINGKAT2',
        'HJ_TINGKAT3',
        'HJ_TINGKAT4',
        'HJ_TINGKAT5',
        'HJ_TINGKAT6',
        'HJ_TINGKAT7',
        'MIN_TINGKAT1',
        'MIN_TINGKAT2',
        'MIN_TINGKAT3',
        'MIN_TINGKAT4',
        'MIN_TINGKAT5',
        'MIN_TINGKAT6',
        'MIN_TINGKAT7'
    ];
    public $keyType = 'string';
    // protected $guarded = [
    //     'KODE',
    //     'KODE_TOKO'
    // ];
    public $timestamps = false;

    public function setUpdatedAt($value)
    {
        return NULL;
    }


    public function setCreatedAt($value)
    {
        return NULL;
    }

    public function golongan(): BelongsTo
    {
        return $this->belongsTo(GolonganStock::class, 'GOLONGAN', 'KODE');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(SatuanStock::class, 'SATUAN', 'KODE');
    }

    public function satuan2(): BelongsTo
    {
        return $this->belongsTo(SatuanStock::class, 'SATUAN2', 'KODE');
    }

    public function satuan3(): BelongsTo
    {
        return $this->belongsTo(SatuanStock::class, 'SATUAN3', 'KODE');
    }
}
