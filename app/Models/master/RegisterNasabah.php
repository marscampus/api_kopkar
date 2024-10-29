<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisterNasabah extends Model
{
    use HasFactory;
    protected $table = 'registernasabah';
    // protected $table = 'registernasabah2';
    protected $primaryKey = 'Kode';
    protected $guarded=['ID'];
    // protected $fillable = [
    //     'Kode',
    //     'KodeLama',
    //     'Nama',
    //     'Anggota',
    //     'Pekerjaan',
    //     'Alamat',
    //     'NoBerkas',
    //     'Tgl',
    //     'RT',
    //     'RW',
    //     'KodePos',
    //     'Agama',
    //     'KodyaKeterangan',
    //     'KecamatanKeterangan',
    //     'KelurahanKeterangan',
    //     'Kelamin',
    //     'GolonganDarah',
    //     'TglLahir',
    //     'TempatLahir',
    //     'Telepon',
    //     'Fax',
    //     'StatusPerkawinan',
    //     'KTP',
    //     'TglKTP',
    //     'NamaPasangan',
    //     'TempatLahirPasangan',
    //     'TglLahirPasangan',
    //     'KTPPasangan',
    //     'KodyaPasangan',
    //     'KecamatanPasangan',
    //     'KelurahanPasangan',
    //     'RTRWPasangan',
    //     'AlamatPasangan',
    //     'NamaKantor',
    //     'AlamatKantor',
    //     'TeleponKantor',
    //     'FaxKantor',
    //     'AlamatTinggal',
    //     'KodePosTinggal',
    //     'TeleponTinggal',
    //     'FaxTinggal',
    //     'KodyaTinggal',
    //     'KecamatanTinggal',
    //     'KelurahanTinggal',
    //     'RTRWTinggal',
    //     'PetaTinggal',
    //     'PosPetaTinggal',
    //     'DesaTinggal',
    // ];
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
