<?php

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakHeaderSimpananController extends Controller
{
    function getDataEdit(Request $request)
    {
        try {
            $Rekening = $request->Rekening;
            $result = DB::table('tabungan as t')
                ->select('t.Tgl', 'r.Nama', 'r.RTRW', 'r.Kodya', 'r.Kecamatan', 'r.Kelurahan', 'r.Alamat', 'k.Keterangan as NamaKodya', 'r.Telepon', 'r.KTP', 't.GolonganTabungan', 'g.Keterangan as NamaGolonganTabungan', 'c.Keterangan as NamaCabang')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 't.kode')
                ->leftJoin('kodya as k', 'r.kodya', '=', 'k.kode')
                ->leftJoin('golongantabungan as g', 'g.kode', '=', 't.golongantabungan')
                ->leftJoin('cabang as c', DB::raw('left(r.kode, 2)'), '=', 'c.kode')
                ->where('t.rekening', $Rekening)
                ->first();

            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
