<?php

namespace App\Models\Pinjaman;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanKredit extends Model
{
    use HasFactory;
    protected $table = 'pengajuankredit';
    protected $primaryKey = '';
    protected $guarded = [
        'StatusPengajuan'
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
