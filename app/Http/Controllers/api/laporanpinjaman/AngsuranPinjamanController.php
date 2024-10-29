<?php

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use App\Models\pinjaman\Debitur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AngsuranPinjamanController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cGroupBy = $vaRequestData['GroupBy'];
            $cOrder = "d.GolonganKredit";
            if ($cGroupBy == 'K') {
                $cOrder = "d.CabangEntry";
            } else if ($cGroupBy == 'A') {
                $cOrder = "d.AO";
            }
            $cJenisMutasi = "";
            if ($cJenisMutasi == 'P') {
                $cJenisMutasi = "a.Faktur NOT LIKE 'ANG%'";
            } else if ($cJenisMutasi == 'M') {
                $cJenisMutasi = "a.Faktur LIKE 'ANG%'";
            }
            $vaData = DB::table('angsuran as a')
                ->select(
                    'a.Faktur',
                    'a.Tgl',
                    'd.Rekening',
                    'd.RekeningLama',
                    'd.GolonganKredit',
                    'a.KPokok',
                    'a.KBunga',
                    'a.Denda',
                    'r.Nama',
                    'g.Keterangan as NamaGolongan',
                    'a.CabangEntry',
                    'c.Keterangan as NamaCabang',
                    'd.AO',
                    'o.Nama as NamaAO',
                    'a.Username',
                    'c.Kode as CabangNasabah'
                )
                ->leftJoin('debitur as d', 'd.Rekening', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('golongankredit as g', 'g.Kode', '=', 'd.GolonganKredit')
                ->leftJoin('cabang AS c', 'c.kode', '=', 'd.CabangEntry')
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->where('a.Status', '=', '5')
                ->whereBetween('a.Tgl', [$dTglAwal, $dTglAkhir])
                ->when(
                    !empty($cJenisMutasi), // Ubah di sini untuk memeriksa apakah $cJenisMutasi tidak kosong
                    function ($query) use ($cJenisMutasi) {
                        $query->whereRaw($cJenisMutasi); // Gunakan $cJenisMutasi langsung
                    }
                )
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->orderBy($cOrder)
                ->orderBy('a.Tgl')
                ->orderBy('a.Faktur')
                ->get();
            $nRow = 0;
            $nTotalBakiDebet = 0;
            $nTotalPokok = 0;
            $nTotalBunga = 0;
            $nTotalDenda = 0;
            $nTotalAngsuran = 0;
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $cRekeningLama = $d->RekeningLama;
                $nPokok = $d->KPokok;
                $nBunga = $d->KBunga;
                $nDenda = $d->Denda;
                $nAngsuran = $nPokok + $nBunga + $nDenda;
                $dTgl = $d->Tgl;
                $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTgl);
                $cFaktur = $d->Faktur;
                $cNama = $d->Nama;
                $cAO = $d->AO;
                $cGroup = $d->GolonganKredit;
                $cNamaGroup = $d->NamaGolongan;
                if ($cGroupBy == 'K') {
                    $cGroup = $d->CabangEntry;
                    $cNamaGroup = $d->NamaCabang;
                }
                if ($cGroupBy == 'A') {
                    $cGroup = $d->AO;
                    $cNamaGroup = $d->NamaAO;

                    $vaData2 = DB::table('debitur_ao as da')
                        ->select(
                            DB::raw('IFNULL(o.Kode, "") AS AOBaru'),
                            'o.Nama'
                        )
                        ->leftJoin('ao as o', 'o.Kode', '=', 'da.AO_Baru')
                        ->where('da.Rekening', '=', $cRekening)
                        ->orderByDesc('da.ID')
                        ->first();
                    if ($vaData2) {
                        $cAOBaru = $vaData2->AOBaru;
                        if (!empty($cAOBaru)) {
                            $cGroup = $vaData2->AOBaru;
                            $cNamaGroup = $vaData2->Nama;
                        }
                    }
                }

                $nTotalPokok += $nPokok;
                $nTotalBunga += $nBunga;
                $nTotalDenda += $nDenda;
                $nTotalAngsuran += $nAngsuran;
                $vaArray[] = [
                    'No' => ++$nRow,
                    'Rekening' => $cRekening,
                    'GolonganPinjaman' => $cGroup . ' - ' . $cNamaGroup,
                    'Cabang' => $d->CabangNasabah,
                    'UserName' => $d->Username,
                    'Nama' => $cNama,
                    'Tgl' => $dTgl,
                    'Faktur' => $cFaktur,
                    'Pokok' => $nPokok,
                    'Bunga' => $nBunga,
                    'Denda' => $nDenda,
                    'Jumlah' => $nAngsuran,
                    'BakiDebet' => $nBakiDebet ?? 0,
                    'AO' => $cAO
                ];
                $vaTotal = [
                    'TotalPokok' => $nTotalPokok,
                    'TotalBunga' => $nTotalBunga,
                    'TotalDenda' => $nTotalDenda,
                    'TotalJumlah' => $nTotalAngsuran
                ];
            }
            $numericArray = array_values($vaArray);

            $responseData = [
                'data' => $numericArray,
                'totals' => $vaTotal
            ];

            if ($responseData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $responseData
                ];
                Func::writeLog('Angsuran Pinjaman', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($responseData);
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

            Func::writeLog('Angsuran Pinjaman', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function data1(Request $request)
    {
        $originalMaxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', '0');
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $vaTotal = [];
        $data = DB::table('angsuran')
            ->select(
                'angsuran.Faktur',
                'angsuran.Tgl',
                'debitur.Rekening',
                'debitur.RekeningLama',
                'debitur.GolonganKredit',
                'angsuran.KPokok',
                'angsuran.KBunga',
                'angsuran.Denda',
                'registernasabah.Nama',
                'golongankredit.Keterangan AS KetGolongan',
                'angsuran.CabangEntry',
                'cabang.Keterangan AS KetCabang',
                'debitur.AO',
                'ao.Nama AS NamaAO',
                'angsuran.UserName',
                'debitur.CabangEntry AS CabangNasabah'
            )
            ->join('debitur', 'angsuran.Rekening', '=', 'debitur.Rekening')
            ->join('registernasabah', 'debitur.Kode', '=', 'registernasabah.Kode')
            ->join('golongankredit', 'debitur.GolonganKredit', '=', 'golongankredit.Kode')
            ->join('cabang', 'debitur.CabangEntry', '=', 'cabang.Kode')
            ->join('ao', 'debitur.AO', '=', 'ao.Kode')
            ->where('angsuran.Status', '=', '5')
            ->where('angsuran.Tgl', '>=', $tglAwal)
            ->where('angsuran.Tgl', '<=', $tglAkhir)
            ->orderBy('angsuran.Tgl', 'desc')
            ->orderBy('angsuran.Faktur', 'desc')
            ->get();
        $row = 0;
        $totalBakiDebet = 0;
        $totalPokok = 0;
        $totalBunga = 0;
        $totalDenda = 0;
        $totalAngsuran = 0;
        $vaArray = [];
        $totals = [];
        foreach ($data as $d) {
            $rekening = $d->Rekening;
            $rekeningLama = $d->RekeningLama;
            $pokok = $d->KPokok;
            $bunga = $d->KBunga;
            $denda = $d->Denda;
            $angsuran = $pokok + $bunga + $denda;
            $tgl = $d->Tgl;
            $bakiDebet = GetterSetter::getBakiDebet($rekening, $tgl);
            $faktur = $d->Faktur;
            $nama = $d->Nama;
            $ao = $d->AO;
            $golKredit = $d->GolonganKredit;
            $ketGolKredit = $d->KetGolongan;
            // if (!isset($totals[$golKredit])) {
            //     $totals[$golk]
            // }
            $totalPokok += $pokok;
            $totalBunga += $bunga;
            $totalDenda += $denda;
            $totalAngsuran += $angsuran;

            $vaArray[] = [
                'No' => ++$row,
                'Rekening' => $rekening,
                'GolonganPinjaman' => $golKredit . ' - ' . $ketGolKredit,
                'Cabang' => $d->CabangNasabah,
                'UserName' => $d->UserName,
                'Nama' => $nama,
                'Tgl' => $tgl,
                'Faktur' => $faktur,
                'Pokok' => $pokok,
                'Bunga' => $bunga,
                'Denda' => $denda,
                'Jumlah' => $angsuran,
                'BakiDebet' => $bakiDebet ?? 0,
                'AO' => $ao
            ];
            $vaTotal = [
                'TotalPokok' => $totalPokok,
                'TotalBunga' => $totalBunga,
                'TotalDenda' => $totalDenda,
                'TotalJumlah' => $totalAngsuran
            ];
        }

        $numericArray = array_values($vaArray);

        $responseData = [
            'data' => $numericArray,
            'totals' => $vaTotal
        ];

        return response()->json($responseData);
        ini_set('max_execution_time', $originalMaxExecutionTime);
    }
}
