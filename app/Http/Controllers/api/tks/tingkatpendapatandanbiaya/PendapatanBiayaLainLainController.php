<?php

namespace App\Http\Controllers\api\tks\tingkatpendapatandanbiaya;

use App\Helpers\Func;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendapatanBiayaLainLainController extends Controller
{
    function data(Request $request)
    {
        try {
            $nSaldoA = 0;
            $nSaldoB = 0;
            $nSaldoC = 0;
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
                ->where('Sandi', 'LIKE', '11.400%')
                ->get();
            foreach ($vaData as $data) {
                $vaSandi = explode(" ", $data->Sandi);
                $cSandi = $vaSandi[1];
                if ($cSandi === "A") {
                    $nSaldoA = $data->Saldo;
                } elseif ($cSandi === "B") {
                    $nSaldoB = $data->Saldo;
                } elseif ($cSandi === "C") {
                    $nSaldoC = $data->Saldo;
                }
            }
            $nRatio = PerhitunganTKS::getRatioR11($nSaldoA, $nSaldoB, $nSaldoC);

            $vaArray = [
                ['Sandi' => 'A', 'Keterangan' => 'Total Pendapatan Atau Biaya Lain-Lain (Non-Recurring Income Or Expense) Tahun Berjalan', 'Nilai' => Func::formatSaldo($nSaldoA), 'Status' => 'I'],
                ['Sandi' => 'B', 'Keterangan' => 'Total Aset Sampai Dengan Akhir Tahun Berjalan', 'Nilai' => Func::formatSaldo($nSaldoB), 'Status' => 'I'],
                ['Sandi' => 'C', 'Keterangan' => 'Total Aset Sampai Akhir Tahun Llau', 'Nilai' => Func::formatSaldo($nSaldoC), 'Status' => 'I'],
                ['Sandi' => 'RUMUS', 'Keterangan' => 'A / [(A + B) / 2] x 100%', 'Nilai' => '', 'Status' => 'I'],
                ['Sandi' => '', 'Keterangan' => '', 'Nilai' => 'RATIO', 'Status' => 'ID'],
                ['Sandi' => 'SASARAN', 'Keterangan' => 'Sekecil Mungkin', 'Nilai' => Func::formatSaldo($nRatio) . '%', 'Status' => 'I'],
            ];

            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Pendapatan Biaya Lain-Lain', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Pendapatan Biaya Lain-Lain', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
