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
 * Created on Mon Dec 25 2023 - 17:05:41
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\Teller;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\fun\MutasiTabungan;
use App\Models\fun\Username;
use App\Models\master\RegisterNasabah;
use App\Models\teller\MutasiAnggota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiAnggotaController extends Controller
{
    public function getDataAnggota(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 1 || $nReqCount > 1 || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Anggota', 'getDataAnggota', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $vaData = DB::table('registernasabah')
                ->select('StatusAktif')
                ->where('Kode', '=', $cRekening)
                ->first();
            if ($vaData) {
                $cStatusAktif = $vaData->StatusAktif;
                if ($cStatusAktif == 0) {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "ANGGOTA TELAH KELUAR"
                    ];
                    Func::writeLog('Mutasi Anggota', 'getDataAnggota', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return  response()->json(
                        [
                            'status' => 'error',
                            'message' => 'ANGGOTA TELAH KELUAR'
                        ]
                    );
                } else {
                    $dTglTransaksi = GetterSetter::getTglTransaksi();
                    $vaData2 = DB::table('registernasabah as r')
                        ->selectRaw(
                            'r.Nama,
                            COALESCE(SUM(CASE WHEN m.Tgl <= ? THEN m.KreditPokok - m.DebetPokok ELSE 0 END), 0) AS SimpananPokok,
                            COALESCE(SUM(CASE WHEN m.Tgl <= ? THEN m.KreditWajib - m.DebetWajib ELSE 0 END), 0) AS SimpananWajib',
                            [$dTglTransaksi, $dTglTransaksi]
                        )
                        ->leftJoin('mutasianggota as m', 'm.Kode', '=', 'r.Kode')
                        ->where('r.Kode', '=', $cRekening)
                        ->groupBy('r.Nama')
                        ->first();
                    if ($vaData2) {
                        $vaResult = [
                            "Faktur" => GetterSetter::getLastFaktur("MA", 6),
                            "SaldoAwalPokok" => $vaData2->SimpananPokok,
                            "SaldoAwalWajib" => $vaData2->SimpananWajib,
                            "Keterangan" => 'Mutasi Anggota ' . '[' . $cRekening . '] ' . $vaData2->Nama
                        ];
                        // JIKA REQUEST SUKSES
                        $vaRetVal = [
                            "status" => "00",
                            "message" => $vaResult
                        ];
                        Func::writeLog('Mutasi Anggota', 'getDataAnggota', $vaRequestData, $vaRetVal, $cUser);
                        return response()->json($vaResult);
                    } else {
                        $vaRetVal = [
                            "status" => "03",
                            "message" => "DATA TIDAK DITEMUKAN"
                        ];
                        Func::writeLog('Mutasi Anggota', 'getDataAnggota', $vaRequestData, $vaRetVal, $cUser);
                        // return response()->json($vaRetVal);
                        return response()->json(['status' => 'error']);
                    }
                }
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
            Func::writeLog('Mutasi Anggota', 'getDataAnggota', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataTable(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $nReqCount = count($vaRequestData);
        if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID"
            ];
            Func::writeLog('Mutasi Anggota', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $cRekening = $vaRequestData['Rekening'];
        $dTgl = GetterSetter::getTglTransaksi();
        $vaData = DB::table('registernasabah as r')
            ->select(
                DB::raw('ifnull(SUM(m.KreditPokok - m.DebetPokok),0) AS SimpananPokok'),
                DB::raw('ifnull(SUM(m.KreditWajib - m.DebetWajib),0) AS SimpananWajib'),
                DB::raw('0 AS Nominal')
            )
            ->leftJoin('mutasianggota as m', 'm.Kode', '=', 'r.Kode')
            ->where('r.Kode', '=', $cRekening)
            ->where('m.Tgl', '<=', $dTgl)
            ->first();
        if ($vaData) {
            $nSaldoPokok = $vaData->SimpananPokok;
            $nSaldoWajib = $vaData->SimpananWajib;
        }
        $vaData2 = DB::table('mutasianggota')
            ->select(
                'Faktur',
                'Tgl',
                'DebetPokok',
                'KreditPokok',
                'DebetWajib',
                'KreditWajib',
                'Keterangan',
                'UserName'
            )
            ->where('Kode', $cRekening)
            ->where('Tgl', '<=', $dTgl)
            ->orderByDesc('Tgl')
            ->limit(10)
            ->get();
        if (count($vaData2) > 0) {
            $vaResults = [];
            foreach ($vaData2 as $d) {
                $nDebetPokok = $d->DebetPokok;
                $nKreditPokok = $d->KreditPokok;
                $nDebetWajib = $d->DebetWajib;
                $nKreditWajib = $d->KreditWajib;

                $vaResult = [
                    "Faktur" => $d->Faktur,
                    "Tgl" => $d->Tgl,
                    "KreditPokok" => $nKreditPokok,
                    "DebetPokok" => $nDebetPokok,
                    "SaldoPokok" => $nSaldoPokok,
                    "KreditWajib" => $nKreditWajib,
                    "DebetWajib" => $nDebetWajib,
                    "SaldoWajib" => $nSaldoWajib,
                    "Keterangan" => $d->Keterangan,
                    "UserName" => $d->UserName
                ];
                $nSaldoPokok -= $nKreditPokok - $nDebetPokok;
                $nSaldoWajib -= $nKreditWajib - $nDebetWajib;
                $vaResults[] = $vaResult;
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Mutasi Anggota', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "DATA TIDAK DITEMUKAN"
            ];
            Func::writeLog('Mutasi Anggota', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
        }
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 8 || $nReqCount > 8) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = $vaRequestData['Tgl'];
            $nMutasiPokok = $vaRequestData['MutasiPokok'];
            $nMutasiWajib = $vaRequestData['MutasiWajib'];
            $cKas = $vaRequestData['Kas'];
            $cJenis = $vaRequestData['Jenis'];
            $cRekeningAkuntansi = $vaRequestData['RekeningAkuntansi'] ?? null;
            $cKeterangan = $vaRequestData['Keterangan'];
            $cFaktur = GetterSetter::getLastFaktur("MA", 6);
            if ($cJenis == 'D') {
                MutasiAnggota::where('Faktur', $cFaktur)->delete();
                BukuBesar::where('Faktur', $cFaktur)->delete();
                MutasiTabungan::where('Faktur', $cFaktur)->delete();
                Upd::UpdMutasiAnggota($cFaktur, $dTgl, $cRekening, $cKeterangan, $cJenis, $nMutasiPokok, $nMutasiWajib, true, '', $cKas, $cRekeningAkuntansi, $cUser);
                $user = 'ARADHEA'; // GET CONFIG
                $data = Username::where('UserName', 'LIKE', $user . '%')->first();
                if ($data) {
                    $usernameAcc = $data->UserNameAcc;
                }
                GetterSetter::setLastFaktur("MA");
            } else {
                Upd::updMutasiAnggota($cFaktur, $dTgl, $cRekening, $cKeterangan, $cJenis, $nMutasiPokok, $nMutasiWajib, true, '', $cKas, $cRekeningAkuntansi, $cUser);
                GetterSetter::setLastFaktur("MA");
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            GetterSetter::setLastFaktur("MA");
            Func::writeLog('Mutasi Anggota', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Mutasi Anggota', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function cetakValidasi(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 2 || $nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Anggota', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $cFaktur = $vaRequestData['Faktur'];
            $vaData = DB::table('mutasianggota as ma')
                ->select(
                    'ma.DK',
                    'ma.Jumlah',
                    'ma.UserName',
                    'ma.Keterangan',
                    'ma.Kode',
                    'r.Nama',
                    'ma.Faktur',
                    'ma.Tgl',
                    'r.Alamat',
                    'ma.KreditPokok',
                    'ma.KreditWajib',
                    'ma.DebetPokok',
                    'ma.DebetWajib'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'ma.Kode')
                ->where('ma.Faktur', '=', $cFaktur)
                ->where('ma.Kode', '=', $cRekening)
                ->first();
            $vaResult = [];
            if ($vaData) {
                $cKet = $vaData->Keterangan . " DB";
                if ($vaData->DK == "K") {
                    $cKet = $vaData->Keterangan . " CR";
                }
                $vaResult = [
                    'Faktur' => $cFaktur,
                    'User' => $vaData->UserName,
                    'Kode' => $vaData->Kode,
                    'Nama' => $vaData->Nama,
                    'Keterangan' => $cKet,
                    'Jumlah' => $vaData->Jumlah,
                    'Tgl' => $vaData->Tgl,
                    'SimpananPokok' => $vaData->KreditPokok,
                    'SimpananWajib' => $vaData->KreditWajib,
                    'SimpananDPokok' => $vaData->DebetPokok,
                    'SimpananDWajib' => $vaData->DebetWajib,
                    'DK' => $vaData->DK,
                    'Terbilang' => Func::Terbilang(round(Func::String2Number($vaData->Jumlah), 0))
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Anggota', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
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

            Func::writeLog('Mutasi Anggota', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
