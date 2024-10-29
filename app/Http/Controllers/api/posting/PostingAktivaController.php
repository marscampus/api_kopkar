<?php

namespace App\Http\Controllers\api\posting;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganPinjaman;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\jurnal\Jurnal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostingAktivaController extends Controller
{

    public function data(Request $request)
    {
        try {
            $originalMaxExecutionTime = ini_get('max_execution_time');
            ini_set('max_execution_time', '0');
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            // $nReqCount = count($vaRequestData);
            // if ($nReqCount !== 3) {  // Changed condition to check for equality
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Laporan Penyusutan Aktiva', 'data', $vaRequestData, $vaRetVal, $cUser);
            //     return response()->json(['status' => 'error']);
            // }
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dBulan = $vaRequestData['Bulan'];
            $dTahun = $vaRequestData['Tahun'];
            $nTime = mktime(0, 0, 0, $dBulan + 1, 0, $dTahun);
            $dTgl = date('Y-m-d', $nTime);
            // $dTglBlnIni = date('Y-m-d', mktime(0, 0, 0, $dBulan + 1, $dTahun));
            $vaData = DB::table('aktiva as a')
                ->select(
                    'a.Kode',
                    'a.Nama',
                    'a.Unit',
                    'a.TglPerolehan',
                    'a.HargaPerolehan',
                    'a.Golongan',
                    'a.Lama',
                    'a.CabangEntry as Cabang',
                    'a.Residu',
                    'a.TarifPenyusutan',
                    'a.PenyusutanPerBulan',
                    'a.JenisPenyusutan',
                    'g.Keterangan as NamaGolongan',
                    'a.Status'
                )
                ->leftJoin('golonganaktiva as g', 'g.Kode', '=', 'a.Golongan')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'a.CabangEntry')
                ->where('a.TglPerolehan', '<=', $dTgl)
                ->where('a.TglWriteOff', '>', $dTgl)
                ->where('a.TglPenyusutan', '<=', $dTgl)
                ->where('g.Kode', '<>', 'BDD')
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->orderBy('a.Golongan')
                ->orderBy('a.Kode')
                ->get();

            $nTotalHargaPerolehan = 0;
            $nTotalPenyusutanAwal = 0;
            $nTotalPenyusutanBulanIni = 0;
            $nTotalPenyusutanAkhir = 0;
            $nTotalNilaiBuku = 0;
            $optJenisLaporan = '';
            $vaResult = [];
            foreach ($vaData as $d) {
                $va = PerhitunganPinjaman::getPenyusutan($d->Kode, $dTgl, $d->Cabang);
                $nPenyusutanAkhir = $va['Akhir'];
                $nNilaiBuku = $d->HargaPerolehan - $nPenyusutanAkhir;

                if ($nNilaiBuku > 0) {
                    $nNilaiBukuAkhir  = $nNilaiBuku;
                    $nNilaiBulanIni   = $va['BulanIni'];
                    $nPenyusutanAkhir = $va['Akhir'];
                    $nPenyusutanAwal  = $va['Awal'];
                } else {
                    $nNilaiBukuAkhir = Func::String2Number($d->Unit);
                    $nNilaiBulanIni   = $va['BulanIni'];
                    $nPenyusutanAkhir = $va['Akhir'] -  Func::String2Number($d->Unit);
                    $nPenyusutanAwal  = $va['Awal'] -  Func::String2Number($d->Unit);
                }

                if ($d->Status == '2') {
                    $nNilaiBukuAkhir = 0;
                }

                $bFilter = true;
                if ($optJenisLaporan == 'Aktif' && $nNilaiBukuAkhir == 0 && $nNilaiBulanIni == 0) {
                    $bFilter = false;
                }
                if ($optJenisLaporan == 'TidakAktif' && $nNilaiBukuAkhir > 1) {
                    $bFilter = false;
                }

                if ($bFilter) {
                    $nTotalHargaPerolehan += $d->HargaPerolehan;
                    $nTotalPenyusutanAwal += $nPenyusutanAwal;
                    $nTotalPenyusutanBulanIni += $va['BulanIni'];
                    $nTotalPenyusutanAkhir += $nPenyusutanAkhir;
                    $nTotalNilaiBuku += $nNilaiBukuAkhir;

                    $cCabang = $d->Cabang;
                    $cGolongan = $d->Golongan;

                    if (empty($vaResult[$cCabang][$cGolongan])) {
                        $cKeterangan = GetterSetter::getKeterangan($cGolongan, 'Keterangan', 'golonganaktiva');
                        $vaResult[$cCabang][$cGolongan] = [
                            "Cabang" => $cCabang,
                            "Golongan" => $cGolongan,
                            "Keterangan" => $cKeterangan,
                            "Awal" => 0,
                            "Penyusutan" => 0,
                            "Akhir" => 0,
                            "NilaiBuku" => 0,
                        ];
                    }

                    $vaResult[$cCabang][$cGolongan]['Awal'] += $nPenyusutanAwal;
                    $vaResult[$cCabang][$cGolongan]['Penyusutan'] += $nNilaiBulanIni;
                    $vaResult[$cCabang][$cGolongan]['Akhir'] += $nPenyusutanAkhir;
                    $vaResult[$cCabang][$cGolongan]['NilaiBuku'] += $nNilaiBukuAkhir;
                }
            }
            $finalResult = [];
            foreach ($vaResult as $cabangData) {
                foreach ($cabangData as $golonganData) {
                    $finalResult[] = $golonganData;
                }
            }
            // Return response after the loop
            $vaRetVal = [
                "status" => "00",
                "message" => $finalResult
            ];

            Func::writeLog('Posting Aktiva', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($finalResult);
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

            Func::writeLog('Posting Aktiva', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        } finally {
            ini_set('max_execution_time', $originalMaxExecutionTime);
        }
    }

    public function posting(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dBulan = $vaRequestData['Bulan'];
            $dTahun = $vaRequestData['Tahun'];
            $nTime = mktime(0, 0, 0, $dBulan + 1, 0, $dTahun);
            $dTgl = date('Y-m-d', $nTime);
            $dTglTransaksi = GetterSetter::getTglTransaksi();
            foreach ($request->input('updJurnal') as $data) {
                $cCabangEntry = $data['Cabang'];
                $cGolongan = $data['Golongan'];
                $nPenyusutan = $data['Penyusutan'];
                $nAkhir = Func::String2Number($data['Akhir']);
                $nNilaiBuku = Func::String2Number($data['NilaiBuku']);
                $cKeterangan = "AKM PENYUSUTAN " . $dTgl . " " . $data['Keterangan'];
                $cRekeningAkuntansi = "";
                $cRekeningBiaya = "";
                $vaData = DB::table('golonganaktiva')
                    ->select(
                        'RekeningDebet',
                        'RekeningKredit'
                    )
                    ->where('Kode', '=', $cGolongan)
                    ->orderBy('Kode')
                    ->first();
                if ($vaData) {
                    $cRekeningAkuntansi = $vaData->RekeningDebet;
                    $cRekeningBiaya = $vaData->RekeningKredit;
                }
                if ($nPenyusutan > 0) {
                    Jurnal::where('CabangEntry', '=', $cCabangEntry)
                        ->where('Faktur', 'LIKE', 'JR%')
                        ->where('Keterangan', '=', $cKeterangan)
                        ->delete();
                    BukuBesar::where('Cabang', '=', $cCabangEntry)
                        ->where('Faktur', 'LIKE', 'JR%')
                        ->where('Keterangan', '=', $cKeterangan)
                        ->delete();
                    $cFaktur = GetterSetter::getLastFaktur('JR', true);
                    $nSaldoNeraca = 0;
                    $nSaldoNeraca = GetterSetter::getSaldoAwal($dTglTransaksi, $cRekeningAkuntansi, '', false, $cCabangEntry, true, false, 'C');
                    $nDebet = 0;
                    $nKredit = 0;
                    if ($nSaldoNeraca < 0) {
                        $nDebet = abs($nSaldoNeraca);
                    } else {
                        $nKredit = abs($nSaldoNeraca);
                    }

                    $nDebet = 0;
                    $nKredit = 0;
                    $nSelisih = $nAkhir + $nSaldoNeraca;
                    if ($cGolongan == "BDD") {
                        $nSelisih = $nSaldoNeraca - $nNilaiBuku;
                    }
                    if ($nSelisih < 0) {
                        $nKredit = abs($nSelisih);
                    } else {
                        $nDebet = abs($nSelisih);
                    }

                    Upd::updJurnalLainLain($dTglTransaksi, $cFaktur, $cRekeningAkuntansi, $cKeterangan, 0, $nPenyusutan, true, '', '', 'N', $cCabangEntry, $cUser);
                    Upd::updJurnalLainLain($dTglTransaksi, $cFaktur, $cRekeningBiaya, $cKeterangan, $nPenyusutan, 0, true, '', '', 'N', $cCabangEntry, $cUser);
                }
                GetterSetter::setLastFaktur('JR');
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "success"
            ];
            Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
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

            Func::writeLog('Posting Aktiva', 'posting', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }
}
