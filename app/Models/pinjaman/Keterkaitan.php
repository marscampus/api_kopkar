<?php

namespace App\Models\pinjaman;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keterkaitan extends Model
{
    use HasFactory;
    protected $table = 'keterkaitan';
    protected $primaryKey = 'Kode';
    protected $guarded = [
        'Kode'
    ];
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
