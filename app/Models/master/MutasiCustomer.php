<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiCustomer extends Model
{
    use HasFactory;
    protected $table = 'mutasicustomer';
    protected $primaryKey = 'ID';
    protected $guarded = [
        "ID"
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
