<?php

namespace App\Http\Controllers\api\tks;

use App\Helpers\Func;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanAnalisisController extends Controller
{
    public function data()
    {
        // Mengambil tahun dari request (bisa disesuaikan dari form atau parameter)
        $tahun = request('Periode', Carbon::now()->year);

        // Mengatur tanggal akhir tahun
        $tglAkhir = Carbon::create($tahun, 12, 31)->format('Y-m-d');

        // Inisialisasi variabel saldo
        $saldo_a = $saldo_b = $saldo_c = $saldo_d = 0.00;
        $saldo_e = $saldo_f = $saldo_g = $saldo_h = 0.00;
        $saldo_i = $saldo_j = 0.00;

        // Menghitung total record untuk progress (bisa diabaikan jika tidak butuh)
        $row = DB::table('mastertkspearls')->count();

        // Inisialisasi data master
        $masterData = [];

        // Query untuk mengambil data sesuai periode
        $tksPearls = DB::table('tkspearls')->where('Periode', $tglAkhir)
            ->orderBy('ID', 'asc')
            ->orderBy('Sandi', 'asc')
            ->get();

        // Inisialisasi sandiRekening dan rumusRekening
        $sandiRekening = '';
        $rumusRekening = '';

        foreach ($tksPearls as $pearl) {
            $sandiArray = explode(" ", $pearl->Sandi);
            $sandi = trim($sandiArray[1]);
            $keterangan = $pearl->Keterangan;
            $jenis = $pearl->Jenis;
            $sasaran = $pearl->Sasaran;
            $rumus = $pearl->Rumus;
            $saldo = $pearl->Saldo;

            if ($jenis != "I") {
                switch ($sandi) {
                    case 'A':
                        $saldo_a = $saldo;
                        break;
                    case 'B':
                        $saldo_b = $saldo;
                        break;
                    case 'C':
                        $saldo_c = $saldo;
                        break;
                    case 'D':
                        $saldo_d = $saldo;
                        break;
                    case 'E':
                        $saldo_e = $saldo;
                        break;
                    case 'F':
                        $saldo_f = $saldo;
                        break;
                    case 'G':
                        $saldo_g = $saldo;
                        break;
                    case 'H':
                        $saldo_h = $saldo;
                        break;
                    case 'I':
                        $saldo_i = $saldo;
                        break;
                    case 'J':
                        $saldo_j = $saldo;
                        break;
                }

                $masterData[] = [
                    'Sandi' => '   ' . $sandi,
                    'Keterangan' => $keterangan,
                    'Sasaran' => $sasaran,
                    'Nilai' => number_format($saldo, 2),
                    'Ratio' => '',
                    'Jenis' => $jenis
                ];
            } else {
                // Jika ada perhitungan rasio
                if (!empty($sandiRekening) && $sandi != $sandiRekening) {
                    $masterData[] = [
                        'Sandi' => '',
                        'Keterangan' => $rumusRekening,
                        'Sasaran' => '',
                        'Nilai' => '',
                        'Ratio' => number_format(PerhitunganTKS::getRatio($sandi, $saldo_a, $saldo_b, $saldo_c, $saldo_d, $saldo_e, $saldo_f, $saldo_g, $saldo_h, $saldo_i, $saldo_j), 2) . '%',
                        'Jenis' => $jenis
                    ];

                    // Reset saldo setelah perhitungan
                    $saldo_a = $saldo_b = $saldo_c = $saldo_d = 0.00;
                    $saldo_e = $saldo_f = $saldo_g = $saldo_h = 0.00;
                    $saldo_i = $saldo_j = 0.00;
                }

                $masterData[] = [
                    'Sandi' => $sandi,
                    'Keterangan' => $keterangan,
                    'Sasaran' => $sasaran,
                    'Nilai' => '',
                    'Ratio' => '',
                    'Jenis' => $jenis
                ];

                // Menyimpan nilai sandi dan rumus untuk perhitungan berikutnya
                $sandiRekening = $sandi;
                $rumusRekening = $rumus;
            }
        }

        // Jika masih ada rasio yang belum dihitung
        if (!empty($sandiRekening)) {
            $masterData[] = [
                'Sandi' => '',
                'Keterangan' => $rumusRekening,
                'Sasaran' => '',
                'Nilai' => '',
                'Ratio' => number_format(PerhitunganTKS::getRatio($sandi, $saldo_a, $saldo_b, $saldo_c, $saldo_d, $saldo_e, $saldo_f, $saldo_g, $saldo_h, $saldo_i, $saldo_j), 2) . '%',
                'Jenis' => 'I'
            ];
        }

        // Kembalikan data ke view atau JSON
        return response()->json($masterData);
    }
}
