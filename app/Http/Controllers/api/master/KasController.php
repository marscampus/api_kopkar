<?php
/*
 * Copyright (C) Godong
 *http://www.marstech.co.id
 *Email. info@marstech.co.id
 *Telp. 0811-3636-09
 *Office        : Jl. Margatama Asri IV, Kanigoro, Kec. Kartoharjo, Kota Madiun, Jawa Timur 63118
 *Branch Office : Perum Griya Gadang Sejahtera Kav. 14 Gadang - Sukun - Kota Malang - Jawa Timur
 *
 *Godong
 *Adalah merek dagang dari PT. Marstech Global
 *
 *License Agreement
 *Software komputer atau perangkat lunak komputer ini telah diakui sebagai salah satu aset perusahaan yang bernilai.
 *Di Indonesia secara khusus,
 *software telah dianggap seperti benda-benda berwujud lainnya yang memiliki kekuatan hukum.
 *Oleh karena itu pemilik software berhak untuk memberi ijin atau tidak memberi ijin orang lain untuk menggunakan softwarenya.
 *Dalam hal ini ada aturan hukum yang berlaku di Indonesia yang secara khusus melindungi para programmer dari pembajakan software yang mereka buat,
 *yaitu diatur dalam hukum hak kekayaan intelektual (HAKI).
 *
 *********************************************************************************************************
 *Pasal 72 ayat 3 UU Hak Cipta berbunyi,
 *' Barangsiapa dengan sengaja dan tanpa hak memperbanyak penggunaan untuk kepentingan komersial '
 *' suatu program komputer dipidana dengan pidana penjara paling lama 5 (lima) tahun dan/atau '
 *' denda paling banyak Rp. 500.000.000,00 (lima ratus juta rupiah) '
 *********************************************************************************************************
 *
 *Proprietary Software
 *Adalah software berpemilik, sehingga seseorang harus meminta izin serta dilarang untuk mengedarkan,
 *menggunakan atau memodifikasi software tersebut.
 *
 *Commercial software
 *Adalah software yang dibuat dan dikembangkan oleh perusahaan dengan konsep bisnis,
 *dibutuhkan proses pembelian atau sewa untuk bisa menggunakan software tersebut.
 *Detail Licensi yang dianut di software https://en.wikipedia.org/wiki/Proprietary_software
 *EULA https://en.wikipedia.org/wiki/End-user_license_agreement
 *
 *Lisensi Perangkat Lunak https://id.wikipedia.org/wiki/Lisensi_perangkat_lunak
 *EULA https://id.wikipedia.org/wiki/EULA
 *
 * Created on Mon Mar 04 2024 - 06:24:59
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use Carbon\Carbon;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\Kas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;



class KasController extends Controller
{

    public function getFaktur(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        $response = GetterSetter::getLastFaktur($KODE, $LEN);
        return $response;
    }

    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTgl = $vaRequestData['Tgl'];
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData->Cabang;
            }
            $vaResult = [];
            $cRekeningKas = $vaRequestData['RekeningKas'] ?? GetterSetter::getDBConfig('msRekeningKas');
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.Faktur',
                    'j.Tgl',
                    'j.Rekening',
                    'j.CabangEntry',
                    DB::raw('IFNULL(SUM(j.Debet), 0) as Penerimaan'),
                    DB::raw('IFNULL(SUM(j.Kredit), 0) as Pengeluaran'),
                    'j.Keterangan'
                )
                ->leftJoin('cabang as c', 'c.Kode', '=', 'j.CabangEntry')
                ->where('j.Tgl', '=', $dTgl)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->when(
                    $cRekeningKas !== '',
                    function ($query) use ($cRekeningKas) {
                        $query->where('j.Rekening', '=', $cRekeningKas);
                    }
                )
                ->groupBy('j.Faktur', 'j.Tgl', 'j.Keterangan', 'j.Rekening')
                ->havingRaw("(j.Faktur LIKE 'KM%' AND SUM(j.Kredit) = 0) OR (j.Faktur LIKE 'KK%' AND SUM(j.Debet) = 0)");

            if (!empty($request->filters)) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }

            $vaData = $vaData->get();
            $nRow = 0;
            $nTotalPengeluaran = 0;
            $nTotalPenerimaan = 0;
            foreach ($vaData as $d) {
                $vaResult[] = [
                    'No' => ++$nRow,
                    'Faktur' => $d->Faktur,
                    'Tgl' => $d->Tgl,
                    'Rekening' => $d->Rekening,
                    'Penerimaan' => $d->Penerimaan,
                    'Pengeluaran' => $d->Pengeluaran,
                    'Keterangan' => $d->Keterangan
                ];
                $nTotalPengeluaran += $d->Pengeluaran;
                $nTotalPenerimaan += $d->Penerimaan;
            }
            $vaTotal = [
                'TotalPengeluaran' => $nTotalPengeluaran,
                'TotalPenerimaan' => $nTotalPenerimaan
            ];
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'totals' => $vaTotal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Kas', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
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
            Func::writeLog('Kas', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
        }
    }

    function data2(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            // unset($vaRequestData['auth']);
            // unset($vaRequestData['page']);
            $dTglAwal = $request['tglAwal'];
            // $dTglAwal = Carbon::createFromFormat('Y/m/d', $dTglAwal)->format('Y-m-d');

            $dTglAkhir = $request['tglAkhir'];
            
            // $dTglAkhir = Carbon::createFromFormat('Y/m/d', $dTglAkhir)->format('Y-m-d');
            //$cJenisGabungan = $vaRequestData['JenisGabungan'];
            // $cCabang = null;
            // if ($cJenisGabungan !== "C") {
            //     $cCabang = $vaRequestData->Cabang;
            // }
            $vaResult = [];
            $cRekeningKas = $vaRequestData['RekeningKas'] ?? GetterSetter::getDBConfig('msRekeningKas');
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.Faktur',
                    'j.Tgl',
                    'j.Rekening',
                    'j.CabangEntry',
                    DB::raw('IFNULL(SUM(j.Debet), 0) as Penerimaan'),
                    DB::raw('IFNULL(SUM(j.Kredit), 0) as Pengeluaran'),
                    'j.Keterangan'
                )
               //->leftJoin('cabang as c', 'c.Kode', '=', 'j.CabangEntry')
                // ->where('j.Tgl', '>=', '2024-03-01')
                // ->where('j.Tgl', '=', '2024/03/01')
                // ->where('j.Tgl', '<=', $dTglAkhir)
                ->whereBetween('j.Tgl', [$dTglAwal, $dTglAkhir])
                // ->when(
                //     $cJenisGabungan !== 'C',
                //     function ($query) use ($cJenisGabungan, $cCabang) {
                //         $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                //     }
                // )
                // ->when(
                //     $cRekeningKas !== '',
                //     function ($query) use ($cRekeningKas) {
                //         $query->where('j.Rekening', '=', $cRekeningKas);
                //     }
                // )
                ->groupBy('j.Faktur', 'j.Tgl', 'j.Keterangan', 'j.Rekening')
                ->havingRaw("(j.Faktur LIKE 'KM%' AND SUM(j.Kredit) = 0) OR (j.Faktur LIKE 'KK%' AND SUM(j.Debet) = 0)");
                // ->limit(10);
                
            if (!empty($request->filters)) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }

            $vaData = $vaData->get();
            $nRow = 0;
            $nTotalPengeluaran = 0;
            $nTotalPenerimaan = 0;
            foreach ($vaData as $d) {
                $vaResult[] = [
                    'No' => ++$nRow,
                    'Faktur' => $d->Faktur,
                    'Tgl' => $d->Tgl,
                    'Rekening' => $d->Rekening,
                    'Penerimaan' => $d->Penerimaan,
                    'Pengeluaran' => $d->Pengeluaran,
                    'Keterangan' => $d->Keterangan
                ];
                $nTotalPengeluaran += $d->Pengeluaran;
                $nTotalPenerimaan += $d->Penerimaan;
            }
            $vaTotal = [
                'TotalPengeluaran' => $nTotalPengeluaran,
                'TotalPenerimaan' => $nTotalPenerimaan
            ];
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'totals' => $vaTotal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Kas', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => $th,//"REQUEST TIDAK Valdi",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Kas', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
        }
    }

    public function getDataByFakturDebet(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $Kas = Kas::select('jurnal.ID', 'jurnal.Faktur', 'jurnal.Tgl', 'jurnal.rekening', 'rekening.keterangan AS KeteranganRekening', 'jurnal.Debet AS Jumlah', 'jurnal.Keterangan')
                ->join('rekening', 'jurnal.rekening', '=', 'rekening.kode')
                ->where('jurnal.Faktur', '=', $Faktur)
                ->where('jurnal.Debet', '!=', 0)
                ->get();
            return response()->json($Kas);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataByFakturKredit(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $Kas = Kas::select('jurnal.ID', 'jurnal.Faktur', 'jurnal.Tgl', 'jurnal.rekening', 'rekening.keterangan AS KeteranganRekening', 'jurnal.Kredit AS Jumlah', 'jurnal.Keterangan')
                ->join('rekening', 'jurnal.rekening', '=', 'rekening.kode')
                ->where('jurnal.Faktur', '=', $Faktur)
                ->where('jurnal.Kredit', '!=', 0)
                ->get();
            return response()->json($Kas);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        $Faktur = $request->Faktur;
        $Tgl = $request->Tgl;
        $RekeningDebet = $request->RekeningDebet;
        $RekeningKredit = $request->RekeningKredit;
        $Debet = $request->Debet;
        $Kredit = $request->Kredit;
        $keterangan = $request->Keterangan;

        if ($Debet != null) { //-------------------------------------< di penerimaan kas >
            try {
                $Kas = Kas::create([
                    'Faktur' => $Faktur,
                    'Tgl' => $Tgl,
                    'Rekening' => $RekeningDebet,
                    'Debet' => $Debet,
                    'Kredit' => 0,
                    'Keterangan' => $keterangan,
                ]);
            } catch (\Throwable $th) {
                return response()->json(['status' => 'error']);
            }
            try {
                $Kas = Kas::create([
                    'Faktur' => $Faktur,
                    'Tgl' => $Tgl,
                    'Rekening' => $RekeningKredit,
                    'Debet' => 0,
                    'Kredit' => $Debet,
                    'Keterangan' => $keterangan,
                ]);
                GetterSetter::setLastFaktur('KM');
            } catch (\Throwable $th) {
                return response()->json(['status' => 'error']);
            }
        } elseif ($Kredit != null) { //-------------------------------------< di pengeluaran kas >
            try {
                $Kas = Kas::create([
                    'Faktur' => $Faktur,
                    'Tgl' => $Tgl,
                    'Rekening' => $RekeningKredit,
                    'Debet' => 0,
                    'Kredit' => $Kredit,
                    'Keterangan' => $keterangan,
                ]);
            } catch (\Throwable $th) {
                return response()->json(['status' => 'error']);
            }
            try {
                $Kas = Kas::create([
                    'Faktur' => $Faktur,
                    'Tgl' => $Tgl,
                    'Rekening' => $RekeningDebet,
                    'Debet' => $Kredit,
                    'Kredit' => 0,
                    'Keterangan' => $keterangan,
                ]);
                GetterSetter::setLastFaktur('KK');
            } catch (\Throwable $th) {
                return response()->json(['status' => 'error']);
            }
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $ID)
    {
        $Kas = Kas::where('ID', $ID)->update([
            'Faktur' => $request->Faktur,
            'Tgl' => $request->Tgl,
            'Rekening' => $request->Rekening,
            'Debet' => $request->Debet,
            'Kredit' => $request->Kredit,
            'Keterangan' => $request->Keterangan,
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $Faktur = $request->Faktur;
            $Kas = Kas::where('Faktur', $Faktur);
            $Kas->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
