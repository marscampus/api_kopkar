<?php

namespace App\Http\Controllers\api\laporansimpanan;

use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutupSimpananController extends Controller
{
    public function data(Request $request)
    {
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $no = 0;
        $data = DB::table('tabungan as t')
            ->select(
                't.Rekening',
                'r.Nama',
                't.GolonganTabungan',
                'g.Keterangan as NamaGolonganTabungan',
                't.Kode',
                't.Tgl',
                't.TglPenutupan'
            )
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->whereColumn('t.TglPenutupan', '>=', 't.Tgl')
            ->where('t.TglPenutupan', '>=', $tglAwal)
            ->where('t.TglPenutupan', '<=', $tglAkhir)
            ->where('t.Close', '>=', 1)
            ->orderBy('t.TglPenutupan')
            ->paginate(10);
        $responseArray = []; // Deklarasi $responseArray sebagai array kosong

        foreach ($data as $d) {
            $no++;
            $array = [
                'No' => $no,
                'Rekening' => $d->Rekening,
                'Nama' => $d->Nama,
                'TglPenutupan' => $d->TglPenutupan,
                'JenisTabungan' => $d->NamaGolonganTabungan ? $d->NamaGolonganTabungan : ''
            ];
            $responseArray[] = $array;
        }
        return response()->json($responseArray);
        $totalData = count($responseArray);
        if ($totalData) {
            // Jika ada data, tampilkan respons JSON dengan data
            return response()->json(['data' => $responseArray, 'jmlData' => $totalData]);
        } else {
            // Jika tidak ada data, tampilkan pesan error
            return response()->json(['status' => 'error', 'message' => 'Data Kosong'], 404);
        }
    }
}
