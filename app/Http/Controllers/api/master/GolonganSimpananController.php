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
 * Created on Wed Apr 03 2024 - 01:37:43
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\GolonganSimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;

class GolonganSimpananController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nLimit = 10;
            $vaData = DB::table('golongantabungan')
                ->select(
                    'Kode',
                    'Keterangan',
                    'Rekening',
                    'RekeningBunga',
                    'SukuBunga',
                    'SaldoMinimum',
                    'SetoranMinimum',
                    'SaldoMinimumDapatBunga',
                    'AdministrasiTutup',
                    'AdministrasiBulanan',
                    'AdminPasif',
                    'AdministrasiTahunan',
                    'PenjualanBukuTabungan',
                    'WajibPajak',
                );
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->orderBy('Kode', 'ASC');
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
                Func::writeLog('Golongan Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Golongan Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Golongan Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        $Rekening = $request->Rekening;
        $RekeningBunga = $request->RekeningBunga;
        $SukuBunga = $request->SukuBunga;
        $SaldoMinimum = $request->SaldoMinimum;
        $SetoranMinimum = $request->SetoranMinimum;
        $SaldoMinimumDapatBunga = $request->SaldoMinimumDapatBunga;
        $AdministrasiTutup = $request->AdministrasiTutup;
        $AdministrasiBulanan = $request->AdministrasiBulanan;
        $AdminPasif = $request->AdminPasif;
        $AdministrasiTahunan = $request->AdministrasiTahunan;
        $PenjualanBukuTabungan = $request->PenjualanBukuTabungan;
        $RekeningCadanganBunga = $request->RekeningCadanganBunga;
        $WajibPajak = $request->WajibPajak;

        try {
            $GolonganSimpanan = GolonganSimpanan::create([
                'Kode' => $Kode,
                'Keterangan' => $keterangan,
                'Rekening' => $Rekening,
                'RekeningBunga' => $RekeningBunga,
                'SukuBunga' => $SukuBunga,
                'SaldoMinimum' => $SaldoMinimum,
                'SetoranMinimum' => $SetoranMinimum,
                'SaldoMinimumDapatBunga' => $SaldoMinimumDapatBunga,
                'AdministrasiTutup' => $AdministrasiTutup,
                'AdministrasiBulanan' => $AdministrasiBulanan,
                'AdminPasif' => $AdminPasif,
                'AdministrasiTahunan' => $AdministrasiTahunan,
                'PenjualanBukuTabungan' => $PenjualanBukuTabungan,
                'RekeningCadanganBunga' => $RekeningCadanganBunga,
                'WajibPajak' => $WajibPajak
            ]);
        } catch (\Throwable $th) {
            return $th; //response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $GolonganSimpanan = GolonganSimpanan::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan,
            'Rekening' => $request->Rekening,
            'RekeningBunga' => $request->RekeningBunga,
            'SukuBunga' => $request->SukuBunga,
            'SaldoMinimum' => $request->SaldoMinimum,
            'SetoranMinimum' => $request->SetoranMinimum,
            'SaldoMinimumDapatBunga' => $request->SaldoMinimumDapatBunga,
            'AdministrasiTutup' => $request->AdministrasiTutup,
            'AdministrasiBulanan' => $request->AdministrasiBulanan,
            'AdminPasif' => $request->AdminPasif,
            'AdministrasiTahunan' => $request->AdministrasiTahunan,
            'PenjualanBukuTabungan' => $request->PenjualanBukuTabungan,
            'RekeningCadanganBunga' => $request->RekeningCadanganBunga,
            'WajibPajak' => $request->WajibPajak
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $GolonganSimpanan = GolonganSimpanan::findOrFail($request->Kode);
            $GolonganSimpanan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
