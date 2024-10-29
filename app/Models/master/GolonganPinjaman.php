<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganPinjaman extends Model
{
    use HasFactory;
    protected $table = 'golongankredit';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Keterangan',
        'Rekening',
        'RekeningBunga',
        'RekeningDenda'
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
