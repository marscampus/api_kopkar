<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    protected $table = 'request';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'datetime',
        'transaksi',
        'Nominal',
        'UserRequest',
        'date',
        'Level',
        'Rekening',
        'Acc',
        'UserAcc',
        'Faktur',
        'UserNameAcc',
        'Jenis',
        'UserNameAcc2',
        'UserNameAcc3'
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
