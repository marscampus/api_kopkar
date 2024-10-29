<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerhitunganTKSKoperasi
{
    public static function getNilaiCOA($cRekening, $dTglAwal, $dTglAkhir)
    {
        $nSaldo = 0;
    
        // Split $cRekening into an array if it contains semicolons
        $rekeningArray = explode(';', $cRekening);
    
        foreach ($rekeningArray as $rekening) {
            // Trim whitespace from each rekening
            $rekening = trim($rekening);
    
            // Initialize saldoQuery
            $saldoQuery = null;
    
            // Determine the sum calculation based on the prefix of rekening
            if (substr($rekening, 0, 1) === '1' || substr($rekening, 0, 1) === '3' || substr($rekening, 0, 1) === '5') {
                $saldoQuery = DB::table('bukubesar as b')
                    ->leftJoin('cabang as c', 'c.Kode', '=', 'b.Cabang')
                    ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                    ->where('b.Rekening', 'like', $rekening . '%')
                    ->select(DB::raw('IFNULL(SUM(debet - kredit), 0) as Saldo'));
            } elseif (substr($rekening, 0, 1) === '2' || substr($rekening, 0, 1) === '4') {
                $saldoQuery = DB::table('bukubesar as b')
                    ->leftJoin('cabang as c', 'c.Kode', '=', 'b.Cabang')
                    ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                    ->where('b.Rekening', 'like', $rekening . '%')
                    ->select(DB::raw('IFNULL(SUM(kredit - debet), 0) as Saldo'));
            }
    
            // Check if saldoQuery was initialized and execute the query to get the saldo
            if ($saldoQuery) {
                $dbRow = $saldoQuery->first();
    
                if ($dbRow) {
                    // Accumulate saldo for the current rekening
                    $nSaldo += abs($dbRow->Saldo);
                }
            }
        }
    
        return $nSaldo;
    }
    
}
