<?php

namespace App\Http\Controllers\api\tks\tandatandapertumbuhan;

use App\Helpers\Func;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PertumbuhanModalLembagaBersihController extends Controller
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
                ->where('Sandi', 'LIKE', '9.600%')
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

            $nRatio = PerhitunganTKS::getRatioS9($nSaldoA, $nSaldoB);

            $vaArray = [
                ['Sandi' => 'A', 'Keterangan' => 'Modal Lembaga Bersih Tahun Berjalan', 'Nilai' => Func::formatSaldo($nSaldoA), 'Status' => 'I'],
                ['Sandi' => 'B', 'Keterangan' => 'Modal Lembaga Bersih Sampai Akhir Tahun Lalu', 'Nilai' => Func::formatSaldo($nSaldoB), 'Status' => 'I'],
                ['Sandi' => 'RUMUS', 'Keterangan' => '[(A / B)-1] x 100 atau [(A - B) / B] x 100', 'Nilai' => '', 'Status' => 'I'],
                ['Sandi' => '', 'Keterangan' => '', 'Nilai' => 'RATIO', 'Status' => 'ID'],
                [
                    'Sandi' => 'SASARAN',
                    'Keterangan' => "1. Untuk meningkatkan persentase total modal lembaga bersih (E89), S9 harus lebih besar dari S11\n" .
                        "2. Untuk mempertahankan persentase total modal lembaga bersih (E9), S9 harus sama dengan S11\n" .
                        "3. Untuk mempertahankan persentase total modal lembaga bersih (E9), S9 harus sama dengan S11",
                    'Nilai' => Func::formatSaldo($nRatio) . '%',
                    'Status' => 'I'
                ],
            ];

            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Pertumbuhan Modal Lembaga Bersih', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Pertumbuhan Modal Lembaga Bersih', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
