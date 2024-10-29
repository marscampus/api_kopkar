<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SukuBunga extends Model
{
    use HasFactory;
    protected $table = 'sukubunga';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
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
