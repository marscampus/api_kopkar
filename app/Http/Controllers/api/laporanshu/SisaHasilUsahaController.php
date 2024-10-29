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
 * Created on Fri Jan 19 2024 - 02:19:56
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanshu;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\jurnal\Jurnal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SisaHasilUsahaController extends Controller
{
    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        $cJenisGabungan = $vaRequestData['JenisGabungan'];
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $vaRequestData['Cabang'];
        }
        $nLimit = 10;
        $dTgl = $vaRequestData['Periode'];
        try {
            $vaData = DB::table('nisbah')
                ->select(
                    'Kode',
                    'Keterangan',
                    'Rekening',
                    'Nisbah'
                );
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", "%" . $filterValue . "%");
                }
            }
            if ($vaRequestData['page'] == null) {
                $vaData = $vaData->get(0);
            } else {
                $vaData = $vaData->paginate($nLimit);
            }
            $cRekening = GetterSetter::getDBConfig('msRekeningLabaTahunLalu');
            $nSaldoAwal = GetterSetter::getSaldoAwal($dTgl, $cRekening, '', '', $cCabang, '', '', $cJenisGabungan);
            // JIKA REQUEST SUKSES
            $vaResult = [
                'SaldoAwal' => $nSaldoAwal,
                'data' => $vaData
            ];
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Nisbah', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Sisa Hasil Usaha', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function preview(Request $request)
    {
        try {
            // Ambil data dari request dan konversi ke array
            $vaRequestData = $request->json()->all();

            // Dapatkan informasi tambahan yang dibutuhkan
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTgl = $vaRequestData['Periode'];
            $cRekening = '3.500.03';
            $nSaldoAwal = GetterSetter::getSaldoAwal($dTgl, $cRekening, '', '', '', '', '', '');
            $nTotalDataPersen = 0;
            $nTotalDataRupiah = 0;

            $vaResult = [
                "SaldoAwal" => Func::getZFormat($nSaldoAwal),
                "TotalPersen" => 0,
                "TotalRupiah" => 0,
                "data" => [],
            ];

            // Iterasi setiap data pada properti 'data'
            foreach ($vaRequestData['data'] as $item) {
                $nNisbah = $item['Nisbah'];
                $nTotalDataPersen += $nNisbah;
                $nTotal = $nSaldoAwal * $nNisbah / 100;
                $nTotalDataRupiah += $nTotal;
                $vaResult['data'][] = [
                    'Kode' => $item['Kode'],
                    'Keterangan' => $item['Keterangan'],
                    'Nisbah' => $nNisbah . '%',
                    'Total' => Func::getZFormat($nSaldoAwal * $nNisbah / 100),
                ];
            }

            $vaResult['TotalPersen'] = $nTotalDataPersen;
            $vaResult['TotalRupiah'] = Func::getZFormat($nTotalDataRupiah);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Sisa Hasil Usaha', 'preview', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Sisa Hasil Usaha', 'preview', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function posting(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTgl = GetterSetter::getTglTransaksi();
            foreach ($vaRequestData as $data) {
                $nSubTotalNisbah = Func::String2Number($data['Total']);
                $cRekening = '3.500.03';
                $cRekeningLawan = $data['Rekening'];
                $cFaktur = GetterSetter::getLastFaktur("JR", true);
                $cKet = $data['Keterangan'];

                if ($nSubTotalNisbah > 0) {
                    Jurnal::where('Faktur', 'LIKE', 'JR%')
                        ->where('Keterangan', '=', $cKet)
                        ->delete();

                    Upd::updJurnalLainLain($dTgl, $cFaktur, $cRekening, $cKet, $nSubTotalNisbah, 0, true, '', '', '', '', $cUser);
                    Upd::updJurnalLainLain($dTgl, $cFaktur, $cRekeningLawan, $cKet,  0, $nSubTotalNisbah, true, '', '', '', '', $cUser);
                }
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "success"
            ];
            Func::writeLog('Sisa Hasil Usaha', 'posting', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Sisa Hasil Usaha', 'posting', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
