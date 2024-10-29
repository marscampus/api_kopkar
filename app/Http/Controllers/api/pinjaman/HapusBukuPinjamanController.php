<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Debitur;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HapusBukuPinjamanController extends Controller
{
    public function getRekening(Request $request)
    {
        $rekening = $request->Rekening;
        $tgl = GetterSetter::getTglTransaksi();
        $data = Debitur::with('registernasabah')
            ->where('Rekening', '=', $rekening)
            ->first();
        if ($data) {
            $tglRealisasi = $data->Tgl;
            $lama = $data->Lama;
            // $jthTmp = Carbon::parse($tglRealisasi)->addMonths($lama)->format('Y-m-d');
            $faktur = GetterSetter::getLastFaktur('W0', 7);
            $bakiDebet = GetterSetter::getBakiDebet($rekening, $tgl);
            // Cek status pencairan
            if ($data->StatusPencairan == '0') {
                return response()->json(
                    ['status' => 'error', 'message' => 'Pinjaman Belum Dicairkan!']
                );
            }
            if ($data->TglWriteOff != '9999-99-99') {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Pinjaman Sudah Dihapus!']
                );
            }
            $array = [
                'Faktur' => $faktur,
                'Plafond' => $data->Plafond,
                'TglRealisasi' => $tglRealisasi,
                'BakiDebet' => $bakiDebet,
                'Nama' => $data->registernasabah->Nama,
                'Alamat' => $data->registernasabah->Alamat
            ];
            return response()->json($array);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Terdaftar!']
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            // // Cek rekening
            // $data = Debitur::where('Rekening', $rekening)
            //     ->where('TglWriteOff', '!=', '9999-99-99')
            //     ->count();
            // if ($data > 0) {
            //     return response()->json(
            //         ['status' => 'error', 'message' => 'Rekening Pinjaman Sudah Dihapus!']
            //     );
            // }
            // $data2 = Debitur::where('Rekening', $rekening)
            //     ->count();
            // if ($data2 == 0) {
            //     return response()->json(
            //         ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Terdaftar!']
            //     );
            // }

            Upd::updWriteOff($request->Tgl, $request->Faktur, $request->Rekening, $request->Plafond, $request->BakiDebet);
            GetterSetter::setLastFaktur('WO');
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
