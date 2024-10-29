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
 * Created on Thu Dec 28 2023 - 14:05:08
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiSimpananHarianController extends Controller
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
            $cKodeTransaksiAwal = $vaRequestData['KodeTransaksiAwal'];
            $cKodeTransaksiAkhir = $vaRequestData['KodeTransaksiAkhir'];
            $cGroupBy = $vaRequestData['GroupBy'];
            $cJenisMutasi = $vaRequestData['JenisMutasi'];
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            if ($cGroupBy == 'K') {
                $cOrder = 'm.KodeTransaksi';
            } else if ($cGroupBy == 'G') {
                $cOrder = 't.GolonganTabungan';
            }
            $vaData = DB::table('tabungan')
                ->select('golongantabungan as Kode')
                ->distinct()
                ->get();

            $vaResult = [];
            $nTotalDebet = 0;
            $nTotalKredit = 0;
            $nTotalSaldoAkhir = 0;

            foreach ($vaData as $d) {
                $cKode = $d->Kode;
                $cKeterangan = GetterSetter::getKeterangan($cKode, 'KETERANGAN', 'golongantabungan');
                $nSaldoAwal = 0;
                $vaData2 = DB::table('tabungan')
                    ->leftJoin('mutasitabungan', 'mutasitabungan.rekening', '=', 'tabungan.rekening')
                    ->select(DB::raw('IFNULL(SUM(mutasitabungan.kredit - mutasitabungan.debet),0) as Saldo'))
                    ->where('tabungan.golongantabungan', '=', $cKode)
                    ->where('mutasitabungan.tgl', '<', $dTglAwal)
                    ->get();
                foreach ($vaData2 as $d2) {
                    $nSaldoAwal = $d2->Saldo;
                }
                $vaSaldo[$cKode] = [
                    'Keterangan' => $cKeterangan,
                    'SaldoAwal' => $nSaldoAwal,
                    'Debet' => 0,
                    'Kredit' => 0,
                    'SaldoAkhir' => $nSaldoAwal
                ];
                $vaData3 = DB::table('mutasitabungan as m')
                    ->select(
                        'm.Rekening',
                        't.RekeningLama',
                        't.NamaNasabah as Nama',
                        'm.Faktur',
                        'm.Tgl',
                        'm.Debet',
                        'm.Kredit',
                        'm.UserName',
                        't.GolonganTabungan',
                        DB::raw('g.keterangan as NamaGolonganTabungan'),
                        'm.KodeTransaksi',
                        DB::raw('k.keterangan as NamaKodeTransaksi')
                    )
                    ->leftJoin('tabungan as t', 't.rekening', '=', 'm.rekening')
                    ->leftJoin('registernasabah as r', 'r.kode', '=', 't.kode')
                    ->leftJoin('golongantabungan as g', 'g.kode', '=', 't.golongantabungan')
                    ->leftJoin('kodetransaksi as k', 'k.kode', '=', 'm.kodetransaksi')
                    ->leftJoin('cabang as c', 'c.kode', '=', 'm.cabangentry')
                    ->whereBetween('m.tgl', [$dTglAwal, $dTglAkhir])
                    ->where('m.kodetransaksi', '>=', $cKodeTransaksiAwal)
                    ->where('m.kodetransaksi', '<=', $cKodeTransaksiAkhir)
                    ->where(
                        function ($query) use ($cJenisMutasi) {
                            if ($cJenisMutasi == 'P') {
                                $query->where('Faktur', 'NOT LIKE', 'BPR%');
                            } elseif ($cJenisMutasi == 'M') {
                                $query->where('Faktur', 'LIKE', 'BPR%');
                            }
                        }
                    )
                    ->when(
                        $cJenisGabungan != 'C',
                        function ($query) use ($cJenisGabungan, $cCabang) {
                            $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                        }
                    )
                    ->orderBy($cOrder)
                    ->orderBy('m.tgl')
                    ->orderBy('m.rekening')
                    ->orderBy('m.id')
                    ->orderBy('m.faktur')
                    // ->paginate(10);
                    ->get();

                $nRow = 0;
                $previousGolongan = null;
                foreach ($vaData3 as $d3) {
                    $nRow++;
                    $cRekening = $d3->Rekening;
                    $dTgl = $d3->Tgl;
                    if ($cGroupBy == 'G') { // GOLONGAN TABUNGAN
                        $cGolongan =  $d3->GolonganTabungan;
                        $cKetGolongan = $d3->NamaGolonganTabungan;
                    } else { // KODE TRANSAKSI
                        $cGolongan = $d3->KodeTransaksi;
                        $cKetGolongan = $d3->NamaKodeTransaksi;
                    }
                    $dTglHarianKemarin = date("Y-m-d", strtotime($dTgl) - (60 * 60 * 24));
                    if (!isset($vaTabungan[$cRekening])) {
                        $vaTabungan[$cRekening] = PerhitunganTabungan::getSaldoTabungan($cRekening, $dTglHarianKemarin);
                    }
                    $vaTabungan[$cRekening] += $d3->Kredit - $d3->Debet;

                    $vaResult[] = [
                        'No' => $nRow,
                        'Rekening' => $cRekening,
                        'GolTabungan' => $cGolongan . ' - ' . $cKetGolongan,
                        'UserName' => $d3->UserName,
                        'NoTransaksi' => $d3->Faktur,
                        'Tgl' => $d3->Tgl,
                        'Nama' => GetterSetter::getNamaRegisterNasabah($cRekening),
                        'Sandi' => $d3->KodeTransaksi,
                        'Debet' => $d3->Debet,
                        'Kredit' => $d3->Kredit,
                        'SaldoAkhir' => $vaTabungan[$cRekening]
                    ];

                    $nTotalDebet += $d3->Debet; // Mengumpulkan total debet
                    $nTotalKredit += $d3->Kredit; // Mengumpulkan total kredit
                    $nTotalSaldoAkhir += $vaTabungan[$cRekening]; // Mengumpulkan total saldo akhir

                    $vaTotal = [
                        'TotalDebet' => $nTotalDebet,
                        'TotalKredit' => $nTotalKredit,
                        'TotalSaldoAkhir' => $nTotalSaldoAkhir,
                    ];
                }

                $vaNumericArray = array_values($vaResult);

                $vaResults = [
                    'data' => $vaNumericArray,
                    'total_data' => count($vaNumericArray),
                    'totals' => $vaTotal
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResults
                ];
                Func::writeLog('Mutasi Simpanan Harian', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResults);
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
            Func::writeLog('Mutasi Simpanan Harian', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
