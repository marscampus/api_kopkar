<?php

namespace App\Http\Controllers\api\laporansimpanan;

use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlokirSimpananController extends Controller
{
    public function data(Request $request)
    {
        $tgl = $request->Tgl;
        $no = 0;
        $data =
            DB::table('tabungan as t')
            ->select(
                'r.Nama',
                'r.Alamat',
                'r.Kode',
                't.Rekening',
                't.Tgl',
                'g.Keterangan as NamaGolongan',
                't.GolonganTabungan',
                't.StatusBlokir',
                't.JumlahBlokir',
                't.TglBlokir',
            )
            ->leftJoin('registernasabah as r', 'r.Kode', '!=', 't.Kode')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('cabang as c', 'c.kode', '=', 't.cabangentry')
            ->where('t.statusblokir', '=', 1)
            ->where('t.TglBlokir', '<=', $tgl)
            ->orderByDesc('t.TglBlokir')
            ->get();
        // ->paginate(10);

        $responseArray = [];

        foreach ($data as $d) {
            $no++;
            $rekening = $d->Rekening;
            $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($rekening, $tgl);
            $array = [
                'No' => $no,
                'CIF' => $d->Kode,
                'Rekening' => $rekening,
                'Nama' => $d->Nama,
                'Alamat' => $d->Alamat,
                'GolTabungan' => $d->NamaGolongan ?? '', // Gunakan null coalescing operator
                // 'TglBuka' => $d->TglBuka,
                'TglBlokir' => $d->TglBlokir,
                'JumlahBlokir' => $d->JumlahBlokir,
                'SaldoTabungan' => $saldoTabungan
            ];
            $responseArray[] = $array;
        }
        return response()->json($responseArray);
        $totalData = count($responseArray);
        if ($totalData > 0) {
            // Jika ada data, tampilkan respons JSON dengan data
            return response()->json(['data' => $responseArray, 'jmlData' => $totalData]);
        } else {
            // Jika tidak ada data, tampilkan pesan error
            return response()->json(['status' => 'error', 'message' => 'Data Kosong'], 404);
        }
    }
}
