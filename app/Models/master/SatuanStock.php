<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatuanStock extends Model
{
    use HasFactory;
    protected $table = 'satuanstock';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN'
    ];
    public $timestamps = false;
    protected $keyType = 'string';

    public function setUpdatedAt($value)
    {
        return NULL;
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'KODE', 'SATUAN');
        }
        public function stock2(): HasMany
        {
            return $this->hasMany(Stock::class, 'KODE', 'SATUAN2');
        }

    public function setCreatedAt($value)
    {
        return NULL;
    }
}
