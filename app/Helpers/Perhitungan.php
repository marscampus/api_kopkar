<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Perhitungan
{
    public static function getSaldoMutasi($dTglAwal, $dTglAkhir, $cRekening, $cRekening2 = '', $lRekonsiliasi = false, $lPenihilan = false, $cCabang = '', $cJenisGabungan = 'A', $cKas = '')
    {
        $dTglAwal = Func::Date2String($dTglAwal);
        $dTglAkhir = Func::Date2String($dTglAkhir);

        $cSum = (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3") ?
            DB::raw('(b.Kredit - b.Debet) as Saldo') :
            DB::raw('(b.Debet - b.Kredit) as Saldo');

        if ($cRekening2 !== '') {
            $cLike = "b.rekening >= '{$cRekening}' and b.rekening <= '{$cRekening2}'";
        } else {
            $cLike = "b.rekening like '{$cRekening}%'";
        }

        $cWhere = "";
        if ($lPenihilan) {
            $cWhere = " and b.Faktur NOT LIKE 'TH%'";
            $cWhere = " and b.keterangan NOT LIKE 'Jurnal awal tahun %'";
        } else {
            $cWhere = "";
        }

        if (empty($cCabang)) {
            $cCabang = GetterSetter::getDBConfig('msKodeCabang');
        }

        $cWhereGabungan = GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);

        $cWhereRAK = "";
        if ($cJenisGabungan == 'C') {
            $cWhereRAK = " and b.rekening not like '1.170%'";
        }

        $cWhereKas = '';
        if (!empty($cKas)) {
            $cWhereKas = " and b.kas = '{$cKas}'";
        }

        $vaData = DB::table('bukubesar as b')
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
            ->selectRaw($cSum)
            ->where('b.tgl', '>=', $dTglAwal)
            ->where('b.tgl', '<=', $dTglAkhir)
            ->whereRaw($cLike)
            ->whereRaw($cWhereGabungan)
            ->whereRaw($cWhere, [], '')
            ->whereRaw($cWhereRAK, [], '')
            ->whereRaw($cWhereKas, [], '')
            ->first();

        $nSaldo = 0;
        if ($vaData) {
            $nSaldo = $vaData->Saldo;
        }

        if ($lRekonsiliasi) {
            $vaData = DB::table('jurnal as b')
                ->selectRaw($cSum)
                ->where('b.tgl', '>=', $dTglAwal)
                ->where('b.tgl', '<=', $dTglAkhir)
                ->where('b.rekonsiliasi', '=', 'Y')
                ->whereRaw($cLike)
                ->whereRaw("SUBSTRING(b.faktur, 3, 2) LIKE '{$cCabang}%'")
                ->whereRaw($cWhere)
                ->first();

            if ($vaData) {
                $nSaldo += $vaData->Saldo;
            }
        }

        return $nSaldo;
    }
}
