<?php

namespace App\Models\simpananberjangka;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositoSukuBunga extends Model
{
    use HasFactory;
    protected $table = 'deposito_sukubunga';
    protected $primaryKey = 'ID';
    // protected $fillable = [
    //     'Tgl',
    //     'Rekening',
    //     'SukuBunga',
    //     ''
    // ];
    protected $guarded = [
        'Rekening'
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
