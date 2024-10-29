<?php

namespace App\Models\pinjaman;

use App\Models\master\Jaminan;
use App\Models\master\RegisterNasabah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgunanDetailTmp extends Model
{
    use HasFactory;
    protected $table = 'agunan_detail_tmp';
    protected $primaryKey = 'ID';
    protected $guarded  = [
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

    public function jaminan(): BelongsTo
    {
        return $this->belongsTo(Jaminan::class, 'Jaminan', 'Kode');
    }

    // public function registerNasabah(): BelongsTo
    // {
    //     return $this->belongsTo(RegisterNasabah::class, 'Kode', 'Kode');
    // }
}
