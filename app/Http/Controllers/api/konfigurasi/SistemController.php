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
 * Created on Fri Jan 26 2024 - 09:31:23
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\konfigurasi;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SistemController extends Controller
{
    public function getDataGeneral()
    {
        $cSandiBI = GetterSetter::getDBConfig("msSandiBank");
        $cSandiKantor = GetterSetter::getDBConfig("msSandiKantor");
        $cCabang = GetterSetter::getDBConfig("msKodeCabang");
        $cNamaCabang = GetterSetter::getKeterangan($cCabang, 'Keterangan', 'cabang');
        $cRekeningKas = GetterSetter::getDBConfig("msRekeningKas");
        $cNamaRekeningKas = GetterSetter::getKeterangan($cRekeningKas, 'Keterangan', 'rekening');
        $cRekeningKasTeller = GetterSetter::getDBConfig("msRekeningKasTeller");
        $cNamaRekeningKasTeller = GetterSetter::getKeterangan($cRekeningKasTeller, 'Keterangan', 'rekening');
        $cRekeningPB = GetterSetter::getDBConfig("msRekeningPB");
        $cNamaRekeningPB = GetterSetter::getKeterangan($cRekeningPB, 'Keterangan', 'rekening');
        $cRekeningLaba = GetterSetter::getDBConfig("msRekeningLaba");
        $cNamaRekeningLaba = GetterSetter::getKeterangan($cRekeningPB, 'Keterangan', 'rekening');
        $cRekeningTahunLalu = GetterSetter::getDBConfig("msRekeningLabaTahunLalu");
        $cNamaRekeningTahunLalu = GetterSetter::getKeterangan($cRekeningTahunLalu, 'Keterangan', 'rekening');
        $cRekeningPNO = GetterSetter::getDBConfig("msRekeningPNO");
        $cNamaRekeningPNO = GetterSetter::getKeterangan($cRekeningPNO, 'keterangan', 'rekening');
        $cRekeningBNO = GetterSetter::getDBConfig("msRekeningBNO");
        $cNamaRekeningBNO = GetterSetter::getKeterangan($cRekeningBNO, 'keterangan', 'rekening');
        $ckodeGolonganNasabahTerkait = GetterSetter::getDBConfig('msGolNasabahTerkait');
        $cNamaGolonganNasabahTerkait = GetterSetter::getKeterangan($ckodeGolonganNasabahTerkait, 'keterangan', 'golonganNasabah');
        $nSaldoMinimumKenaPajak = GetterSetter::getDBConfig('msSaldoMinKenaPajak');
        $nTarifPajak = GetterSetter::getDBConfig('msTarifPajak');
        $nDenda = GetterSetter::getDBConfig('msDenda');
        $nHariTelat = GetterSetter::getDBConfig('msHariTelat');
        $cCaraPencairan = GetterSetter::getDBConfig('msCaraPencairan');

        $vaArray = [
            "sandiBI" => $cSandiBI,
            "sandiKantor" => $cSandiKantor,
            "kodeCabang" => $cCabang,
            "ketCabang" => $cNamaCabang,
            "rekKas" => $cRekeningKas,
            "ketRekKas" => $cNamaRekeningKas,
            "rekKasTeller" => $cRekeningKasTeller,
            "ketRekKasTeller" => $cNamaRekeningKasTeller,
            "rekPB" => $cRekeningPB,
            "ketRekPB" => $cNamaRekeningPB,
            "rekLaba" => $cRekeningLaba,
            "ketRekLaba" => $cNamaRekeningLaba,
            "rekTahunLalu" => $cRekeningTahunLalu,
            "ketRekTahunLalu" => $cNamaRekeningTahunLalu,
            "rekPNO" => $cRekeningPNO,
            "ketRekPNO" => $cNamaRekeningPNO,
            "rekBNO" => $cRekeningBNO,
            "ketRekBNO" => $cNamaRekeningBNO,
            "golNasabah" => $ckodeGolonganNasabahTerkait,
            "ketGolNasabah" => $cNamaGolonganNasabahTerkait,
            "saldoMinimumKenaPajak" => $nSaldoMinimumKenaPajak,
            "tarifPajak" => $nTarifPajak,
            "tarifDenda" => $nDenda,
            "hariTelat" => $nHariTelat,
            "caraPencairan" => $cCaraPencairan
        ];
        return response()->json($vaArray);
    }

    public function getDataProduk()
    {
        $nPembulatanAgsPokok = GetterSetter::getDBConfig('msPembulatanAngsuranPokok');
        $nPembulatanAgsBunga = GetterSetter::getDBConfig('msPembulatanAngsuran');
        $nPembulatanFrekuensi = GetterSetter::getDBConfig('msPembulatanFrekuensi');

        $vaArray = [
            'pembulatanAngsPokok' => $nPembulatanAgsPokok,
            'pembulatanAngsBunga' => $nPembulatanAgsBunga,
            'pembulatanFrekuensi' => $nPembulatanFrekuensi
        ];
        return response()->json($vaArray);
    }

    public function getDataAntarKantor()
    {
        $cRekeningAKA = GetterSetter::getDBConfig('msRekeningAKA');
        $cKetRekeningAKA = GetterSetter::getKeterangan($cRekeningAKA, 'Keterangan', 'rekening');
        $cRekeningAKP = GetterSetter::getDBConfig('msRekeningAKP');
        $cKetRekeningAKP = GetterSetter::getKeterangan($cRekeningAKP, 'Keterangan', 'rekening');

        $vaArray = [
            'rekAKA' => $cRekeningAKA,
            'ketRekAKA' => $cKetRekeningAKA,
            'rekAKP' => $cRekeningAKP,
            'ketRekAKP' => $cKetRekeningAKP
        ];
        return response()->json($vaArray);
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $user =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            // Save Data General
            GetterSetter::setDBConfig("msSandiBank", $vaRequestData['sandiBI']);
            GetterSetter::setDBConfig("msSandiKantor", $vaRequestData['sandiKantor']);
            GetterSetter::setDBConfig("msKodeCabang", $vaRequestData['kodeCabang']);
            GetterSetter::setDBConfig("msRekeningKas", $vaRequestData['rekKas']);
            GetterSetter::setDBConfig("msRekeningKasTeller", $vaRequestData['rekKasTeller']);
            GetterSetter::setDBConfig("msRekeningPB", $vaRequestData['rekPB']);
            GetterSetter::setDBConfig("msRekeningLaba", $vaRequestData['rekLaba']);
            GetterSetter::setDBConfig("msRekeningLabaTahunLalu", $vaRequestData['rekTahunLalu']);
            GetterSetter::setDBConfig("msRekeningPNO", $vaRequestData['rekPNO']);
            GetterSetter::setDBConfig("msRekeningBNO", $vaRequestData['rekBNO']);
            // GetterSetter::setDBConfig("msGolNasabahTerkait", $vaRequestData['golNasabah']);
            GetterSetter::setDBConfig("msSaldoMinKenaPajak", $vaRequestData['saldoMinimumKenaPajak']);
            GetterSetter::setDBConfig("msTarifPajak", $vaRequestData['tarifPajak']);
            GetterSetter::setDBConfig("msDenda", $vaRequestData['tarifDenda']);
            GetterSetter::setDBConfig("msHariTelat", $vaRequestData['hariTelat']);
            GetterSetter::setDBConfig("msCaraPencairan", $vaRequestData['caraPencairan']);
            // Save Data Produk
            GetterSetter::setDBConfig('msPembulatanAngsuran', $vaRequestData['pembulatanAngsBunga']);
            GetterSetter::setDBConfig('msPembulatanAngsuranPokok', $vaRequestData['pembulatanAngsPokok']);
            GetterSetter::setDBConfig('msPembulatanFrekuensi', $vaRequestData['pembulatanFrekuensi']);
            // Save Data Antar Kantor
            // JIKA REQUEST SUKSES
            GetterSetter::setDBConfig('msRekeningAKA', $vaRequestData['rekAKA']);
            GetterSetter::setDBConfig('msRekeningAKP', $vaRequestData['rekAKP']);
            $retVal = ["status" => "00", "message" => "SUKSES"];
            Func::writeLog('Konfigurasi Sistem', 'store', $vaRequestData, $retVal, $user);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $retVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                    // tambahkan informasi lainnya yang ingin Anda sertakan
                ]
            ];
            Func::writeLog('Konfigurasi Sistem', 'store', $vaRequestData, $th, $user);
            // return response()->json($retVal);
            return response()->json(['status' => 'error']);
        }
    }
}
