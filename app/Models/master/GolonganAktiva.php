<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganAktiva extends Model
{
    use HasFactory;
    protected $table = 'golonganaktiva';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Keterangan',
        'RekeningDebet',
        'RekeningKredit',
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
