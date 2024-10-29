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
 * Created on Thu Mar 14 2024 - 02:09:13
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\CaraPerhitungan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PinjamanJatuhTempoController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $vaData = DB::table('debitur as d')
                ->select(
                    'd.RekeningLama',
                    'd.Rekening',
                    'd.Tgl',
                    'r.Nama',
                    'r.Alamat',
                    'd.SektorEkonomi',
                    'd.GolonganDebitur',
                    'd.JenisPenggunaan',
                    'd.Lama',
                    'd.NoSPK',
                    'd.Plafond',
                    DB::raw('IFNULL(SUM(a.DPokok - a.KPokok), 0) as SaldoPokok'),
                    'o.Nama as NamaAO',
                    'd.AO',
                    DB::raw('IFNULL(SUM(a.KPokok), 0) as PembayaranPokok'),
                    DB::raw('IFNULL(SUM(a.KBunga), 0) as PembayaranBunga'),
                    'd.SukuBunga',
                    'd.CaraPerhitungan',
                    'd.Wilayah',
                    'd.BagianYangDijamin',
                    'd.GolonganPenjamin'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('angsuran as a', function ($join) use ($dTglAkhir) {
                    $join->on('a.Rekening', '=', 'd.Rekening')
                        ->where('a.Tgl', '<=', $dTglAkhir);
                })
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->where('d.Tgl', '<=', $dTglAkhir)
                ->groupBy('a.Rekening')
                ->having('SaldoPokok', '>', '0')
                ->get();
            $nTotalPlafond = 0;
            $nTotalBakiDebet = 0;
            $nTotalTPokok = 0;
            $nTotalTBunga = 0;
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $vaRealisasi = GetterSetter::getAdendum($cRekening, $dTglAkhir);
                $d->Tgl = $vaRealisasi['Tgl'];
                $d->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
                $d->Lama = $vaRealisasi['Lama'];
                $d->SukuBunga = $vaRealisasi['SukuBunga'];
                $d->Plafond = $vaRealisasi['Plafond'];
                $nTglRealisasi = Func::Tgl2Time($d->Tgl);
                $nLama = $d->Lama;
                $dJthTmp = date('Y-m-d', Date::nextMonth($nTglRealisasi, $nLama));
                $vaTotal = GetterSetter::getTotalPembayaranKredit($cRekening, $dTglAkhir);
                $vaT = GetterSetter::getTunggakan($cRekening, $dTglAkhir);
                if ($dJthTmp >= $dTglAwal && $dJthTmp <= $dTglAkhir) {
                    $vaArray[] = [
                        'Rekening' => $cRekening,
                        'NoSPK' => $d->NoSPK,
                        'NamaNasabah' => $d->Nama,
                        'TglCair' => $d->Tgl,
                        'JangkaWaktu' => $nLama,
                        'JthTmp' => $dJthTmp,
                        'SukuBunga' => $d->SukuBunga,
                        'Kol' => 0,
                        'Plafond' => $d->Plafond,
                        'BakiDebet' => $d->SaldoPokok,
                        'TPokok' => $vaT['T_Pokok_Akhir'],
                        'TBunga' => $vaT['T_Bunga_Akhir'],
                        'Kewajiban' => $vaT['T_Pokok_Akhir'] + $vaT['T_Bunga_Akhir'],
                        'AO' => $d->AO
                    ];
                    $nTotalPlafond += $d->Plafond;
                    $nTotalBakiDebet += $d->SaldoPokok;
                    $nTotalTPokok += $vaT['T_Pokok_Akhir'];
                    $nTotalTBunga += $vaT['T_Bunga_Akhir'];
                    $vaTotals = [
                        'TotalPlafond' => $nTotalPlafond,
                        'TotalBakiDebet' => $nTotalBakiDebet,
                        'TotalTBunga' => $nTotalTBunga,
                        'TotalTPokok' => $nTotalTPokok,
                        'Kewajiban' => $nTotalTPokok + $nTotalTBunga
                    ];
                }
            }
            $vaResults = [
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'totals' => $vaTotals
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Pinjaman Jatuh Tempo', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pinjaman Jatuh Tempo', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }
}
