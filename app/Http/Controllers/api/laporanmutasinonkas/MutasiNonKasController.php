<?php

namespace App\Http\Controllers\api\laporanmutasinonkas;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiNonKasController extends Controller
{
    public function data(Request $request)
    {
        $tgl = $request->Tgl;
        $tglTransaksi = GetterSetter::getTglTransaksi();
        $data = DB::table('bukubesar as b')
            ->select('b.ID', 'b.Faktur', 'b.Rekening', 'b.Tgl', 'r.Keterangan as NamaPerkiraan', 'b.Keterangan', 'b.Debet', 'b.Kredit', 'b.UserName')
            ->leftJoin('rekening as r', 'r.kode', '=', 'b.rekening')
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
            ->where('b.Tgl', $tgl)
            ->where(function ($query) {
                $query->where('b.Faktur', 'NOT LIKE', 'KM%')
                    ->orWhere('b.Faktur', 'NOT LIKE', 'KK%')
                    ->orWhere('b.Faktur', 'NOT LIKE', 'AA%')
                    ->orWhere('b.Faktur', 'NOT LIKE', 'ZZ%');
            })
            ->orderBy('b.Faktur')
            ->orderBy('b.tgl')
            ->orderByDesc('b.Debet')
            ->orderBy('b.Kredit')
            ->get();
        $row = 0;
        $totalDebet = 0;
        $totalKredit = 0;
        $cekFaktur = "";
        $rowTampil = "";
        $result = [];
        foreach ($data as $d) {
            if ($cekFaktur <> $d->Faktur) {
                $rowTampil = ++$row;
                $cekFaktur = $d->Faktur;
                $faktur = $d->Faktur;
                $tgl = date("d-m-y", strtotime($d->Tgl));
            } else {
                $cekFaktur = $d->Faktur;
                $rowTampil = "";
                $faktur = "";
                $tgl = "";
            }
            $totalDebet += $d->Debet;
            $totalKredit += $d->Kredit;
            $array[] = [
                'No' => $rowTampil,
                'Faktur' => $faktur,
                'Tgl' => $tgl,
                'Rekening' => $d->Rekening,
                'NamaPerkiraan' => $d->NamaPerkiraan,
                'Keterangan' => $d->Keterangan,
                'Debet' => $d->Debet,
                'Kredit' => $d->Kredit,
                'UserName' => $d->UserName
            ];
            $result = [
                'data' => $array,
                'Debet' => $totalDebet,
                'Kredit' => $totalKredit
            ];
        }
        return response()->json($result);
    }
}
