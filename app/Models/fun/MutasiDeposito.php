<?php

namespace App\Models\fun;

use App\Models\master\GolonganSimpananBerjangka;
use App\Models\simpananberjangka\Deposito;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiDeposito extends Model
{
    use HasFactory;
    protected $table = 'mutasideposito';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'Jenis',
        'ID',
        'Faktur',
        'Rekening',
        'CabangEntry',
        'Tgl',
        'Jthtmp',
        'SetoranPlafond',
        'PencairanPlafond',
        'Bunga',
        'Pajak',
        'KoreksiBunga',
        'KoreksiPajak',
        'Pinalty',
        'DTitipan',
        'KTitipan',
        'Kas',
        'UserName',
        'DateTime',
        'StatusPrinter',
        'StatusPrinterSlip',
        'Fee',
        'TglJurnal',
        'RekeningTab',
        'Accrual',
        'RekeningAkuntansi'
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

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'Rekening', 'Rekening');
    }

    public function goldeposito(): BelongsTo
    {
        return $this->belongsTo(GolonganSimpananBerjangka::class, 'GolonganDeposito', 'Kode');
    }
}
