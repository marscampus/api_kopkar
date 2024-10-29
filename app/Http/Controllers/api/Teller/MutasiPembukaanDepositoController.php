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
 * Created on Thu Dec 07 2023 - 16:45:48
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\Teller;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\simpananberjangka\Deposito;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiPembukaanDepositoController extends Controller
{
    public function getDataDeposito(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $nReqCount = count($vaRequestData);
        if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID"
            ];
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'getDataDeposito', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $dTgl = GetterSetter::getTglTransaksi();
        $cRekening = $vaRequestData['Rekening'];
        $vaData = DB::table('deposito as d')
            ->select(
                'd.GolonganDeposito',
                'g.Keterangan',
                'g.Lama',
                'd.JthTmp',
                'd.RekeningTabungan',
                'd.TempNominal'
            )
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongandeposito as g', 'g.Kode', '=', 'd.GolonganDeposito')
            ->where('d.Rekening', '=', $cRekening)
            ->first();
        if ($vaData) {
            $cGolDeposito = $vaData->GolonganDeposito;
            $cKetGolDeposito = $vaData->Keterangan;
            $nJangkaWaktu = $vaData->Lama;
            $dJthTmp = $vaData->JthTmp;
            $cRekTabungan = $vaData->RekeningTabungan;
            $nNominal = $vaData->TempNominal;
            if (!empty($cRekTabungan)) {
                $cKodeTabungan = GetterSetter::getKode($cRekening);
                $cNamaTabungan = GetterSetter::getKeterangan($cKodeTabungan, 'Nama', 'registernasabah');
                $nSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($cRekTabungan, $dTgl);
            } else {
                $cKodeTabungan = "";
                $cNamaTabungan = "";
                $nSaldoTabungan = "";
            }

            $vaResult = [
                'Faktur' => GetterSetter::getLastFaktur("DP", 6),
                'GolDeposito' => $cGolDeposito,
                'KetGolDeposito' => $cKetGolDeposito,
                'Nominal' => $nNominal,
                'RekTabungan' => $cRekTabungan,
                'NamaTabungan' => $cNamaTabungan,
                'SaldoTabungan' => $nSaldoTabungan,
                'JangkaWaktu' => $nJangkaWaktu,
                'JthTmp' => Carbon::parse($dJthTmp)->format('d-m-Y')
            ]; // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'getDataDeposito', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "Rekening Simpanan Berjangka Tidak Ditemukan!"
            ];
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'getDataDeposito', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening Tabungan Tidak Ditemukan!'
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $dTgl = GetterSetter::getTglTransaksi();
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 3 || $nReqCount > 3) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $vaData = DB::table('deposito')
                ->select(
                    'Tgl',
                    'JthTmp',
                    'TempNominal',
                    'NamaNasabah'
                )
                ->where('Rekening', $cRekening)
                ->first();
            if ($vaData) {
                $dJthtmp = $vaData->JthTmp;
                $nNominal = $vaData->TempNominal;
                $nNama = $vaData->NamaNasabah;
            }
            $cKeterangan = "Pembukaan Deposito an. " . $nNama;
            $cCaraSetoran = $vaRequestData['CaraSetoran'];
            $cRekAkuntansi = $vaRequestData['RekeningAkutansi'];
            $cFaktur = GetterSetter::getLastFaktur("DP", 6);
            Deposito::where('Rekening', '=', $cRekening)
                ->update([
                    'Nominal' => $nNominal,
                    'TempNominal' => 0
                ]);
            Upd::updMutasiDeposito(true, '1', $cFaktur, $cRekening, '', $dTgl, $dJthtmp, $nNominal, 0, 0, 0, 0, 0, 0, $cCaraSetoran, $cKeterangan, true, 0, 0, $cRekAkuntansi);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            GetterSetter::setLastFaktur("DP");
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public function cetakValidasi(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 2 || $nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Pembukaan Simpanan Rekening', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $cFaktur = $vaRequestData['Faktur'];
            $vaData = DB::table('mutasideposito as m')
                ->select(
                    'r.Nama',
                    'm.SetoranPlafond',
                    'm.PencairanPlafond',
                    'm.Bunga',
                    'm.Pajak',
                    'm.Pinalty',
                    'd.Tgl as TglValuta',
                    'm.JthTmp',
                    'd.RekeningLama',
                    'm.Tgl',
                    'd.Nominal',
                    'm.UserName',
                    'm.Faktur'
                )
                ->leftJoin('deposito as d', 'd.Rekening', '=', 'm.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->where('m.Rekening', '=', $cRekening)
                ->where('m.Faktur', '=', $cFaktur)
                ->first();
            $vaResult = [];
            if ($vaData) {
                $nNominal = $vaData->Nominal;
                $nSetoranPlafond = $vaData->SetoranPlafond;
                $nPencairanPlafond = $vaData->PencairanPlafond;
                $nBunga = $vaData->Bunga;
                $nPajak = $vaData->Pajak;
                $nPinalty = $vaData->Pinalty;
                $dTglValuta = $vaData->TglValuta;
                $dJthTmp = $vaData->JthTmp;
                $dTgl = $vaData->Tgl;
                $nSukuBunga = GetterSetter::getRate($dTgl, $cRekening) . "%";
                $nJumlah = $nPencairanPlafond + $nBunga - $nPajak - $nPinalty;
                $nJmlPencairan = $nNominal + $nBunga - $nPajak - $nPinalty;
                $nJmlJasa = $nBunga - $nPajak;

                $ket = '';

                if ($nBunga > 0) {
                    $ket .= "Bunga " . Func::formatCurrency($nBunga);
                }

                if ($nPajak > 0) {
                    if ($ket) {
                        $ket .= " ";
                    }
                    $ket .= "Pajak " . Func::formatCurrency($nPajak);
                }
                if ($nSetoranPlafond > 0) {
                    $ket = "Setoran Simpanan Berjangka " . Func::formatCurrency($nSetoranPlafond);
                }

                if ($nPencairanPlafond > 0) {
                    $ket = "Pencairan Simpanan Berjangka " . Func::formatCurrency($nPencairanPlafond);
                }
                if ($nPinalty > 0) {
                    if ($ket) {
                        $ket .= " ";
                    }
                    $ket .= " Pinalty Simpanan Berjangka " . Func::formatCurrency($nPinalty);
                }

                $vaResult = [
                    'Faktur' => $vaData->Faktur,
                    'UserName' => $vaData->UserName,
                    'Rekening' => $cRekening,
                    'Nama' => $vaData->Nama,
                    'Ket' => $ket,
                    'Nominal' => $nNominal,
                    'SetoranPlafond' => $nSetoranPlafond,
                    'PencairanPlafond' => $nPencairanPlafond,
                    'Bunga' => $nBunga,
                    'Pajak' => $nPajak,
                    'Pinalty' => $vaData->Pinalty,
                    'RekeningLama' => $vaData->RekeningLama,
                    'TglValuta' => $dTglValuta,
                    'JthTmp' => $dJthTmp,
                    'Tgl' => $dTgl,
                    'SukuBunga' => $nSukuBunga,
                    'Jumlah' => $nJumlah,
                    'JmlPencairan' => $nJmlPencairan,
                    'JmlJasa' => $nJmlJasa,
                    'Terbilang' => $nSetoranPlafond > 0 ? Func::Terbilang(round(Func::String2Number($nNominal), 0)) : Func::Terbilang(round(Func::String2Number($nJumlah), 0))
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Mutasi Pembukaan Simpanan Berjangka', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
