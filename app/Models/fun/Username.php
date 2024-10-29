<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Username extends Model
{
    use HasFactory;
    protected $table = 'username';
    protected $primaryKey = 'ID';
    // protected $fillable = [
    //     'ID',
    //     'UserName',
    //     'UserPassword',
    //     'FullName',
    //     'Login',
    //     'KasTeller',
    //     'KODE',
    //     'Online',
    //     'Plafond',
    //     'TimeOut',
    //     'Kas',
    //     'Tabungan',
    //     'Deposito',
    //     'Kredit',
    //     'Akuntansi',
    //     'Block',
    //     'Aktif',
    //     'Cabang',
    //     'CabangInduk',
    //     'Tgl',
    //     'PortPrinter',
    //     'PlafondSetoran',
    //     'Gabungan',
    //     'UserNameAcc',
    //     'Unit',
    //     'StatusOtorisasi',
    //     'IP'
    // ];
    protected $guarded = [
        "ID"
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
