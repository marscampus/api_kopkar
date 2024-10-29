<?php

namespace App\Models\fun;

use App\Models\master\Ao;
use App\Models\master\GolonganPinjaman;
use App\Models\master\RegisterNasabah;
use App\Models\pinjaman\Debitur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class  Angsuran extends Model
{
    use HasFactory;
    // protected $table = 'angsuran2';
    protected $table = 'angsuran';
    protected $primaryKey = 'ID';
    // protected $fillable = [
    //     'ID',
    //     'CabangEntry',
    //     'Status',
    //     'Faktur',
    //     'TGL',
    //     'Rekening',
    //     'Keterangan',
    //     'DPokok',
    //     'KPokok',
    //     'DBunga',
    //     'KBunga',
    //     'DBungaRK',
    //     'KBungaRK',
    //     'PotonganBunga',
    //     'Denda',
    //     'Tabungan',
    //     'DTitipan',
    //     'KTitipan',
    //     'Administrasi',
    //     'Kas',
    //     'RekeningPB',
    //     'StatusPrinter',
    //     'DateTime',
    //     'UserName',
    //     'BungaPinalty',
    //     'SimpananWajib',
    //     'RRA',
    //     'BungaTunggakan',
    //     'StatusPrinterRealisasi',
    //     'StatusPrinterSlip',
    //     'StatusPrinterKwitansi',
    //     'StatusAngsuran',
    //     'Rekonsiliasi',
    //     'DRRA',
    //     'KRRA',
    //     'PPAP',
    //     'CaraTransaksi',
    //     'IPTW'
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

    public function debitur(): BelongsTo
    {
        return $this->belongsTo(Debitur::class, 'Rekening', 'Rekening');
    }
}
