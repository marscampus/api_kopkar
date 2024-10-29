<?php

namespace App\Models\pinjaman;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuruBayar extends Model
{
    use HasFactory;
    protected $table = 'jurubayar';
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
