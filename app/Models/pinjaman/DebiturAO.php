<?php

namespace App\Models\pinjaman;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebiturAO extends Model
{
    use HasFactory;
    protected $table = 'debitur_ao';
    protected $primaryKey = 'ID';
    protected $guarded = [
        'ID'
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