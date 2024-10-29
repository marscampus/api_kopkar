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
 * Created on Thu Apr 04 2024 - 07:36:13
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\pemindahbukuan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\jurnal\Jurnal;
use App\Models\pemindahbukuan\Pemindahbukuan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PemindahbukuanController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $nLimit = 10;
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pemindahbukuan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cRekeningPemindahbukuaan = isset($request->rekeningPemindahbukuan) ? $request->rekeningPemindahbukuan : '1.100.01.01'; //--> yang dimaksud adalah rekening usernya kalau fungsinya sudah ada
            $cUserNameLike = isset($cUser) ? '=' : 'LIKE';
            $vaData = DB::table('pemindahbukuan')
                ->select(
                    'Faktur',
                    'Tgl',
                    'DK',
                    'Keterangan',
                    'Pokok',
                    'Bunga',
                    'Denda',
                    'SimpananWajib',
                    'JumlahD',
                    'JumlahK',
                    'UserName',
                    'KRRA',
                    'CabangEntry'
                )
                ->selectRaw(' CASE WHEN RekeningNasabah IS NOT NULL AND RekeningNasabah != "" THEN RekeningNasabah ELSE RekeningJurnal END AS Rekening ');
            if ($dTglAwal == null || $dTglAkhir == null || empty($dTglAwal) || empty($dTglAkhir)) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pemindahbukuan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            $vaData->whereBetween('Tgl', [$dTglAwal, $dTglAkhir]);
            $vaData->orderByDesc('Faktur');

            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            if ($vaRequestData['page'] == null) {
                // Mengambil semua data tanpa paginate
                $vaData = $vaData->get();
            } else {
                // Menggunakan paginate dengan jumlah data per halaman yang diinginkan
                $vaData = $vaData->paginate($nLimit);
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Pemindahbukuaan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Pemindahbukuaan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return  response()->json(
                    [
                        'status' => 'error',
                        'message' => 'DATA TIDAK DITEMUKAN'

                    ]
                );
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

            Func::writeLog('Pemindahbukuaan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        try {
            $vaRequestData = $request->json()->all();
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);

            $vaValidator = Validator::make($vaRequestData, [
                '*.Faktur' => 'required|max:20',
                '*.Tgl' => 'date',
                '*.DK' => 'max:1',
                '*.RekeningJurnal' => 'max:30',
                '*.RekeningNasabah' => 'max:20',
                '*.JumlahD' => 'max:16',
                '*.JumlahK' => 'max:16',
                '*.Pokok' => 'max:16',
                '*.Bunga' => 'max:16',
                '*.Denda' => 'max:16',
                '*.UserName' => 'max:25',
                '*.CabangEntry' => 'max:3',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
            ]);

            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Pemindahbukuan', 'store', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'error', 'message' => $vaErrorMsgs]);
            }

            $vaArrays = [];
            foreach ($vaRequestData as $item) {
                $vaArrays[] = [
                    'Faktur' => $item['Faktur'],
                    'Tgl' => $item['Tgl'],
                    'DK' => $item['DK'],
                    'RekeningJurnal' => $item['RekeningJurnal'],
                    'RekeningNasabah' => $item['RekeningNasabah'],
                    'Keterangan' => $item['Keterangan'],
                    'JumlahD' => $item['JumlahD'],
                    'JumlahK' => $item['JumlahK'],
                    'Pokok' => $item['Pokok'],
                    'Bunga' => $item['Bunga'],
                    'Denda' => $item['Denda'],
                    'UserName' => $cUser,
                    'CabangEntry' => $item['CabangEntry'],
                    "DateTime" => Carbon::now()
                ];
            }

            Pemindahbukuan::insert($vaArrays);

            GetterSetter::setLastFaktur("PB");

            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pemindahbukuan', 'store', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
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
            Func::writeLog('Pemindahbukuan', 'store', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }


    function storeJurnal(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            // if ($nReqCount > 8 || $nReqCount < 8) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeJurnal', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            // $vaValidator = validator::make($request->all(), [
            //     'Faktur' => 'required|max:25',
            //     'Rekening' => 'required|30',
            //     'Tgl' => 'date',
            //     'Debet' => 'max:16',
            //     'Kredit' => 'max:16',
            //     'CabangEntry' => 'max:3',
            // ], [
            //     'required' => 'Kolom :attribute harus diisi.',
            //     'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            // ]);
            // if ($vaValidator->fails()) {
            //     $vaErrorMsgs = $vaValidator->errors()->first();
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => $vaErrorMsgs
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeJurnal', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => $vaErrorMsgs
            //     ]);
            // }
            foreach ($vaRequestData as $record) {
                $vaArray = [
                    'Faktur' => $record['Faktur'],
                    'Rekening' => $record['Rekening'],
                    'Tgl' => $record['Tgl'],
                    'Debet' => $record['Debet'],
                    'Kredit' => $record['Kredit'],
                    'Keterangan' => $record['Keterangan'],
                    'CabangEntry' => $record['CabangEntry'],
                    'NoReff' => $record['NoReff'],
                    'UserName' => $cUser,
                    "DateTime" => Carbon::now()
                ];
            Jurnal::create($vaArray);
            }
            GetterSetter::setLastFaktur('JR');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pemindahbukuan', 'storeJurnal', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pemindahbukuan', 'storeJurnal', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function storeMutasiDeposito(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            // if ($nReqCount > 7 || $nReqCount < 7) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeMutasiDeposito', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            // $vaValidator = validator::make($request->all(), [
            //     'Faktur' => 'required|max:20',
            //     'Rekening' => 'required|15',
            //     'Tgl' => 'date',
            //     'SetoranPlafond' => 'max:16',
            //     'PencairanPlafond' => 'max:16',
            //     'CabangEntry' => 'max:3',
            //     'Kas' => 'max:1',
            // ], [
            //     'required' => 'Kolom :attribute harus diisi.',
            //     'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            // ]);
            // if ($vaValidator->fails()) {
            //     $vaErrorMsgs = $vaValidator->errors()->first();
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => $vaErrorMsgs
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeMutasiDeposito', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => $vaErrorMsgs
            //     ]);
            // }

            // $jthTMP = DB::table('deposito')->where('rekening', $vaRequestData['Rekening'])->value('jthTMP');
                
            foreach ($vaRequestData as $record) {
                
                $vaArray = [
                    'Faktur' => $record['Faktur'],
                    'Rekening' => $record['Rekening'],
                    'Tgl' => $record['Tgl'],
                    'SetoranPlafond' => $record['SetoranPlafond'],
                    'PencairanPlafond' => $record['PencairanPlafond'],
                    'Kas' => $record['Kas'],
                    'CabangEntry' => $record['CabangEntry'],
                    'DateTime' => Carbon::now(),
                    'UserName' => $cUser,
                    'Jthtmp' => DB::table('deposito')->where('rekening', $record['Rekening'])->value('Jthtmp')
                ];
                MutasiDeposito::create($vaArray);
            }
            
            GetterSetter::setLastFaktur('JR');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pemindahbukuan', 'storeMutasiDeposito', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pemindahbukuan', 'storeMutasiDeposito', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function storeMutasiTabungan(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            // $nReqCount = count($vaRequestData);
            // if ($nReqCount > 10 || $nReqCount < 10) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeMutasiTabungan', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            // $vaValidator = validator::make($request->all(), [
            //     'Faktur' => 'required|max:20',
            //     'Rekening' => 'required|15',
            //     'Tgl' => 'date',
            //     'KodeTransaksi' => 'max:3',
            //     'Kredit' => 'max:16',
            //     'Debet' => 'max:16',
            //     'Jumlah' => 'max:16',
            //     'DK' => 'max:1',
            //     'CabangEntry' => 'max:3',
            // ], [
            //     'required' => 'Kolom :attribute harus diisi.',
            //     'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            // ]);
            // if ($vaValidator->fails()) {
            //     $vaErrorMsgs = $vaValidator->errors()->first();
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => $vaErrorMsgs
            //     ];
            //     Func::writeLog('Pemindahbukuan', 'storeMutasiTabungan', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => $vaErrorMsgs
            //     ]);
            // }
            foreach ($vaRequestData as $record) {
                $vaArray = [
                    'Faktur' => $record['Faktur'],
                    'Rekening' => $record['Rekening'],
                    'Tgl' => $record['Tgl'],
                    'KodeTransaksi' => $record['KodeTransaksi'],
                    'Kredit' => $record['Kredit'],
                    'Debet' => $record['Debet'],
                    'Jumlah' => $record['Jumlah'],
                    'DK' => $record['DK'],
                    'Keterangan' => $record['Keterangan'],
                    'CabangEntry' => $record['CabangEntry'],
                    'DateTime' => Carbon::now(),
                    'UserName' => $cUser
                ];
            MutasiTabungan::create($vaArray);
            }
            GetterSetter::setLastFaktur('JR');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pemindahbukuan', 'storeMutasiTabungan', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pemindahbukuan', 'storeMutasiTabungan', $vaRequestData, $vaRetVal, $cUser);
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
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Faktur'] == null || empty($vaRequestData['Faktur'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('pemindahbukuan', 'delete', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cFaktur = $vaRequestData['Faktur'];
            $vaData  = Pemindahbukuan::where('Faktur', '=', $cFaktur)->delete();
            $vaData2 = Jurnal::where('Faktur', '=', $cFaktur)->delete();
            $vaData3 = Angsuran::where('Faktur', '=', $cFaktur)->delete();
            $vaData4 = MutasiTabungan::where('Faktur', '=', $cFaktur)->delete();
            $vaData5 = MutasiDeposito::where('Faktur', '=', $cFaktur)->delete();
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pemindahbukuan', 'delete', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pemindahbukuan', 'delete', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
