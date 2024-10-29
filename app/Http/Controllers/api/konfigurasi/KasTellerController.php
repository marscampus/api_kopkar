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
 * Created on Wed Jan 31 2024 - 07:29:10
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\konfigurasi;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Cabang;
use App\Models\fun\Config;
use App\Models\fun\Username;
use App\Models\fun\UsernameKantorKas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KasTellerController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTgl = GetterSetter::getTglTransaksi();
            $nLimit = 10;
            $vaData = DB::table('username as u')
                ->select(
                    'ID',
                    'UserName',
                    'FullName',
                    DB::raw('format(Plafond,2) as MaxPlafond'),
                    DB::raw('format(PlafondKredit,2) as MaxPlafondKredit'),
                    'PortPrinter',
                    DB::raw("(select kasteller from username_kantorkas where username = u.username and tgl <= '$dTgl' order by tgl desc limit 1) as KasTeller"),
                    DB::raw("(select tgl from username_kantorkas where username = u.username and tgl <= '$dTgl' order by tgl desc limit 1) as Tgl"),
                    DB::raw("(select cabang from username_kantorkas where username = u.username and tgl <= '$dTgl' order by tgl desc limit 1) as Cabang"),
                    'Aktif',
                    DB::raw("(select Gabungan from username_kantorkas where username = u.username and tgl <= '$dTgl' order by tgl desc limit 1) as Gabungan"),
                    'UserNameAcc'
                )
                ->where('Aktif', '<>', '0')
                ->paginate($nLimit);
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Kas Teller', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
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

            Func::writeLog('Kas Teller', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataUsername(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $dTgl = GetterSetter::getTglTransaksi();
            $cUser = $vaRequestData['auth']['name'];
            $cId = $vaRequestData['ID'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['ID'] == null || empty($vaRequestData['ID'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Kas Teller', 'getDataUsername', $vaRequestData, $vaRetVal, $cUser);
                //return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('username as u')
                ->select(
                    'u.UserName',
                    'u.FullName',
                    'u.Plafond',
                    'u.PlafondKredit',
                    'u.Kas',
                    'u.Tabungan',
                    'u.Deposito',
                    'u.Kredit',
                    'u.Akuntansi',
                    'u.PortPrinter',
                    'u.CabangInduk',
                    DB::raw("(SELECT IFNULL(kasteller, u.kasteller) FROM username_kantorkas WHERE username = u.username AND tgl <= '$dTgl' ORDER BY tgl DESC LIMIT 1) AS KasTeller"),
                    DB::raw("(SELECT tgl FROM username_kantorkas WHERE username = u.username AND tgl <= '$dTgl' ORDER BY tgl DESC LIMIT 1) AS Tgl"),
                    DB::raw("(SELECT cabang FROM username_kantorkas WHERE username = u.username AND tgl <= '$dTgl' ORDER BY tgl DESC LIMIT 1) AS Cabang"),
                    'u.Aktif',
                    DB::raw("(SELECT IFNULL(gabungan, u.gabungan) FROM username_kantorkas WHERE username = u.username AND tgl <= '$dTgl' ORDER BY tgl DESC LIMIT 1) AS Gabungan"),
                    DB::raw("(SELECT IFNULL(unit, u.unit) FROM username_kantorkas WHERE username = u.username AND tgl <= '$dTgl' ORDER BY tgl DESC LIMIT 1) AS Unit"),
                    'u.UserNameAcc'
                )

                ->where('u.ID', '=', $cId)
                ->first();
            if ($vaData) {
                $cKasTeller = $vaData->KasTeller;
                $cCabang = $vaData->Cabang;
                $cCabangInduk = $vaData->CabangInduk;
                $cUserNameAcc = $vaData->UserNameAcc;
                $cketUserNameAcc = "";
                $vaData2 = DB::table('username')
                    ->select('FullName')
                    ->where('UserName', '=', $cUserNameAcc)
                    ->first();
                if ($vaData2) {
                    $cketUserNameAcc = $vaData2->FullName;
                }
                $vaResult = [
                    'KasTeller' => $cKasTeller,
                    'KetKasTeller' => GetterSetter::getKeterangan($cKasTeller, 'Keterangan', 'rekening'),
                    'Cabang' => $cCabang,
                    'KetCabang' => GetterSetter::getKeterangan($cCabang, 'Keterangan', 'cabang'),
                    'CabangInduk' => $cCabangInduk,
                    'KetCabangInduk' => GetterSetter::getKeterangan($cCabangInduk, 'Keterangan', 'cabang'),
                    'KantorUnit' => $vaData->Unit,
                    'Gabungan' => $vaData->Gabungan,
                    'MaksPenarikan' => $vaData->Plafond,
                    'MaksPencairanPinjaman' => $vaData->PlafondKredit,
                    'UserNameAcc' => $cUserNameAcc,
                    'KetUserNameAcc' => $cketUserNameAcc
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Kas Teller', 'getDataUsername', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Kas Teller', 'getDataUsername', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function update(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTgl = GetterSetter::getTglTransaksi();
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 9 || $vaRequestData < 9) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Kas Teller', 'update', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make($request->all(), [
                'UserName' => 'required|max:20',
                'Cabang' => 'max:4',
                'CabangInduk' => 'max:4',
                'KasTeller' => 'max:20',
                'Gabungan' => 'max:1',
                'UserNameAcc' => 'max:20',
                'Unit' => 'max:1',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            ]);
            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Kas Teller', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }
            $cUserName = $vaRequestData['UserName'];
            $vaData = DB::table('username')
                ->where('UserName', '=', $cUserName)
                ->exists();
            if ($vaData) {
                $vaArray = [
                    'Plafond' => $vaRequestData['MaksPenarikan'],
                    'PlafondKredit' => $vaRequestData['MaksPencairanPinjaman'],
                    'Cabang' => $vaRequestData['Cabang'],
                    'CabangInduk' => $vaRequestData['CabangInduk'],
                    'KasTeller' => $vaRequestData['KasTeller'],
                    'Gabungan' => $vaRequestData['Gabungan'],
                    'UserNameAcc' => $vaRequestData['UserNameAcc'],
                    'Unit' => $vaRequestData['KantorUnit']
                ];
                Username::where('UserName', '=', $cUserName)->update($vaArray);
            }

            $vaData2  = DB::table('username_kantorkas')
                ->where('UserName', '=', $cUserName)
                ->where('Tgl', '=', $dTgl)
                ->exists();
            if ($vaData2) {
                $vaArray = [
                    'Cabang' => $vaRequestData['Cabang'],
                    'KasTeller' => $vaRequestData['KasTeller'],
                    'Gabungan' => $vaRequestData['Gabungan'],
                    'Unit' => $vaRequestData['KantorUnit'],
                    'DateTime' => Carbon::now()
                ];
                UsernameKantorKas::where('UserName', '=', $cUserName)
                    ->Where('Tgl', '=', $dTgl)->update($vaArray);
            } else {
                $vaArray = [
                    'UserName' => $cUserName,
                    'Tgl' => $dTgl,
                    'Cabang' => $vaRequestData['Cabang'],
                    'KasTeller' => $vaRequestData['KasTeller'],
                    'Gabungan' => $vaRequestData['Gabungan'],
                    'Unit' => $vaRequestData['KantorUnit'],
                    'DateTime' => Carbon::now()
                ];
                UsernameKantorKas::create($vaArray);
            }

            GetterSetter::setDBConfig('msKodeCabang', $vaRequestData['Cabang']);

            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Kas Teller', 'update', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Kas Teller', 'update', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
