<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerhitunganTabungan
{
    public static function getTotalTabungan($dTgl, $cKode)
    {
        $result = DB::table('mutasitabungan as m')
            ->select(DB::raw('SUM(m.kredit - m.debet) as saldo'))
            ->leftJoin('tabungan as t', 't.rekening', '=', 'm.rekening')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 't.kode')
            ->where(function ($query) use ($cKode) {
                $query->where('r.kode', $cKode)
                    ->orWhere('r.kodeinduk', $cKode);
            })
            ->where('m.tgl', '<=', $dTgl)
            ->first();

        $nSaldo = $result ? $result->saldo : 0;

        return $nSaldo;
    }

    public static function getSaldoTabungan($cRekening, $dTgl, $lSaldoEfektif = false)
    {
        $nSaldo = 0;
        $dTgl = Func::Date2String($dTgl);

        // Query pertama untuk mengambil saldo awal
        $saldoAwal = DB::table('mutasitabungan')
            ->select(DB::raw('IFNULL(SUM(Kredit - Debet), 0) as Saldo'))
            ->where('rekening', '=', $cRekening)
            ->where('tgl', '<=', $dTgl)
            ->first();

        if ($saldoAwal) {
            $nSaldo = $saldoAwal->Saldo;
        }

        if ($lSaldoEfektif) {
            $cGolonganTabungan = GetterSetter::getGolongan($cRekening);
            $nSaldoMinimum = GetterSetter::getKeterangan($cGolonganTabungan, 'saldominimum', 'golongantabungan');
            $nSaldo = max(0, $nSaldo - $nSaldoMinimum);

            // Query kedua untuk mengambil saldo blokir
            $saldoBlokir = DB::table('tabungan')
                ->select('jumlahblokir')
                ->where('rekening', '=', $cRekening)
                ->first();

            if ($saldoBlokir) {
                $nSaldoBlokir = $saldoBlokir->jumlahblokir;
                $nSaldo = max(0, $nSaldo - $nSaldoBlokir);
            }
        }

        return $nSaldo;
    }
}
