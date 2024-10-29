<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvisiDanAdministrasi extends Model
{
    use HasFactory;
    protected $table = 'cfgadministrasi';
    protected $primaryKey = 'GolonganKredit';
    protected $fillable = [
        'GolonganKredit',
        'Lama',
        'Provisi',
        'Administrasi'
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
