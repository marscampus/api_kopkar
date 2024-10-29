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
 * Created on Wed Mar 13 2024 - 04:10:10
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\jurnal;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\jurnal\Jurnal;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;


class JurnalController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.ID',
                    'j.Faktur',
                    'j.Rekening',
                    'j.Tgl',
                    'r.Keterangan as NamaPerkiraan',
                    'j.Keterangan',
                    'j.Debet',
                    'j.Kredit',
                    'u.UserName'
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'j.Rekening')
                ->leftJoin('urutfaktur as u', 'u.Faktur', '=', 'j.Faktur');
            if ($vaRequestData['TglAwal'] == null || $vaRequestData['TglAkhir'] == null || empty($vaRequestData['TglAwal']) || empty($vaRequestData['TglAkhir'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Jurnal', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData->whereBetween('j.Tgl', [$vaRequestData['TglAwal'], $vaRequestData['TglAkhir']]);
            $vaData->where('j.Faktur', 'LIKE', 'JR%');
            $vaData->orderBy('j.Faktur');
            $vaData = $vaData->get();
            $nRow = 0;
            $nTotalDebet = 0;
            $nTotalKredit = 0;
            $vaArray = [];
            $vaTotal = [];
            foreach ($vaData as $d) {
                $nRow++;
                $vaArray[] = [
                    'No' => $nRow,
                    'Faktur' => $d->Faktur,
                    'Tgl' => $d->Tgl,
                    'Rekening' => $d->Rekening,
                    'KetPerkiraan' => $d->NamaPerkiraan,
                    'Keterangan' => $d->Keterangan,
                    'Debet' => $d->Debet,
                    'Kredit' => $d->Kredit,
                    'Username' => $d->UserName
                ];
                $nTotalDebet += $d->Debet;
                $nTotalKredit += $d->Kredit;
                $vaTotal = [
                    'TotalDebet' => $nTotalDebet,
                    'TotalKredit' => $nTotalKredit
                ];
            }

            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaData),
                'totals' => $vaTotal
            ];

            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Jurnal', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
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

            Func::writeLog('Jurnal', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function data1(Request $request)
    {

        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $rekeningJurnal = isset($request->rekeningJurnal) ? $request->rekeningJurnal : '1.100.01.01'; //--> yang dimaksud adalah rekening usernya kalau fungsinya sudah ada
        $userName = isset($request->userName) ? $request->userName : '%%'; //--> yang dimaksud adalah rekening usernya kalau fungsinya sudah ada
        $userNameLike = isset($request->userName) ? '=' : 'LIKE'; //--> yang dimaksud adalah rekening usernya kalau fungsinya sudah ada

        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $Jurnal = Jurnal::whereBetween('Tgl', [$tglAwal, $tglAkhir])
                    ->where($k, "LIKE", '%' . $v . '%')
                    ->orderBy('Faktur')
                    ->paginate(20);
                return response()->json($Jurnal);
            }
        }
        $Jurnal = Jurnal::whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->where('Faktur', "LIKE", 'JR%')
            ->orderBy('Faktur')
            ->paginate(20);

        return response()->json($Jurnal);
    }

    function jurnalUmum(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cFaktur = $vaRequestData['Faktur'];
            $cUsername = $vaRequestData['Username'];
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $cWhereFaktur = '';
            $cWhereUserName = '';
            $cWhereFakturIn = '';

            // Cek dan buat kondisi WHERE untuk faktur
            if (!empty($cFaktur)) {
                $cWhereFaktur = "b.Faktur = '$cFaktur'";
            }

            // Cek dan buat kondisi WHERE untuk username
            if (!empty($cUsername)) {
                $cWhereUserName = "b.UserName = '$cUsername'";
            }

            // Cek dan buat kondisi WHERE untuk faktur dalam subquery
            if (!empty($cRekeningKas)) {
                // Ambil faktur dari tabel bukubesar dengan menggunakan Eloquent ORM
                $fakturs = BukuBesar::select('faktur')
                    ->where('rekening', 'like', $cRekeningKas . '%')
                    ->groupBy('faktur')
                    ->pluck('faktur')
                    ->toArray();

                // Bangun string untuk klausul WHERE
                $cWhereFakturIn = !empty($fakturs) ? "b.Faktur IN ('" . implode("','", $fakturs) . "')" : "b.Faktur IN ('')";
            }

            $nTotalDebet = 0;
            $nTotalKredit = 0;
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Jurnal', 'jurnalUmum', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData
                = DB::table('bukubesar as b')
                ->select(
                    'b.Id',
                    'b.Faktur',
                    'b.Rekening',
                    'b.Tgl',
                    'r.Keterangan as NamaPerkiraan',
                    'b.Keterangan',
                    'b.Debet',
                    'b.Kredit',
                    'b.UserName',
                    'b.Cabang'
                )
                ->leftJoin('rekening as r', 'r.kode', '=', 'b.rekening')
                ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
                ->where('b.Tgl', '>=', $dTglAwal)
                ->where('b.Tgl', '<=', $dTglAkhir)
                ->where('b.rekening', '<', '6')
                ->when(
                    $cWhereFaktur !== '',
                    function ($query) use ($cWhereFaktur) {
                        $query->whereRaw($cWhereFaktur);
                    }
                )
                ->when(
                    $cWhereUserName !== '',
                    function ($query) use ($cWhereUserName) {
                        $query->whereRaw($cWhereUserName);
                    }
                )
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->orderBy('b.Tgl')
                ->orderBy('b.datetime')
                ->orderBy('b.Faktur')
                ->orderBy('b.Cabang')
                ->orderBy('b.Kredit')
                ->orderBy('b.Debet')
                ->orderBy('b.Id')
                ->orderByDesc('b.Tgl')
                ->orderBy('b.Debet')
                ->orderBy('b.Id')
                ->orderByDesc('b.Kredit');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->get();
            $vaTotal = [
                'TotalDebet' => 0,
                'TotalKredit' => 0
            ];
            $vaResult = [];
            // JIKA REQUEST SUKSES
            foreach ($vaData as $d) {
                $vaResult[] = [
                    'Faktur' => $d->Faktur,
                    'Tgl' => $d->Tgl,
                    'Cabang' => $d->Cabang,
                    'Rekening' => $d->Rekening,
                    'NamaPerkiraan' => $d->NamaPerkiraan,
                    'Keterangan' => $d->Keterangan,
                    'Debet' => $d->Debet,
                    'Kredit' => $d->Kredit,
                    'UserName' => $d->UserName
                ];
                $nTotalDebet += $d->Debet;
                $nTotalKredit += $d->Kredit;
                $vaTotal = [
                    'TotalDebet' => ($nTotalDebet <= 0) ? 0 : $nTotalDebet,
                    'TotalKredit' => ($nTotalDebet <= 0) ? 0 : $nTotalKredit,
                ];
            }

            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'total' => $vaTotal
            ];
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Jurnal', 'jurnalUmum', $vaRequestData, $vaRetVal, $cUser);
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

            // Func::writeLog('Jurnal', 'jurnalUmum', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function bukuBesarDetail(Request $request)
    {
        function customNumberFormat($value)
        {
            if ($value == 0) {
                return "";
            }
            if ($value < 0) {
                // Ubah nilai negatif menjadi positif dan tambahkan tanda kurung
                return '(' . number_format(abs($value), 2, '.', ',') . ')';
            } else {
                return number_format($value, 2, '.', ',');
            }
        }
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $rekeningAwal = isset($request->RekeningAwal) ? $request->RekeningAwal : '';     // Min rekening value as per the SQL query
        $rekeningAkhir = isset($request->RekeningAkhir) ? $request->RekeningAkhir : $rekeningAwal;     // Max rekening value as per the SQL query
        $cJenisGabungan = $request['JenisGabungan'];
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $request['Cabang'];
        }
        $firstQueryResult = DB::table('rekening as r')
            ->select('r.Kode as Rekening', 'r.Keterangan as NamaPerkiraan', DB::raw('IFNULL(SUM(b.Debet-b.Kredit),0) as SaldoAwal'))
            ->leftJoin('bukubesar as b', function ($join) use ($tglAwal) {
                $join->on('r.Kode', '=', 'b.Rekening')
                    ->where('b.Tgl', '<', $tglAwal);
            })
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.Cabang')
            ->whereBetween('r.Kode', [$rekeningAwal, $rekeningAkhir])
            ->whereIn('r.Kode', function ($subquery) {
                $subquery->select(DB::raw('distinct(rekening)'))
                    ->from('bukubesar');
            })
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->groupBy('r.Kode', 'r.Keterangan')
            ->orderBy('r.Kode')
            ->get();

        // Inisialisasi array untuk menyimpan hasil akhir
        $finalResult = [];

        foreach ($firstQueryResult as $row) {
            $totalDebet = 0;
            $totalKredit = 0;

            $rekening = $row->Rekening;
            $namaPerkiraan = $row->NamaPerkiraan;

            $saldoAwalJudul = $row->SaldoAwal;
            $saldoAwal = $row->SaldoAwal;

            $barisSaldoAwal = [
                "No" => "",
                "Faktur" => "",
                "Tgl" => "",
                "Keterangan" => "",
                "Debet" => "",
                "Kredit" => "",
                "Akhir" => "",
            ];

            $queryResult = DB::table('bukubesar as b')
                ->select('b.Rekening', 'b.Faktur', 'b.Tgl', 'b.Keterangan', 'r.kode', 'r.keterangan as NamaPerkiraan', 'b.Debet', 'b.Kredit', 'b.UserName')
                ->leftJoin('rekening as r', 'r.kode', '=', 'b.rekening')
                ->where('tgl', '>=', $tglAwal)
                ->where('tgl', '<=', $tglAkhir)
                ->where('r.Kode', $rekening)
                ->orderBy('b.Rekening')
                ->orderBy('Tgl')
                ->orderBy('Faktur')
                ->orderBy(DB::raw("Faktur like 'AA%'"), 'desc')
                ->orderBy(DB::raw("Faktur like 'km%'"), 'desc')
                ->orderBy(DB::raw("Faktur like 'ag%'"), 'desc')
                ->orderBy(DB::raw("Faktur like 'zz%'"), 'asc')
                ->get();

            foreach ($queryResult as $item) {
                $totalDebet += $item->Debet;
                $totalKredit += $item->Kredit;
            }

            $formattedResult = $queryResult->map(function ($item, $index) use (&$saldoAwal) {
                $akhir = $saldoAwal + $item->Debet - $item->Kredit;

                $saldoAwalFormatted = customNumberFormat($saldoAwal, 2, '.', ',');
                $debetFormatted = customNumberFormat($item->Debet, 2, '.', ',');
                $kreditFormatted = customNumberFormat($item->Kredit, 2, '.', ',');
                $akhirFormatted = customNumberFormat($akhir, 2, '.', ',');

                $row = [
                    "Rekening" =>  $item->Rekening,
                    "No" => $index + 1,
                    "Faktur" => $item->Faktur,
                    "Tgl" => date('d-m-Y', strtotime($item->Tgl)),
                    "Keterangan" => $item->Keterangan,
                    "Debet" => $item->Debet,
                    "Kredit" => $item->Kredit,
                    "Akhir" => $akhir,
                ];

                $saldoAwal = $akhir; // Perbarui saldo awal

                return $row;
            });

            $barisSaldoAkhir = [
                "Rekening" =>  $rekening,
                "No" => "",
                "Faktur" => "",
                "Tgl" => "",
                "Keterangan" => "Total",
                "Debet" => $totalDebet,
                "Kredit" => $totalKredit,
                "Akhir" => "",
            ];

            $finalResult[] = [
                "Rekening" =>  $rekening,
                "No" =>  $rekening,
                "Faktur" => $namaPerkiraan,
                "Tgl" => "",
                "Keterangan" => "Saldo Awal",
                "Debet" => "",
                "Kredit" => "",
                "Akhir" => $saldoAwalJudul,
            ];

            // Tambahkan hasil formattedResult ke $finalResult
            foreach ($formattedResult as $formattedRow) {
                $finalResult[] = $formattedRow;
            }

            $finalResult[] = $barisSaldoAkhir;
        }


        $result = [
            "data" => array_merge(
                $finalResult
            ),
        ];

        $filteredResult = [];
        $rekeningCount = []; // Menyimpan jumlah record untuk setiap rekening

        // Menghitung jumlah record untuk setiap rekening
        foreach ($result['data'] as $row) {
            $rekening = $row['Rekening'];
            if (!isset($rekeningCount[$rekening])) {
                $rekeningCount[$rekening] = 0;
            }
            $rekeningCount[$rekening]++;
        }

        // Memfilter hasil untuk kebalikan dari sebelumnya
        foreach ($result['data'] as $row) {
            $rekening = $row['Rekening'];
            $keterangan = $row['Keterangan'];

            // Hanya tampilkan jika rekening memiliki tidak 2 record atau tidak memiliki keterangan yang sesuai
            if ($rekeningCount[$rekening] !== 2 || ($keterangan !== "Saldo Awal" && $keterangan !== "Total")) {
                $filteredResult[] = $row;
            }
        }

        $result = [
            "data" => array_merge(
                $filteredResult
            )
        ];

        // Return the result as JSON
        return response()->json($result);

        // return $finalResult;
    }

    function bukuBesarTotal(Request $request)
    {
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $rekeningAwal = isset($request->RekeningAwal) ? $request->RekeningAwal : '1.101';     // Min rekening value as per the SQL query
        $rekeningAkhir = isset($request->RekeningAkhir) ? $request->RekeningAkhir : $rekeningAwal;     // Max rekening value as per the SQL query
        $cJenisGabungan = $request['JenisGabungan'];
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $request['Cabang'];
        }
        $query = DB::table('rekening as r')
            ->select('r.Kode as Rekening', 'r.Keterangan as NamaPerkiraan', DB::raw('IFNULL(SUM(b.Debet-b.Kredit),0) as SaldoAwal'))
            ->leftJoin('bukubesar as b', function ($join) use ($tglAwal) {
                $join->on('r.Kode', '=', 'b.Rekening')
                    ->where('b.Tgl', '<', $tglAwal);
            })
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.Cabang')
            ->whereBetween('r.Kode', [$rekeningAwal, $rekeningAkhir])
            ->whereIn('r.Kode', function ($subquery) {
                $subquery->select(DB::raw('distinct(rekening)'))
                    ->from('bukubesar');
            })
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->groupBy('r.Kode')
            ->orderBy('r.Kode')
            ->get();

        $query2 = DB::table('bukubesar as b')
            ->select('b.Rekening', 'b.Tgl', 'r.Keterangan')
            ->distinct()
            ->leftJoin('rekening as r', 'r.kode', '=', 'b.Rekening')
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.Cabang')
            ->whereBetween('b.Tgl', [$tglAwal, $tglAkhir])
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->whereBetween('r.Kode', [$rekeningAwal, $rekeningAkhir])
            ->orderBy('b.Rekening')
            ->get();


        $totalSaldoAwal = $query->sum('SaldoAwal');

        // Array untuk hasil akhir sesuai format yang diinginkan
        $resultArray = [];
        $totalDebet = 0;
        $totalKredit = 0;

        // Saldo Awal
        $resultArray[] = [
            "No" => "",
            "Tgl" => "",
            "Keterangan" => "Saldo Awal",
            "Debet" => "",
            "Kredit" => "",
            "Akhir" => $totalSaldoAwal
        ];

        // Iterate through transactions from the second query
        $index = 1;
        foreach ($query2 as $transaction) {
            $rekening = $transaction->Rekening;
            $tgl = $transaction->Tgl;
            $keterangan = $transaction->Keterangan;

            // Query to get debit and credit for the current rekening and date
            $debit = DB::table('bukubesar')
                ->where('Tgl', $tgl)
                ->where('Rekening', $rekening)
                ->sum('Debet');

            $kredit = DB::table('bukubesar')
                ->where('Tgl', $tgl)
                ->where('Rekening', $rekening)
                ->sum('Kredit');

            $totalDebet += $debit;
            $totalKredit += $kredit;

            // Calculate saldo akhir for the current transaction
            $saldoAkhir = $totalSaldoAwal + $totalDebet - $totalKredit;

            // Create the transaction entry in the desired format
            $resultArray[] = [
                "No" => $index,
                "Tgl" => date('d-m-Y', strtotime($tgl)),
                "Keterangan" => $keterangan . ' Tgl ' . date('d-m-Y', strtotime($tgl)),
                "Debet" => $debit,
                "Kredit" => $kredit,
                "Akhir" => $saldoAkhir
            ];

            $index++;
        }

        // Total
        $resultArray[] = [
            "No" => "",
            "Tgl" => "",
            "Keterangan" => "Total",
            "Debet" => $totalDebet,
            "Kredit" => $totalKredit,
            "Akhir" => ""
        ];


        $result = [
            "data" => array_merge(
                $resultArray
            ),
        ];
        // Return the result as JSON
        return response()->json($result);
    }

    function rekapJurnal(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $nTotalDebet = 0;
            $nTotalKredit = 0;
            $nTotalSaldoAwal = 0;
            $nTotalSaldoAkhir = 0;
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $vaData = DB::table('bukubesar as b')
                ->select(
                    'b.Rekening',
                    'b.Tgl',
                    'r.Keterangan as NamaPerkiraan',
                    DB::raw('IFNULL(SUM(b.Debet),0) as Debet'),
                    DB::raw('IFNULL(SUM(b.Kredit),0) as Kredit')
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'b.Rekening')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'b.Cabang')
                ->whereBetween('b.Tgl', [$dTglAwal, $dTglAkhir])
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy('b.Rekening');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->get();
            $vaResult = [];

            $vaTotal = [
                'TotalSaldoAwal' => 0,
                'TotalDebet' => 0,
                'TotalKredit' => 0,
                'TotalSaldoAkhir' => 0
            ];
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $dTgl = $d->Tgl;
                $cKeterangan = $d->NamaPerkiraan;
                $dTgl = date('Y-m-d', strtotime($dTgl) - 24 * 60 * 60);
                $nSaldoAwal = GetterSetter::getSaldoAwal($dTgl, $cRekening, '', false, $cCabang, true, false, $cJenisGabungan);
                $nDebet = $d->Debet;
                $nKredit = $d->Kredit;
                $nMutasi = $nDebet - $nKredit;
                if (substr($cRekening, 0, 1) == "1" || substr($cRekening, 0, 1) == "5" || substr($cRekening, 0, 1) == "6") {
                    $nSaldoAwal = $nSaldoAwal;
                    $nSaldoAkhir = ($nSaldoAwal + $nDebet - $nKredit);
                } else {
                    $nSaldoAwal = $nSaldoAwal;
                    $nSaldoAkhir = ($nSaldoAwal - $nDebet + $nKredit);
                }
                $vaResult[] = [
                    'Rekening' => $cRekening,
                    'Keterangan' => Func::replaceKarakterKhusus($cKeterangan),
                    'Awal' => $nSaldoAwal ?? 0,
                    'Debet' => $nDebet ?? 0,
                    'Kredit' => $nKredit ?? 0,
                    'Akhir' => $nSaldoAkhir ?? 0
                ];
                $nTotalSaldoAwal += $nSaldoAwal;
                $nTotalDebet += $nDebet;
                $nTotalKredit += $nKredit;
                if (substr($cRekening, 0, 1) == "1" || substr($cRekening, 0, 1) == "5") {
                    $nTotalSaldoAkhir += ($nSaldoAwal + $nDebet) - $nKredit;
                } else {
                    $nTotalSaldoAkhir += ($nSaldoAwal - $nDebet) + $nKredit;
                }

                $vaTotal = [
                    'TotalSaldoAwal' => $nTotalSaldoAwal ?? 0,
                    'TotalDebet' => $nTotalDebet ?? 0,
                    'TotalKredit' => $nTotalKredit ?? 0,
                    'TotalSaldoAkhir' => $nTotalSaldoAkhir ?? 0
                ];
            }
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'total' => $vaTotal
            ];
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Jurnal', 'rekapJurnal', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Jurnal', 'rekapJurnal', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
