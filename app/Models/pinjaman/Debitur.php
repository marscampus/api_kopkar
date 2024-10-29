<?php

namespace App\Models\pinjaman;

use App\Models\master\Ao;
use App\Models\master\GolonganDebitur;
use App\Models\master\RegisterNasabah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debitur extends Model
{
    use HasFactory;
    // protected $table = 'debitur2';
    protected $table = 'debitur';
    protected $primaryKey = 'Rekening';
    protected $guarded = [
        'ID'
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

    public function registernasabah(): BelongsTo
    {
        return $this->belongsTo(RegisterNasabah::class, 'Kode', 'Kode');
    }

    public function agunan(): BelongsTo
    {
        return $this->belongsTo(Agunan::class, 'RekeningJaminan', 'Rekening');
    }

    public function goldebitur(): BelongsTo
    {
        return $this->belongsTo(GolonganDebitur::class, 'RekeningJaminan', 'Kode');
    }

    public function sifatkredit(): BelongsTo
    {
        return $this->belongsTo(SifatKredit::class, 'SifatKredit', 'Kode');
    }

    public function jenispenggunaan(): BelongsTo
    {
        return $this->belongsTo(JenisPenggunaan::class, 'JenisPenggunaan', 'Kode');
    }

    public function sektorekonomi(): BelongsTo
    {
        return $this->belongsTo(SektorEkonomi::class, 'SektorEkonomi', 'Kode');
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'Wilayah', 'Kode');
    }

    public function ao(): BelongsTo
    {
        return $this->belongsTo(Ao::class, 'AO', 'Kode');
    }

    public function golpenjamin(): BelongsTo
    {
        return $this->belongsTo(GolonganPenjamin::class, 'GolonganPenjamin', 'Kode');
    }

    public function golkredit(): BelongsTo
    {
        return $this->belongsTo(GolonganPinjaman::class, 'GolonganKredit', 'Kode');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'CabangEntry', 'Kode');
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'instansi', 'Kode');
    }
}
