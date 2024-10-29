<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
  use HasFactory;
  protected $table = 'bank';
  protected $primaryKey = 'Kode';
  protected $fillable = ['Kode', "Keterangan", "Rekening", "Rekening_Kredit", "Awal", "Administrasi", "PenarikanTunai"];
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
}
