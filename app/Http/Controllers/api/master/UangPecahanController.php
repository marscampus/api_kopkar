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
 * Created on Fri Mar 15 2024 - 06:48:27
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\UangPecahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;

class UangPecahanController extends Controller
{
    function dataJurnalUangPecahan(Request $request)
    {
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $UangPecahan = UangPecahan::where($k, "LIKE", '%' . $v . '%')->orderByDesc('Status')->orderByDesc('Nominal')->paginate(100);
                return response()->json($UangPecahan);
            }
        }

        $UangPecahan = UangPecahan::orderBy('Status')->orderByDesc('Nominal')->paginate(100);
        return response()->json($UangPecahan);
    }

    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            $nLimit = 10;
            unset($vaRequestData['auth']);
            $vaData = DB::table('uangpecahan')
                ->select(
                    'Kode',
                    'Status',
                    'Nominal'
                );
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, 'LIKE', '%' . $filterValue . '%');
                }
            }
            if (!empty($vaRequestData['page'] === null)) {
                $vaData = $vaData->get();
            } else {
                $vaData = $vaData->paginate($nLimit);
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Uang Pecahan', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Uang Pecahan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function all(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            // dd($vaRequestData);
            $cUser =  $vaRequestData['auth']['name'];
            $nLimit = 10;
            unset($vaRequestData['auth']);
            $vaData = DB::table('uangpecahan')
                ->select(
                    'Kode',
                    'Status',
                    'Nominal'
                );
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, 'LIKE', '%' . $filterValue . '%');
                }
            }
            if ($vaRequestData['page'] == null) {
                $vaData = $vaData->get();
            } else {
                $vaData = $vaData->paginate($nLimit);
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Uang Pecahan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Uang Pecahan', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Uang Pecahan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        // $request->validate([
        //     'Kode'=> 'required|max:4',
        //     'KETERANGAN'=>'required'
        // ]);
        $Kode = $request->Kode;
        $nominal = $request->Nominal;
        $status = $request->Status;
        try {
            $UangPecahan = UangPecahan::create([
                'Kode' => $Kode,
                'Nominal' => $nominal,
                'Status' => $status
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        // $request->validate([
        //     'Kode'=> 'required|max:4',
        //     'KETERANGAN'=>'required'
        // ]);
        $UangPecahan = UangPecahan::where('Kode', $Kode)->update([
            'Nominal' => $request->Nominal,
            'Status' => $request->Status
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        // return response()->json([$request]);
        try {
            $UangPecahan = UangPecahan::findOrFail($request->Kode);
            $UangPecahan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
