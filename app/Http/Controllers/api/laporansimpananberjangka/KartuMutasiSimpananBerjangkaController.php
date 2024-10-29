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
 * Created on Thu Jan 04 2024 - 08:43:27
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpananberjangka;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartuMutasiSimpananBerjangkaController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 1 || $nReqCount > 1) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $vaData = DB::table('deposito as d')
                ->select('d.NoBilyet', 'r.Nama', 'r.Alamat', 'd.Tgl', 'g.Lama', 'd.Tgl as TglValuta')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
                ->leftJoin('golongandeposito as g', 'g.kode', '=', 'd.golongandeposito')
                ->leftJoin('mutasideposito as m', 'm.rekening', '=', 'd.Rekening')
                ->where('d.rekening', '=', $cRekening)
                ->orderByDesc('m.Tgl')
                ->first();
            if ($vaData) {
                $dJthTmp = PerhitunganDeposito::getTglJthTmpDeposito($cRekening, GetterSetter::getTglTransaksi());
                $dTglAro = Carbon::parse($dJthTmp)->subMonths($vaData->Lama)->format('Y-m-d');
                $vaResult = [
                    'Nama' => $vaData->Nama,
                    'Alamat' => $vaData->Alamat,
                    'NoBilyet' => $vaData->NoBilyet,
                    'TglValuta' => $dTglAro,
                    'JthTmp' => $dJthTmp
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 3 || $nReqCount > 3) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }

            $cRekening = $vaRequestData['Rekening'];
            $dTgl = GetterSetter::getTglTransaksi();
            $dTglAwal = $vaRequestData['TglValuta'];
            $nNo = 0;
            $nTotalPencairan = 0;
            $nTotalSetoran = 0;
            $nTotalBunga = 0;
            $nTotalPajak = 0;
            $nTotalPinalty = 0;

            $vaData = DB::table('mutasideposito as m')
                ->select(
                    'm.Faktur',
                    'm.Tgl',
                    'm.SetoranPlafond',
                    'm.PencairanPlafond',
                    'm.Bunga',
                    'm.Pajak',
                    'm.Pinalty',
                    'm.UserName',
                    'd.NoBilyet',
                    'd.GolonganDeposito',
                    'd.GolonganDeposan',
                    'd.Keterkaitan',
                    'd.SukuBunga',
                    'd.Nominal',
                    'd.RekeningTabungan',
                    'd.CaraPerhitungan',
                    'd.Aro',
                    'd.Kode',
                    'd.JthTmp',
                    'g.Lama',
                    'g.Keterangan as ketgolDeposito',
                    'e.Keterangan as ketGolDeposan'
                )
                ->leftJoin('deposito as d', 'd.rekening', '=', 'm.rekening')
                ->leftJoin('golongandeposito as g', 'g.Kode', '=', 'd.GolonganDeposito')
                ->leftJoin('golongandeposan as e', 'e.Kode', '=', 'd.GolonganDeposan')
                ->where('m.rekening', '=', $cRekening)
                ->whereBetween('m.Tgl', [$dTglAwal, $dTgl])
                ->orderByDesc('m.Tgl')
                ->paginate(10);

            $vaDetails = [];
            $nKe = 0;
            $nNominal = 0;
            $nJangkaWaktu = 0;
            $cNoBilyet = '';
            $cCif = '';
            $cGolDeposan = '';
            $cGolDeposito = '';
            $cKeterkaitan = '';
            $nSukuBunga = '';
            $cAro = '';
            $cCaraPerhitungan = '';
            $dJthTmp = '';
            $cRekeningTabungan = '';
            $nSetoranPlafond = 0;
            $nPersenBunga = 0;
            $nBunga = 0;
            $nPajak = 0;

            if ($vaData->total() > 0) {
                foreach ($vaData as $d) {
                    $cNoBilyet = $d->NoBilyet;
                    $nTotalPencairan += $d->PencairanPlafond;
                    $nTotalSetoran += $d->SetoranPlafond;
                    $nTotalBunga += $d->Bunga;
                    $nTotalPajak += $d->Pajak;
                    $nTotalPinalty += $d->Pinalty;
                    $cGolDeposan = $d->GolonganDeposan;
                    $cGolDeposito = $d->GolonganDeposito;
                    $cKeterkaitan = $d->Keterkaitan;
                    $nJangkaWaktu = $d->Lama;
                    $dJthTmp = $d->JthTmp;
                    $nSukuBunga = $d->SukuBunga;
                    $cAro = $d->Aro;
                    $cCaraPerhitungan = $d->CaraPerhitungan;
                    $cRekeningTabungan = $d->RekeningTabungan;
                    $cCif = $d->Kode;
                    $nNominal = PerhitunganDeposito::getNominalDeposito($cRekening, $d->Tgl);
                    $nRate = GetterSetter::getRate($d->Tgl, $cRekening);
                    $nKe = GetterSetter::getKe($dTglAwal, $dTgl, $nJangkaWaktu);
                    $nNo++;
                    $vaDetail = [
                        'No' => $nNo,
                        'Faktur' => $d->Faktur,
                        'Tgl' => $d->Tgl,
                        'Faktur' => $d->Faktur . '|' . $nKe,
                        'SetoranPlafond' => $d->SetoranPlafond,
                        'PencairanPlafond' => $d->PencairanPlafond,
                        'Nominal' => $d->Nominal,
                        'Rate' => $nRate,
                        'Bunga' => $d->Bunga,
                        'Pajak' => $d->Pajak,
                        'Pinalty' => $d->Pinalty,
                        'UserName' => $d->UserName
                    ];
                    $vaDetails[] = $vaDetail;
                }

                $nKe += 1;
                $nDeposito = $nNominal;
                $nSetoranPlafond = 0;
                $dTglJadwal = null;
                for ($n = $nKe; $n <= $nJangkaWaktu; $n++) {
                    $nNo++;
                    $nKe = $n;
                    $dTglJadwal = date('Y-m-d', Date::nextMonth(strtotime($dTglAwal), $n, 0));
                    $nPersenBunga = GetterSetter::getRate($dTglJadwal, $cRekening);
                    if ($cAro == 'P') {
                        $nBunga = $nPersenBunga * $nDeposito / 100 / 12;
                        $nPajak = 0;

                        if ($nBunga > 240000) {
                            $nPajak = round(10 * $nSukuBunga / 100);
                        }

                        $nSetoranPlafond = $nBunga - $nPajak;
                        $nDeposito += $nBunga - $nPajak;
                    } else {
                        $vaCair = PerhitunganDeposito::getPencairanDeposito($cRekening, $dTglJadwal);
                        $nBunga = $vaCair['Bunga'];
                        $nPajak = $vaCair['Pajak'];
                    }
                }
                $vaDetail = [
                    'No' => $nNo,
                    'Tgl' => $dTglJadwal,
                    'Faktur' => 'JADWAL PENCAIRAN BUNGA' . $nDeposito,
                    'SetoranPlafond' => $nSetoranPlafond,
                    'PencairanPlafond' => 0,
                    'Nominal' => $nDeposito,
                    'Rate' => $nPersenBunga,
                    'Bunga' => $nBunga,
                    'Pajak' => $nPajak,
                    'Pinalty' => 0,
                    'UserName' => ''
                ];
                $vaDetails[] = $vaDetail;

                $vaTotal = [
                    'TotalSetoranPlafond' => $nTotalSetoran,
                    'TotalPencairanPlafond' => $nTotalPencairan,
                    'TotalBunga' => $nTotalBunga,
                    'TotalPajak' => $nTotalPajak,
                    'TotalPinalty' => $nTotalPinalty
                ];
                $vaTotals[] = $vaTotal;

                $vaResult = [
                    'Rekening' => $cRekening,
                    'Bilyet' => $cNoBilyet !== null ? $cNoBilyet : '',
                    'CIF' => $cCif,
                    'Nama' => GetterSetter::getKeterangan($cCif, 'Nama', 'registernasabah'),
                    'Alamat' => GetterSetter::getKeterangan($cCif, 'Alamat', 'registernasabah'),
                    'GolonganDeposan' => $cGolDeposan,
                    'KetGolDeposan' => GetterSetter::getKeterangan($cGolDeposan, 'Keterangan', 'golongandeposan'),
                    'GolonganDeposito' => $cGolDeposito,
                    'KetGolDeposito' => GetterSetter::getKeterangan($cGolDeposito, 'Keterangan', 'golongandeposito'),
                    'Keterkaitan' => $cKeterkaitan,
                    'KetKeterkaitan' => GetterSetter::getKeterangan($cKeterkaitan, 'Keterangan', 'keterkaitan'),
                    'Nominal' => $nNominal,
                    'JangkaWaktu' => $nJangkaWaktu,
                    'SukuBunga' => $nSukuBunga,
                    'Aro' => $cAro,
                    'CaraPerhitungan' => $cCaraPerhitungan,
                    'TglValuta' => $dTglAwal,
                    'TglJthTmp' => $dJthTmp,
                    'RekeningTabungan' => $cRekeningTabungan,
                    'NamaPenabung' => GetterSetter::getNamaRegisterNasabah($cRekeningTabungan) !== null ? GetterSetter::getNamaRegisterNasabah($cRekeningTabungan) : '',
                    'AlamatPenabung' => GetterSetter::getAlamatRegisterNasabah($cRekeningTabungan),
                    'TotalPencairan' => $nTotalPencairan,
                    'TotalSetoran' => $nTotalSetoran,
                    'TotalBunga' => $nTotalBunga,
                    'TotalPajak' => $nTotalPajak,
                    'TotalPinalty' => $nTotalPinalty,
                    'DetailData' => $vaDetails,
                    'GrandTotal' => $vaTotals
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Kartu Mutasi Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
