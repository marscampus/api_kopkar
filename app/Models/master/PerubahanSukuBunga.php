<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerubahanSukuBunga extends Model
{
    use HasFactory;
    protected $table = 'detailsukubunga';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Tgl',
        'Maximum',
        'SukuBunga'
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
