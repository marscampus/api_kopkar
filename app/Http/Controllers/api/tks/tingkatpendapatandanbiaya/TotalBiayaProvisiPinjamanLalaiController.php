<?php

namespace App\Http\Controllers\api\tks\tingkatpendapatandanbiaya;

use App\Helpers\Func;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TotalBiayaProvisiPinjamanLalaiController extends Controller
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
                ->where('Sandi', 'LIKE', '10.400%')
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
            $nRatio = PerhitunganTKS::getRatioR10($nSaldoA, $nSaldoB, $nSaldoC);

            $vaArray = [
                ['Sandi' => 'A', 'Keterangan' => 'Total Biaya Provisi Untuk Semua Aset Bermasalah Tahun Berjalan', 'Nilai' => Func::formatSaldo($nSaldoA), 'Status' => 'I'],
                ['Sandi' => 'B', 'Keterangan' => 'Total Aset Sampai Akhir Tahun Ini', 'Nilai' => Func::formatSaldo($nSaldoB), 'Status' => 'I'],
                ['Sandi' => 'C', 'Keterangan' => 'Total Aset Sampai Akhir Tahun Lalu', 'Nilai' => Func::formatSaldo($nSaldoC), 'Status' => 'I'],
                ['Sandi' => 'RUMUS', 'Keterangan' => 'A / [(B + C) / 2] x 100%', 'Nilai' => '', 'Status' => 'I'],
                ['Sandi' => '', 'Keterangan' => '', 'Nilai' => 'RATIO', 'Status' => 'ID'],
                ['Sandi' => 'SASARAN', 'Keterangan' => 'Tersedia Untuk Menutup 100% Pinjaman Lalai > 12 Bulan dan 35% Untuk Pinjaman Lalai 1-12 Bulan', 'Nilai' => Func::formatSaldo($nRatio) . '%', 'Status' => 'I'],
            ];

            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Total Biaya Provisi Pinjaman Lalai', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Total Biaya Provisi Pinjaman Lalai', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
