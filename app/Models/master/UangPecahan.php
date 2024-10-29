<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UangPecahan extends Model
{
    use HasFactory;
    protected $table = 'uangpecahan';
    protected $primaryKey = 'Kode';
    protected $fillable = [
        'Kode',
        'Nominal',
        'Status'
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
