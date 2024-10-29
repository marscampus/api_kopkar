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
 * Created on Thu Dec 07 2023 - 14:46:52
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\simpananberjangka;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use App\Models\simpananberjangka\Deposito;
use App\Models\simpananberjangka\DepositoSukuBunga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterRekeningSimpananBerjangkaController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            if ($nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $dStartDate = $vaRequestData['TglAwal'];
            $dEndDate = $vaRequestData['TglAkhir'];
            $vaData = DB::table('deposito as d')
                ->select(
                    'd.Rekening',
                    'r.Nama',
                    'r.Alamat',
                    'd.Tgl',
                    'd.JthTmp',
                    'd.Kode',
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry');
            if ($dStartDate == null || $dEndDate == null || empty($dStartDate) || empty($dEndDate)) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            $vaData->whereBetween('d.Tgl', [$dStartDate, $dEndDate]);
            $vaData->whereBetween('d.AO', [$vaRequestData['AOAwal'], $vaRequestData['AOAkhir']]);
            $vaData->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            );
            $vaData->orderByDesc('d.Tgl');
            if (!empty($request->filters)) {
                foreach ($request->filters as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData->orderByDesc('d.Tgl');
            $vaData = $vaData->get();
            $vaResult = [
                'data' => $vaData,
                'total_data' => count($vaData)
            ];
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Register Rekening Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function allData(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nLimit = 100000000000000;
            $vaData = DB::table('deposito as d')
                ->select(
                    'd.Rekening',
                    'r.Nama',
                    'r.Alamat',
                    'd.Tgl',
                    'd.JthTmp',
                    'd.Kode'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->orderByDesc('d.Tgl');
            if (!empty($request->filters)) {
                foreach ($request->filters as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->paginate($nLimit);
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'allData', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'allData', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaRetVal);
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

            Func::writeLog('Register Rekening Simpanan Berjangka', 'allData', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getAnggota(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Kode'] == null || empty($vaRequestData['Kode'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getAnggota', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $kode = $vaRequestData['Kode'];
            $vaData = DB::table('registernasabah as r')
                ->select(
                    'r.Nama',
                    'r.Alamat',
                    'r.Telepon',
                    'r.Pekerjaan',
                    'p.Keterangan as NamaPekerjaan',
                    'r.Keterkaitan',
                    'k.Keterangan as NamaKeterkaitan',
                    'r.KTP',
                    'r.TempatLahir',
                    'r.TglLahir',
                    'r.KodePos',
                    'r.KodyaKeterangan',
                    'r.Agama'
                )
                ->leftJoin('pekerjaan as p', 'p.Kode', '=', 'r.Pekerjaan')
                ->leftJoin('keterkaitan as k', 'k.Kode', '=', 'r.keterkaitan')
                ->where('r.Kode', '=', $kode)
                ->first();
            if ($vaData) {
                $keterikatan = "2";
                $next = "1";
                $vaResult = [
                    'Kode' => $kode,
                    'Nama' => $vaData->Nama ? $vaData->Nama : '',
                    'Alamat' => $vaData->Alamat ? $vaData->Alamat : '',
                    'KTP' => $vaData->KTP ? $vaData->KTP : '',
                    'TempatLahir' => $vaData->TempatLahir ? $vaData->TempatLahir : '',
                    'TglLahir' => $vaData->TglLahir ? $vaData->TglLahir : '',
                    'KodePos' => $vaData->KodePos ? $vaData->KodePos : '',
                    'KodyaKeterangan' => $vaData->KodyaKeterangan ? $vaData->KodyaKeterangan : '',
                    'Agama' => $vaData->Agama ? $vaData->Agama : '',
                    'Tanggal' => GetterSetter::getTglTransaksi(),
                    'Telepon' => $vaData->Telepon ? $vaData->Telepon : '',
                    'Pekerjaan' => $vaData->Pekerjaan ? $vaData->Pekerjaan : '',
                    'NamaPekerjaan' => $vaData->NamaPekerjaan ? $vaData->NamaPekerjaan : '',
                    'Keterikatan' => $keterikatan,
                    'Next' => $next
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getAnggota', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getAnggota', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
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
            Func::writeLog('Register Rekening Simpanan Berjangka', 'getAnggota', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getRekeningTabungan(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $cRekTabungan = $vaRequestData['RekTabungan'];
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $cRekTabungan == null || empty($cRekTabungan)) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getRekeningTabungan', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('tabungan as t')
                ->select(
                    't.Close',
                    'r.Nama'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->where('t.Rekening', '=', $cRekTabungan)
                ->first();
            if ($vaData) {
                if ($vaData->Close == '1') {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "REKENING SIMPANAN TELAH DITUTUP!'"
                    ];
                    Func::writeLog('Register Rekening Simpanan Berjangka', 'getRekeningTabungan', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Rekening Simpanan Telah Ditutup!'
                    ]);
                }
                $nSaldoAkhir = PerhitunganTabungan::getSaldoTabungan($cRekTabungan, GetterSetter::getTglTransaksi());
                $vaResult = [
                    'NamaTabungan' => $vaData->Nama,
                    'SaldoTabungan' => $nSaldoAkhir
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getRekeningTabungan', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "Rekening Simpanan Tidak Ditemukan!"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'getRekeningTabungan', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening Tabungan Tidak Ditemukan!'
                ]);
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
            Func::writeLog('Register Rekening Simpanan Berjangka', 'getRekeningTabungan', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
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
            if ($nReqCount < 13) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make($request->all(), [
                'CaraPerhitungan' => 'required|max:1',
                'RekeningDeposito' => 'required|max:15',
                'Tgl' => 'required|date',
                'JthTmp' => 'required|date',
                'NoAnggota' => 'required|max:12',
                'AO' => 'required|max:10',
                'CairBunga' => 'required|max:50',
                'Aro' => 'required|max:1',
                'CaraPerpanjangan' => 'required|max:1',
                'SukuBunga' => 'required|max:10',
                'Golongan' => 'required|max:6',
                'BungaDibayar' => 'required|max:1',
                'Nominal' => 'required|max:16'
            ]);
            if ($vaValidator->fails()) {
                $vaRetVal = [
                    "status" => "99",
                    "message" =>  $vaValidator->errors()
                ];
                Func::writeLog('Register Rekening Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            $cRegAnggota = $vaRequestData['NoAnggota'];
            $vaData = DB::table('registernasabah')
                ->select('Nama')
                ->where('Kode', '=', $cRegAnggota)
                ->first();
            if ($vaData) {
                $cNama = $vaData->Nama;
            }
            $cKodeGol = $vaRequestData['Golongan'];
            $vaData2 = DB::table('golongandeposito')
                ->select('Bunga')
                ->where('Kode', $cKodeGol)
                ->first();
            if ($vaData2) {
                $nSukuBunga = $vaData2->Bunga;
            }
            $vaArrayDeposito = [
                'CaraPerhitungan' => $vaRequestData['CaraPerhitungan'] ?? '',
                'Rekening' => $vaRequestData['RekeningDeposito'] ?? '',
                'Tgl' => $vaRequestData['Tgl'] ?? '',
                'Jthtmp' => $vaRequestData['JthTmp'] ?? '',
                'NoBilyet' => $vaRequestData['NoBilyet'] ?? '',
                'Kode' => $vaRequestData['NoAnggota'] ?? '',
                'AO' => $vaRequestData['AO'] ?? '',
                'CairBunga' => $vaRequestData['CairBunga'] ?? '',
                'RekeningTabungan' => $vaRequestData['RekeningTabungan'] ?? '',
                'GolonganDeposan' => '',
                'ARO' => $vaRequestData['Aro'] ?? '',
                'CaraPerpanjangan' => $vaRequestData['CaraPerpanjangan'] ?? '',
                'SukuBunga' => $nSukuBunga ?? '',
                'Keterkaitan' => '',
                'GolonganDeposito' => $cKodeGol ?? '',
                'DateTime' => Carbon::now(),
                'RekeningLama' => $vaRequestData['RekeningLama'] ?? '',
                'NamaNasabah' => $cNama ?? '',
                'AhliWaris' => $vaRequestData['AhliWaris'] ?? '',
                'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                'BungaDibayar' => $vaRequestData['BungaDibayar'] ?? '',
                "TempNominal" => $vaRequestData['Nominal'] ?? '',
                "GolonganDeposan" => 875
            ];
            Deposito::create($vaArrayDeposito);
            $vaArraySukuBunga = [
                'TglTransaksi' => GetterSetter::getTglTransaksi(),
                'tgl' => $vaRequestData['Tgl'],
                'JTHTMP' => $vaRequestData['JthTmp'],
                'Rekening' => $vaRequestData['RekeningDeposito'],
                'Sukubunga' => $nSukuBunga,
                'BungaLama' => 0,
                'Keterangan' => '',
                'UserName' => $cUser,
                'DateTime' => Carbon::now()
            ];
            DepositoSukuBunga::create($vaArraySukuBunga);
            GetterSetter::setRekening('2');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Register Rekening Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Register Rekening Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function delete(Request $request)
    {
        try {
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
                Func::writeLog('Register Rekening Simpanan Berjangka', 'delete', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = Deposito::findOrFail($vaRequestData['Rekening']);
            $vaData->delete();
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Register Rekening Simpanan Berjangka', 'delete', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Register Rekening Simpanan Berjangka', 'delete', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
