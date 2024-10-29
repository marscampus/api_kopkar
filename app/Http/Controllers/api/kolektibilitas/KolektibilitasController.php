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
 * Created on Thu Dec 07 2023 - 09:15:20
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\kolektibilitas;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PerhitunganPinjaman;
use Illuminate\Support\Facades\Validator;

class KolektibilitasController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $nLimit = 10;
            $vaData = DB::table('debitur_kol_harian as dm')
                ->select(
                    'rn.Nama',
                    'rn.Alamat',
                    'd.Tgl',
                    DB::raw('DATE_ADD(d.Tgl, INTERVAL d.Lama MONTH) AS JthTMP'),
                    'd.Lama',
                    'd.Plafond',
                    'dm.*',
                    DB::raw("CASE
                    WHEN dm.Kol = 0 THEN '0 - Lancar Tanpa Tunggakan'
                    WHEN dm.Kol = 1 THEN '1 - Lancar'
                    WHEN dm.Kol = 2 THEN '2 - DPK'
                    WHEN dm.Kol = 3 THEN '3 - Kurang Lancar'
                    WHEN dm.Kol = 4 THEN '4 - Diragukan'
                    WHEN dm.Kol = 5 THEN '5 - Macet'
                END AS Golongan")
                )
                ->join('debitur as d', 'd.Rekening', '=', 'dm.Rekening')
                ->join('registernasabah as rn', 'rn.Kode', '=', 'd.Kode')
                // ->join('cabang as c', 'c.Kode', '=', 'dm.Cabang')
                // ->where('dm.Periode', '=', $vaRequestData['Periode'])
                ->when(
                    $cJenisGabungan != 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->whereBetween('dm.Kol', [$vaRequestData['KolAwal'], $vaRequestData['KolAkhir']])
                ->whereBetween('d.GolonganKredit', [$vaRequestData['GolonganAwal'], $vaRequestData['GolonganAkhir']])
                ->orderBy('dm.Kol');

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

            $nTotalPlafond = 0;
            $nTotalBakiDebet = 0;
            $nTotalTunggakan_Akhir = 0;
            $nTotalTBunga = 0;
            $nTotalTPokok = 0;
            foreach ($vaData as $d) {
                $nTotalPlafond += $d->Plafond;
                $nTotalBakiDebet += $d->BakiDebet;
                $nTotalTunggakan_Akhir += ( $d->TBunga+ $d->TPokok);
                $nTotalTBunga += $d->TBunga;
                $nTotalTPokok += $d->TPokok;
            }

            $vaTotal = [
                'TotalPlafond' => $nTotalPlafond,
                'TotalBakiDebet' => $nTotalBakiDebet,
                'TotalTBunga' => $nTotalTBunga,
                'TotalTPokok' => $nTotalTPokok,
                'TotalTunggakan_Akhir' => $nTotalTunggakan_Akhir,
            ];

            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Kolektibilitas', 'data', $vaRequestData, $vaRetVal, $cUser);

                $vaResponse = [
                    'data' => $vaData,
                    'total_data' => count($vaData),
                    'totals' => $vaTotal
                ];
                return response()->json($vaResponse);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Kolektibilitas', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Kolektibilitas', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
