<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NomorFaktur extends Model
{
    use HasFactory;
    protected $table = 'nomorfaktur';
    protected $primaryKey = 'KODE';
    public $timestamps = false;
    public $keyType = 'string';
    public $fillable = [
        'KODE',
        'ID'
    ];

    public static function getLastKodeRegister($key, $len)
    {
        $valueReturn = '';
        $ID = 0;
        try {
            $query = self::where('KODE', str_replace(' ', '', $key))->first();
            if ($query) {
                $ID = $query->ID;
                $ID++;
            } else {
                $value = str_replace(' ', '', $key);
                self::insert(['KODE' => $value]);
                $query = self::where('KODE', $value)->first();
                if ($query) {
                    $ID = $query->ID;
                    $ID++;
                }
            }
            $valueReturn = (string)$ID;
            $valueReturn = str_pad($valueReturn, $len, '0', STR_PAD_LEFT);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error']);
        }
        return $valueReturn;
    }
}
