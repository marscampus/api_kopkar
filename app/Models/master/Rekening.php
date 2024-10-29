<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    use HasFactory;
    protected $table = 'rekening';
    // protected $table = 'rekening2';
    protected $primaryKey =  'Kode';
    protected $fillable = [
        'Kode', 'Keterangan',
        'Jenis', 'Cabang'
    ];
    protected $keyType = 'string';
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
