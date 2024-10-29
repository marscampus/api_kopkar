<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsernameKantorKas extends Model
{
    use HasFactory;
    protected $table = 'username_kantorkas';
    protected $primaryKey = 'ID';
    // protected $fillable = [
    //     'ID',
    //     'UserName',
    //     'Tgl',
    //     'Cabang',
    //     'KasTeller',
    //     'DateTime',
    //     'Aktif',
    //     'Gabungan',
    //     'Unit'
    // ];
    protected $guarded = ['ID'];
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
