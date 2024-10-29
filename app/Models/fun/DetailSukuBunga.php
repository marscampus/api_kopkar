<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSukuBunga extends Model
{
    use HasFactory;
    protected $table = 'detailsukubunga';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'ID',
        'Kode',
        'Tgl',
        'Maximum',
        'SukuBunga'
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
