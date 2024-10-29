<?php

namespace App\Http\Controllers\api\posting;

use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\jurnal\Jurnal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Posting2Controller extends Controller
{
    public function postingJurnal(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $dTgl = $request->Tgl;
            BukuBesar::whereDate('Tgl', $dTgl)->delete();
            $jurnals = Jurnal::whereDate('Tgl', $dTgl)->get();

            if ($jurnals->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'Tidak ada jurnal pada tanggal yang diminta(?)']);
            }

            $dataBukuBesar = [];
            // Gunakan chunk untuk mengambil data dalam potongan-potongan
            Jurnal::whereDate('Tgl', $dTgl)->chunk(200, function ($jurnals) use ($cUser) {
                foreach ($jurnals as $jurnal) {
                    Upd::updBukuBesar('1', $jurnal->Faktur, $jurnal->CabangEntry, $jurnal->Tgl, $jurnal->Rekening, $jurnal->Keterangan, $jurnal->Debet, $jurnal->Kredit, $cUser, $jurnal->Kas);
                }
            });
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
            throw $th;
        }
    }
}
