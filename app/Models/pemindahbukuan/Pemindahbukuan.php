<?php

namespace App\Models\pemindahbukuan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemindahbukuan extends Model
{
    use HasFactory;
    protected $table = 'pemindahbukuan';
    protected $primaryKey = 'ID';
    // protected $fillable = [
    //     'Faktur', 'Tgl', 'DK', 'RekeningJurnal', 'RekeningNasabah', 'JumlahD', 'Keterangan', 'JumlahK', 'Pokok', 'Bunga', 'Denda', 'UserName', 'CabangEntry'
    // ];
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
