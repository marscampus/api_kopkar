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
 * Created on Fri Dec 29 2023 - 19:56:39
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpananberjangka;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarSaldoSimpananBerjangkaController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cGolAwal = $vaRequestData['GolonganAwal'];
            $cGolAkhir = $vaRequestData['GolonganAkhir'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTgl = $request->Tgl;
            $dTglAkhir = Carbon::parse($dTgl)->endOfMonth();
            $nTotalNominal = 0;

            $vaData = DB::table('deposito as d')
                ->select(
                    'd.CaraPerpanjangan',
                    'd.Kode',
                    'd.RekeningLama',
                    'd.Rekening',
                    'd.RekeningTabungan',
                    'r.Nama',
                    'd.CaraPerhitungan',
                    DB::raw('ifnull(r.KodeInduk,r.kode) as KodeInduk'),
                    'd.WilayahAO',
                    'd.AO',
                    DB::raw("(select sukubunga from deposito_sukubunga where rekening = d.rekening and tgl <= '$dTgl' order by tgl desc limit 1) as Rate"),
                    'd.SukuBunga',
                    'g.Lama',
                    'r.Alamat',
                    'g.WajibPajak',
                    'd.NoBilyet',
                    'd.ARO',
                    'd.GolonganDeposan',
                    'd.Tgl',
                    'd.JthTmp',
                    'd.Keterkaitan',
                    'r.Kodya',
                    'd.status',
                    'g.keterangan as NamaGolongan',
                    'd.GolonganDeposito',
                    DB::raw('sum(m.setoranplafond-m.pencairanplafond) as Nominal')
                )
                ->leftJoin('golongandeposito as g', 'g.kode', '=', 'd.golongandeposito')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                ->leftJoin('mutasideposito as m', function ($join) use ($dTgl) {
                    $join->on('m.Rekening', '=', 'd.Rekening')
                        ->where('m.Tgl', '<=', $dTgl);
                })
                ->where('d.Keterkaitan', '>=', '')
                ->where('d.Keterkaitan', '<=', '2')
                ->where('d.GolonganDeposito', '>=', $cGolAwal)
                ->where('d.GolonganDeposito', '<=', $cGolAkhir)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy(
                    'd.Rekening'
                )
                ->having('Nominal', '>', 0)
                ->orderBy('GolonganDeposito')
                ->orderBy('g.Lama')
                ->orderBy('d.Rekening')
                ->get();

            $vaResult = [];
            $nRow = 0;

            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $cNama = $d->Nama;
                $nNominal = $d->Nominal;
                $dJthTmp = PerhitunganDeposito::getTglJthTmpDeposito($cRekening, $dTgl);
                $dTglAro = Carbon::parse($dJthTmp)->subMonths($d->Lama)->format('Y-m-d');
                $dBagiHari = Carbon::parse($dTgl)->format('d');
                $dDayDeposito = Carbon::parse($d->Tgl)->format('d');
                $nPersenPajak = GetterSetter::getDBConfig('msTarifPajak');
                $nSukuBunga = GetterSetter::getRate($dTgl, $cRekening);
                $nBunga = round($nNominal * $nSukuBunga / 1200, 0);
                $dJumlahHari = Carbon::parse($dTglAkhir)->format('d');
                $cCaraPerhitungan = PerhitunganDeposito::getCaraPerhitunganDeposito($cRekening);
                $nBungaHarian = $nSukuBunga * $nNominal / 100 / 365;
                if ($cCaraPerhitungan) {
                    $nBunga = $nSukuBunga * $nNominal / 100 / 12;
                } else {
                    $nBunga = $dBagiHari * $nBungaHarian;
                }

                $nBunga = floor($nBunga);
                $nAccrual = floor(($dJumlahHari - $dDayDeposito + 1) * $nBungaHarian);
                $nWajibPajak = PerhitunganDeposito::getWajibPajak(PerhitunganDeposito::getGolonganDeposito($cRekening));
                $nPajak = 0;

                if ($nWajibPajak == 'Y') {
                    if ($nNominal > 7500000) {
                        $nPajak = $nPersenPajak * $nBunga / 100;
                    } else {
                        $cKodeInduk = $d->KodeInduk;
                        $nTotalKekayaan = GetterSetter::getTotalKekayaan($cKodeInduk, $dTgl);
                        $nPajak = $nTotalKekayaan > 7500000 ? $nPersenPajak * $nBunga / 100 : 0;
                    }
                    $nPajak = GetterSetter::PembulatanDeposito($nPajak);
                } else {
                    $nPajak = 0;
                }

                $nBungaNetto = $nBunga - $nPajak;
                $dHariValuta = Carbon::parse($d->Tgl)->format('d');
                $dHariBulanIni = $dTglAkhir->format('d');

                if ($dHariValuta >= $dHariBulanIni) {
                    $hariAro = $dHariBulanIni;
                }

                $vaResult[] = [
                    'No' => ++$nRow,
                    'Rekening' => $d->Rekening,
                    'Nama' => $cNama,
                    'Alamat' => $d->Alamat,
                    'NoBilyet' => $d->NoBilyet,
                    'AO' => $d->AO,
                    'Rate' => $nSukuBunga,
                    'TglValuta' => $d->Tgl,
                    'TglAro' => $dTglAro,
                    'JthTmp' => $dJthTmp,
                    'Nominal' => $nNominal,
                    'GolDeposito' => $d->GolonganDeposito . ' - ' . $d->NamaGolongan,
                    'Bunga' => $nBunga,
                    'Accrual' => $nAccrual,
                    'Pajak' => $nPajak,
                    'BungaNetto' => $nBungaNetto
                ];
                $nTotalNominal += $nNominal;
            }
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'totals' => $nTotalNominal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Daftar Saldo Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Daftar Saldo Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }
}
