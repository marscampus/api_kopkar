<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use App\Models\pinjaman\Debitur;
use App\Models\Pinjaman\JadwalAngsuran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KoreksiJadwalAngsuranController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $tgl = GetterSetter::getTglTransaksi();
            $data = Debitur::with('registernasabah')
                ->where('Rekening', $rekening)
                ->first();
            $result = [];
            if ($data) {
                $result['Nama'] = $data->registernasabah->Nama;
                $result['Plafond'] = $data->Plafond;
                $vaTgl = explode("-", GetterSetter::GetTglTransaksi());
                $dBulan = $vaTgl[1];
                $dTahun = $vaTgl[2];
                $dTglTAwal = date('Y-m-d', mktime(0, 0, 0, $dBulan, 1, $dTahun));
                $bakiDebet = GetterSetter::getBakiDebet($rekening, $tgl);

                if ($bakiDebet == 0) {
                    $L = 1;
                    $data2 = Angsuran::where('Rekening', $rekening)
                        ->orderByDesc('Tgl')
                        ->limit($L, 1)
                        ->first();

                    if ($data2) {
                        $tglAkhir = $data2->Tgl;
                    }
                } else {
                    $tglAkhir = $tgl;
                }
// return $tglAkhir."uwaa";
                $data3 = DB::table('debitur as d')
                    ->leftJoin('angsuran as a', function ($join) use ($tglAkhir) {
                        $join->on('a.rekening', '=', 'd.rekening')
                            ->where('a.Tgl', '<=', $tglAkhir);
                    })
                    ->selectRaw('SUM(a.KPokok) as TotPokok, SUM(a.KBunga) as TotBunga, d.Rekening, MAX(d.Tgl) as TglRealisasi, d.Plafond, d.Lama, d.CaraPerhitungan, MAX(SukuBunga) as SukuBunga')
                    ->where('d.Rekening', $rekening)
                    ->groupBy('d.Rekening', 'd.Plafond', 'd.Lama', 'd.CaraPerhitungan')
                    ->get();


                foreach ($data3 as $d3) {
                    $lama = $d3->Lama;
                    $plafond = $d3->Plafond;
                    $tglAwal = Carbon::parse($d3->TglRealisasi);
                    $dTglRealisasi = Carbon::parse($tglAwal);
                    $dTglJthTmp = $dTglRealisasi->addMonths($lama)->format('Y-m-d');
                    $bakiDebet = $plafond;
                    $ke = 0;
                    $row = 0;
                    $totalPokok = 0;
                    $totalBunga = 0;
                    $totalAngsuran = 0;
                    $array['detail'] = [];

                    for ($dTgl = $tglAwal->copy(); $dTgl <= $dTglJthTmp; $dTgl->addMonth()) {
                        $ke++;
                        $tgl = $tglAwal->addMonths($ke)->format('Y-m-d');

                        $pokok = GetterSetter::getAngsuranPokok($rekening, $ke);
                        $bunga = GetterSetter::getAngsuranBunga($rekening, $ke, $bakiDebet);
                        $jumlah = $pokok + $bunga;
                        $totalPokok += $pokok;
                        $totalBunga += $bunga;
                        $bakiDebet -= $pokok;
                        $totalAngsuran = $totalPokok + $totalBunga;
                        $array['detail'][] = [
                            'Ke' => $ke,
                            'TglJadwal' => $tgl,
                            'Pokok' => $pokok,
                            'Bunga' => $bunga,
                            'Jumlah' => $jumlah,
                            'BakiDebet' => $bakiDebet
                        ];
                    }

                    $result['detail'] = $array['detail'];
                    $result['Plafond'] = $plafond;
                    $result['TotalPokok'] = $totalPokok;
                    $result['TotalBunga'] = $totalBunga;
                    $result['TotalAngsuran'] = $totalAngsuran;
                }
            }
            // else {
            //     return response()->json(
            //         ['status' => 'error', 'message' => 'No. Rekening Tidak Valid!'],
            //         404
            //     );
            // }

            return response()->json($result);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function store(Request $request)
    {
        $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
        $errorMessages = []; // Menyimpan pesan-pesan error

        foreach ($request->input('array') as $item) {
            try {
                $rekening = $item['Rekening'];
                $ke = $item['Ke'];
                $array = [
                    'ke' => $ke,
                    'Rekening' => $rekening,
                    'Tgl' => $item['TglJadwal'],
                    'Pokok' => $item['Pokok'],
                    'Bunga' => $item['Bunga'],
                    'BakiDebet' => $item['BakiDebet'],
                    'Username' => $item['UserName'],
                    'DateTime' => Carbon::now()
                ];
                $exists = JadwalAngsuran::where('Rekening', $rekening)
                    ->where('ke', $ke)
                    ->exists();
                if ($exists) {
                    JadwalAngsuran::where('Rekening', $rekening)
                        ->where('ke', $ke)
                        ->update($array);
                } else {
                    JadwalAngsuran::create($array);
                }
            } catch (\Throwable $th) {
                $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                $errorMessages[] = $th->getMessage(); // Simpan pesan error ke dalam array
            }
        }

        if ($allIterationsSuccessful) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error', 'errors' => $errorMessages]);
        }
    }
}
