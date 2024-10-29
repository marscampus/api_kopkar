<?php

namespace App\Models\simpanan;

use App\Models\fun\Cabang;
use App\Models\master\Ao;
use App\Models\master\GolonganNasabah;
use App\Models\master\GolonganSimpanan;
use App\Models\master\RegisterNasabah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tabungan extends Model
{
    use HasFactory;
    // protected $table = 'tabungan';
    protected $table = 'tabungan';
    protected $primaryKey = 'Rekening';
    protected $guarded = ['ID'];
    // protected $fillable = [
    //     'Rekening',
    //     'RekeningLama',
    //     'Tgl',
    //     'Kode',
    //     'NamaNasabah',
    //     'GolonganTabungan',
    //     'AO',
    //     'AhliWaris',
    //     'CabangEntry',
    //     'NoBuku',
    //     'StatusBunga',
    //     'StatusAdministrasi',
    //     'SaldoAkhir'
    // ];
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

    public function ao(): BelongsTo
    {
        return $this->belongsTo(Ao::class, 'AO', 'Kode');
    }

    public function golsimpanan(): BelongsTo
    {
        return $this->belongsTo(GolonganSimpanan::class, 'GolonganTabungan', 'Kode');
    }

    public function registernasabah(): BelongsTo
    {
        return $this->belongsTo(RegisterNasabah::class, 'Kode', 'Kode');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'CabangEntry', 'Kode');
    }

    public function golNasabah(): BelongsTo
    {
        return $this->belongsTo(GolonganNasabah::class, 'GolonganNasabah', 'Kode');
    }
}
