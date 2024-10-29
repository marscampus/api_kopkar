<?php

namespace App\Models\Pinjaman;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalAngsuran extends Model
{
    use HasFactory;
    protected $table = 'jadwalangsuran';
    protected $primaryKey = 'Rekening';
    protected $fillable = [
        'ke',
        'Rekening',
        'Tgl',
        'Pokok',
        'Bunga',
        'BakiDebet',
        'Username',
        'DateTime'
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
