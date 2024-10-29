<?php

namespace App\Models\simpananberjangka;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bilyet extends Model
{
    use HasFactory;
    protected $table = 'bilyetdeposito';
    protected $primaryKey = 'Kode';
    protected $guarded = [
        'ID'
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
