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
 * Created on Mon Dec 25 2023 - 17:55:28
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\Validator;

class RekeningController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nLimit = 9999;
            $vaData = DB::table('rekening')
                ->select(
                    'Kode',
                    'Keterangan',
                    'Jenis'
                )
                ->where('Kode', 'LIKE', $vaRequestData['Kode'] . '%')
                ->paginate($nLimit);
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Rekening', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Rekening', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function allData(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $vaData = DB::table('rekening')
                ->select(
                    'Kode',
                    'Keterangan',
                    'Jenis'
                )
                ->get();

            // JIKA REQUEST SUKSES
            if ($vaData->count() > 0) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Rekening', 'allData', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Rekening', 'allData', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Rekening', 'allData', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }


    function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 3 || $nReqCount < 3) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Rekening', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetval;
                return response()->json(['status' => 'error']);
            }

            $vaValidator = Validator::make($request->all(), [
                'Kode' => 'required|max:20',
                'Keterangan' => 'required|max:50',
                'Jenis' => 'required|max:1'
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
                Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }

            $vaArray = [
                'Kode' => $vaRequestData['Kode'],
                'Keterangan' => $vaRequestData['Keterangan'],
                'Jenis' => $vaRequestData['Jenis']
            ];
            Rekening::create($vaArray);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Rekening', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Rekening', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function update(Request $request, $Kode)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 3 || $nReqCount < 3) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetval;
                return response()->json(['status' => 'error']);
            }

            $vaValidator = Validator::make($request->all(), [
                'Kode' => 'required|max:20',
                'Keterangan' => 'required|max:50',
                'Jenis' => 'required|max:1'
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
                Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }

            $cKode = $vaRequestData['Kode'];
            $vaData = DB::table('rekening')
                ->where('Kode', '=', $cKode)
                ->exists();
            if ($vaData) {
                $vaArray = [
                    'Kode' => $vaRequestData['Kode'],
                    'Keterangan' => $vaRequestData['Keterangan'],
                    'Jenis' => $vaRequestData['Jenis']
                ];
                Rekening::where('Kode', '=', $cKode)->update($vaArray);
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => "SUKSES"
                ];
                Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'success']);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];

                Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Rekening', 'update', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error', 'message' => $th]);
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
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Kode'] == null || empty($vaRequestData['Kode'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Rekening', 'delete', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = Rekening::findOrFail($vaRequestData['Kode']);
            $vaData->delete();
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Rekening', 'delete', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Rekening', 'delete', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
