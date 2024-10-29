<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisCustomer extends Model
{
    use HasFactory;
    protected $table = 'jeniscustomer';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN'
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
