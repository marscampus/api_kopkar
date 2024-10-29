<?php

namespace App\Http\Controllers\api\tks\strukturkeuanganyangefektif;

use App\Helpers\Func;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModalLembagaController extends Controller
{
    function data(Request $request)
    {
        try {
            $nSaldoA = 0;
            $nSaldoB = 0;
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            // Ambil periode dan ubah menjadi tanggal format Y-m-d
            $dYear = $vaRequestData['Periode'];
            $dTgl = Carbon::createFromDate($dYear, 12, 31);
            $dTglFormatter = $dTgl->format('Y-m-d');
            $vaData = DB::table('tkspearls')
                ->select('Sandi', 'Saldo')
                ->where('Periode', '=', $dTglFormatter)
                ->where('Sandi', 'LIKE', '8.200%')
                ->get();
            foreach ($vaData as $data) {
                $vaSandi = explode(" ", $data->Sandi);
                $cSandi = $vaSandi[1];
                if ($cSandi === "A") {
                    $nSaldoA = $data->Saldo;
                } elseif ($cSandi === "B") {
                    $nSaldoB = $data->Saldo;
                }
            }

            $nRatio = PerhitunganTKS::getRatioE8($nSaldoA, $nSaldoB);

            $vaArray = [
                ['Sandi' => 'A', 'Keterangan' => 'Total Modal Lembaga', 'Nilai' => Func::formatSaldo($nSaldoA), 'Status' => 'I'],
                ['Sandi' => 'B', 'Keterangan' => 'Total Aset', 'Nilai' => Func::formatSaldo($nSaldoB), 'Status' => 'I'],
                ['Sandi' => 'RUMUS', 'Keterangan' => 'A / B x 100%', 'Nilai' => '', 'Status' => 'I'],
                ['Sandi' => '', 'Keterangan' => '', 'Nilai' => 'RATIO', 'Status' => 'ID'],
                ['Sandi' => 'SASARAN', 'Keterangan' => 'Maksimal 10%', 'Nilai' => Func::formatSaldo($nRatio) . '%', 'Status' => 'I'],
            ];

            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Modal Lembaga', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaArray);
            }
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];

            Func::writeLog('Modal Lembaga', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
