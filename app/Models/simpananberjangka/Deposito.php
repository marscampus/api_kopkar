<?php

namespace App\Models\simpananberjangka;

use App\Models\fun\Cabang;
use App\Models\fun\MutasiDeposito;
use App\Models\master\GolonganDeposan;
use App\Models\master\GolonganSimpananBerjangka;
use App\Models\master\RegisterNasabah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposito extends Model
{
    use HasFactory;
    // protected $table = 'deposito2';
    protected $table = 'deposito';
    protected $primaryKey = 'Rekening';
    // protected $fillable = [
    //     'CaraPerhitungan',
    //     'Rekening',
    //     'Tgl',
    //     'Jthtmp',
    //     'NoBilyet',
    //     'Kode',
    //     'AO',
    //     'CairBunga',
    //     'RekeningTabungan',
    //     'Aro',
    //     'CaraPerpanjangan',
    //     'SukuBunga',
    //     'GolonganDeposito',
    //     'DateTime',
    //     'RekeningLama',
    //     'NamaNasabah',
    //     'AhliWaris',
    //     'CabangEntry',
    //     'BungaDibayar',
    //     'TempNominal',
    //     'StatusPajak'
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

    public function registernasabah(): BelongsTo
    {
        return $this->belongsTo(RegisterNasabah::class, 'Kode', 'Kode');
    }

    public function goldeposito(): BelongsTo
    {
        return $this->belongsTo(GolonganSimpananBerjangka::class, 'GolonganDeposito', 'KODE');
    }

    public function goldeposan(): BelongsTo
    {
        return $this->belongsTo(GolonganDeposan::class, 'GolonganDeposan', 'Kode');
    }

    public function tabungan(): BelongsTo
    {
        return $this->belongsTo(Tabungan::class, 'RekeningTabungan', 'Rekening');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'CabangEntry', 'Kode');
    }

    public function mutasideposito(): BelongsTo
    {
        return $this->belongsTo(MutasiDeposito::class, 'Rekening', 'Rekening');
    }

    public function depositosukubunga(): BelongsTo
    {
        return $this->belongsTo(DepositoSukuBunga::class, 'Rekening', 'Rekening');
    }
}
