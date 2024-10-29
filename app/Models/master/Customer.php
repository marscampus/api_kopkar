<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customer';
    protected $primaryKey = 'KODE';
    protected $fillable  = [
        'KODE',
        'NAMA',
        'ALAMAT',
        'TELEPON',
        'KOTA',
        'JENIS_USAHA',
        'REKENING',
        'NAMA_CP_1',
        'ALAMAT_CP_1',
        'TELEPON_CP_1',
        'HP_CP_1',
        'EMAIL_CP_1',
        'NAMA_CP_2',
        'ALAMAT_CP_2',
        'TELEPON_CP_2',
        'HP_CP_2',
        'EMAIL_CP_2',
        'PLAFOND_1',
        'PLAFOND_2'
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
    public function mutasiCustomer(): BelongsTo
    {
        return $this->belongsTo(MutasiCustomer::class, 'KODE', 'CUSTOMER');
    }
}
