<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoltTeller extends Model
{
    use HasFactory;
    protected $table = 'jurnal';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'Faktur',
        'Tgl',
        'Rekening',
        'Debet',
        'Kredit',
        'Keterangan'
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
