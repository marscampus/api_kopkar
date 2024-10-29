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
 * Created on Thu Dec 14 2023 - 16:39:52
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Helpers;

use App\Models\fun\Angsuran;
use App\Models\fun\BukuBesar;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\master\KodeTransaksi;
use App\Models\master\Rekening;
use App\Models\pinjaman\Debitur;
use App\Models\simpananberjangka\Deposito;
use App\Models\teller\MutasiAnggota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Upd
{
    const msTabungan = 1;
    const msRealisasiKredit = 2;
    const msTitipanAngsuran = 4;
    const msAngsuranKredit = 5;
    const msKasKeluar = 5;
    const msKasMasuk = 6;
    const msDeposito = 0;
    const msRekeningRRP = 8;
    const msJurnalLain = 9;
    const msPenyusutanAktiva = 10;
    const msAmortisasiProvisi = 11;
    const msPenambahanPlafond = 12;
    const msSaldoAwalRekening = 13;
    const msCadanganBungaTabungan = 14;
    const msCadanganBungaDeposito = 15;
    const msAkhirTahun = 16;
    const msAdendumKredit = 17;
    const msPerubahanGolonganKredit = 18;
    const msMutasiWO = 19;
    const msMutasiAnggota = 20;

    public static function updMutasiTabungan($cFaktur, $dTgl, $cRekening, $cKodeTransaksi = '', $cKeterangan, $nJumlah, $cCabangEntry = '', $bUpdBukuBesar = true, $cUsername = '')
    {
        if ($nJumlah != 0) {
            $cCabangEntry = GetterSetter::getDBConfig('msKodeCabang');
            $dTgl = Func::Date2String($dTgl);

            // Check Jenis Transaksi Debet/Kredit
            $nDebet = $nJumlah;
            $nKredit = 0;
            $cDK = 'D';
            if (substr($cFaktur, 0, 1) == 'R') {
                $vaData = DB::table('angsuran')
                    ->select('UserName')
                    ->where('Faktur', '=', $cFaktur)
                    ->groupBy('Faktur')
                    ->first();
                if ($vaData) {
                    $cUsername = $vaData->UserName;
                }
            }
            $vaData2 = DB::table('kodetransaksi')
                ->select('DK')
                ->where('Kode', '=', $cKodeTransaksi)
                ->first();
            if ($vaData2) {
                $cDK = $vaData2->DK;
            }
            if ($cDK == 'K') {
                $nKredit = $nJumlah;
                $nDebet = 0;
            }
            $vaArray = [
                'Faktur' => $cFaktur,
                'Tgl' => $dTgl,
                'Rekening' => $cRekening,
                'KodeTransaksi' => $cKodeTransaksi,
                'Keterangan' => $cKeterangan,
                'Jumlah' => $nJumlah,
                'CabangEntry' => $cCabangEntry,
                'DK' => $cDK,
                'Debet' => $nDebet,
                'Kredit' => $nKredit,
                'UserName' => $cUsername,
                'DateTime' => Carbon::now()
            ];
            MutasiTabungan::create($vaArray);
            // if ($bUpdBukuBesar) {
            //     self::updRekeningMutasiTabungan($cFaktur);
            // }
        }
    }

    public static function updRekeningMutasiTabungan($cFaktur)
    {
        $cStatus = self::msTabungan;
        // self::deleteBukuBesar($cStatus, $cFaktur);
        $vaRekening = [
            "Debet" => "",
            "Kredit" => ""
        ];
        $vaCabang = [
            "Debet" => "",
            "Kredit" => ""
        ];
        $vaData = DB::table('mutasitabungan as m')
            ->select(
                't.CabangEntry as CabangNasabah',
                'm.Faktur',
                'm.DK',
                'm.Keterangan',
                'g.Rekening as RekeningTabungan',
                'g.RekeningBunga',
                'm.CabangEntry',
                'm.Tgl',
                'm.Jumlah',
                'g.RekeningCadanganPajak',
                'k.Rekening as RekeningKodeTransaksi',
                'k.DK',
                'k.Kas',
                'm.KodeTransaksi',
                'm.UserName'
            )
            ->leftJoin('tabungan as t', 't.Rekening', '=', 'm.Rekening')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('kodetransaksi as k', 'k.Kode', '=', 'm.KodeTransaksi')
            ->where('m.Faktur', $cFaktur)
            ->addSelect(DB::raw("IFNULL((select CabangEntry from tabungan_cabang where Rekening = t.Rekening and dTgl <= m.Tgl order by dTgl desc limit 1 ), t.CabangEntry) as CabangNasabah"))
            ->get();
        foreach ($vaData as $d) {
            $dTgl = $d->Tgl;
            $cCabangNasabah = $d->CabangNasabah;
            $cCabangEntry = $d->CabangEntry;
            $cCabangDebet = $cCabangEntry;
            $cCabangKredit = $cCabangNasabah;
            $cLawan = "Debet";
            $vaRekening['Kredit'] = $d->RekeningTabungan;
            if ($d->DK == "D") {
                $cCabangDebet = $cCabangNasabah;
                $cCabangKredit = $cCabangEntry;
                $cLawan = "Kredit";
                $vaRekening['Debet'] = $d->RekeningTabungan;
            }
            $vaRekening[$cLawan] = $d->RekeningKodeTransaksi;
            if ($d->Kas == 'K') {
                $vaRekening[$cLawan] = GetterSetter::getKasTeller($d->UserName, $d->Tgl);
            }
            if ($d->KodeTransaksi == GetterSetter::getDBConfig("msKodeBungaTabungan")) {
                $vaRekening[$cLawan] = $d->RekeningBunga;
            }
            if (substr($cFaktur, 0, 2) == "PB") {
                $vaRekening[$cLawan] = GetterSetter::getDBConfig("msRekeningPB");
            }
            if ($d->Jumlah > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangDebet, $dTgl, $vaRekening['Debet'], $d->Keterangan, $d->Jumlah, 0, $d->UserName, $d->Kas);
                self::updBukuBesar($cStatus, $cFaktur, $cCabangKredit, $dTgl, $vaRekening['Kredit'], $d->Keterangan, 0, $d->Jumlah, $d->UserName, $d->Kas);
            }
        }
    }

    public static function updMutasiAnggota($cFaktur, $dTgl, $cRekening, $cKeterangan, $cDK, $nMutasiPokok = 0, $nMutasiWajib = 0, $bUpdBukuBesar = true, $cKas = 'K', $cRekTabungan = '', $cRekPB = '', $cUsername)
    {
        $nJumlah = $nMutasiPokok + $nMutasiWajib;
        if ($nJumlah !== 0) {
            $nDebetPokok = 0;
            $nKreditPokok = 0;
            $nDebetWajib = 0;
            $nKreditWajib = 0;
            if ($cDK == 'K') {
                $nDebetPokok = 0;
                $nKreditPokok = $nMutasiPokok;
                $nDebetWajib = 0;
                $nKreditWajib = $nMutasiWajib;
            }
            if ($cDK == 'D') {
                $nDebetPokok = $nMutasiPokok;
                $nKreditPokok = 0;
                $nDebetWajib = $nMutasiWajib;
                $nKreditWajib = 0;
            }
            $vaArray = [
                'Faktur' => $cFaktur,
                'Tgl' => $dTgl,
                'Kode' => $cRekening,
                'Keterangan' => $cKeterangan,
                'Jumlah' => $nJumlah,
                'DK' => $cDK,
                'DebetPokok' => $nDebetPokok,
                'KreditPokok' => $nKreditPokok,
                'DebetWajib' => $nDebetWajib,
                'KreditWajib' => $nKreditWajib,
                'UserName' => $cUsername,
                'DateTime' => Carbon::now(),
                'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                'KAS' => $cKas,
                'RekeningTabungan' => $cRekTabungan,
                'RekeningPB' => $cRekPB
            ];
            MutasiAnggota::create($vaArray);
            // if ($bUpdBukuBesar) {
            // $self::updRekeningMutasiAnggota($FAKTUR);
            // }
            // if ($KAS = 'T') {
            // $penarikanPB = GetterSetter::getDBConfig('msKodePenarikanPB');
            // self::updMutasiTabungan($FAKTUR, $TGL, $REKTABUNGAN, $penarikanPB, 'Mutasi Anggota ' . $KETERANGAN, $nJumlah);
            // }
        }
    }

    public static function updRekeningMutasiAnggota($cFaktur)
    {
        $cStatus = self::msMutasiAnggota;
        BukuBesar::where('Faktur', $cFaktur)
            ->where('cStatus', $cStatus)->delete();
        $vaData = DB::table('mutasianggota as m')
            ->select(
                'm.UserName',
                'm.CabangEntry',
                'm.Faktur',
                'm.DK',
                'm.Keterangan',
                'g.RekeningSimpanan',
                'm.Tgl',
                'm.Jumlah',
                'm.DebetPokok',
                'm.KreditPokok',
                'm.DebetWajib',
                'm.KreditWajib',
                'm.UserName',
                'm.Kas',
                DB::raw('LEFT(r.kode, 3) AS CabangNasabah')
            )
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'm.kode')
            ->leftJoin('golongansimpanan as g', 'g.Kode', '=', 'm.GolonganAnggota')
            ->where('m.Faktur', $cFaktur)
            ->get();

        foreach ($vaData as $d) {
            $dTgl = $d->Tgl;
            $cCabangEntry = $d->CabangEntry;
            $cCabangNasabah = $d->CabangNasabah;
            $vaCab = GetterSetter::getRekeningAntarKantor($cCabangEntry, $cCabangNasabah);
            $cRekeningAKP = $vaCab['RekeningAKP'];
            $cRekeningAKA = $vaCab['RekeningAKA'];
            $cRekeningAKEntry = $vaCab['RekeningAKEntry'];
            $cRekeningAKLawan = $vaCab['RekeningAKLawan'];
            $cRekSimPokok = Func::getRekeningLawan('RekeningSimpanan', 'golongansimpanan', "Kode = '01'");
            $cRekSimWajib = Func::getRekeningLawan('RekeningSimpanan', 'golongansimpanan', "Kode = '02'");
            $cUsername = $d->UserName;
            $cRekKas = GetterSetter::getKasTeller($cUsername, $dTgl);
            if ($d->Kas == 'P') {
                $cRekKas = $d->RekeningPB;
                if (empty($cRekKas)) {
                    $cRekKas = GetterSetter::getDBConfig('msRekeningPB');
                }
            }

            $cRekDebetPokok = "";
            $cRekKreditPokok = "";
            $cRekDebetWajib = "";
            $cRekKreditWajib = "";
            $cCabangDebetP = GetterSetter::getDBConfig('msKodeCabang');
            $cCabangKreditP = GetterSetter::getDBConfig('msKodeCabang');
            $cCabangDebetW = GetterSetter::getDBConfig('msKodeCabang');
            $cCabangKreditW = GetterSetter::getDBConfig('msKodeCabang');
            $nJumlahPokok = 0;
            $nJumlahWajib = 0;
            $nDebetPokok = $d->DebetPokok;
            $nKreditPokok = $d->KreditPokok;
            $nDebetWajib = $d->DebetWajib;
            $nKreditWajib = $d->KreditWajib;
            if ($nDebetPokok > 0) {
                $cRekDebetPokok = $cRekSimPokok;
                $cRekKreditPokok = $cRekKas;
                $nJumlahPokok = $nDebetPokok;
                $cCabangDebetP = $cCabangNasabah;
                $cCabangKreditP = $cCabangEntry;
                $cRekeningAKP = $vaCab['RekeningAKA'];
                $cRekeningAKA = $vaCab['RekeningAKP'];
            }

            if ($nKreditPokok > 0) {
                $cRekDebetPokok = $cRekKas;
                $cRekKreditPokok = $cRekSimPokok;
                $nJumlahPokok = $nKreditPokok;
                $cCabangDebetP = $cCabangEntry;
                $cCabangKreditP = $cCabangNasabah;
            }

            if ($nDebetWajib > 0) {
                $cRekDebetWajib = $cRekSimWajib;
                $cRekKreditWajib = $cRekKas;
                $nJumlahWajib = $nDebetWajib;
                $cCabangDebetW = $cCabangNasabah;
                $cCabangKreditW = $cCabangEntry;
                $cRekeningAKP = $vaCab['RekeningAKA'];
                $cRekeningAKA = $vaCab['RekeningAKP'];
            }

            if ($nKreditWajib > 0) {
                $cRekDebetWajib = $cRekKas;
                $cRekKreditWajib = $cRekSimWajib;
                $nJumlahWajib = $nKreditWajib;
                $cCabangDebetW = $cCabangEntry;
                $cCabangKreditW = $cCabangNasabah;
            }
            // Simpanan Pokok
            if ($nJumlahPokok > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangDebetP, $dTgl, $cRekDebetPokok, $d->Keterangan, $nJumlahPokok, 0, $d->UserName, $d->KAS);
                self::updBukuBesar($cStatus, $cFaktur, $cCabangKreditP, $dTgl, $cRekKreditPokok, $d->Keterangan, 0, $nJumlahPokok, $d->UserName, $d->KAS);
            }
            // Simpanan Wajib
            if ($nJumlahWajib > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangDebetW, $dTgl, $cRekDebetWajib, $d->Keterangan, $nJumlahWajib, 0, $d->UserName, $d->KAS);
                self::updBukuBesar($cStatus, $cFaktur, $cCabangKreditW, $dTgl, $cRekKreditWajib, $d->Keterangan, 0, $nJumlahWajib, $d->UserName, $d->KAS);
            }
        }
    }

    public static function updBukuBesar($cStatus, $cFaktur, $cCabang, $dTgl, $cRekening, $cKeterangan, $nDebet = 0, $nKredit = 0, $cUsername, $cKas = 'N')
    {
        $va = GetterSetter::getUrutFaktur($cFaktur);
        $cKey = substr($cFaktur, 0, 2);
        $rekeningPB = GetterSetter::getDBConfig('msRekeningPB');
        if ($cUsername == '') {
            $cUsername = $va['USERNAME'];
        }
        if (substr($cFaktur, 0, 2) == 'WO') {
            $cUsername = $va['USERNAME'];
        }
        if (substr($cFaktur, 0, 2) !== 'TH' || substr($cFaktur, 0, 2) !== 'JR') {
        }

        if (substr($cFaktur, 0, 2) == 'BT') {
            $cUsername = $va['USERNAME'];
        }

        if (substr($cFaktur, 0, 3) == 'BPR' || substr($cFaktur, 0, 3) == 'ANG') {
            $cCabang = GetterSetter::getCabang($cUsername, $dTgl);
        }
        $cKeterangan = str_replace("'", "", $cKeterangan);
        if ($nDebet !== 0 || $nKredit !== 0) {
            if ($nDebet < 0) {
                $nKredit = abs($nDebet);
                $nDebet = 0;
            }
            if ($nKredit < 0) {
                $nDebet = abs($nKredit);
                $nKredit = 0;
            }
            $vaData = Rekening::where('Kode', $cRekening)->where('Cabang', '<>', '')
                ->first();
            if ($vaData) {
                $cCabang = $vaData->Cabang;
            }
            $vaArray = [
                'Status' => $cStatus,
                'Urut' => $va['ID'],
                'Cabang' => $cCabang,
                'Faktur' => $cFaktur,
                'Tgl' => $dTgl,
                'Rekening' => $cRekening,
                'Keterangan' => $cKeterangan,
                'Debet' => $nDebet,
                'Kredit' => $nKredit,
                'UserName' => $cUsername,
                'DateTime' => Carbon::now(),
                'Kas' => $cKas
            ];
            BukuBesar::create($vaArray);
        }
    }

    public static function updPembukaanDeposito($cFaktur, $dTgl, $cRekening, $cKeterangan, $nNominal, $cCaraSetoran, $cCaraOtorisasi = '1', $cRekeningAkutansi = '')
    {
        $vaArray = [
            'Nominal' => $nNominal,
            'StatusOtorisasi' => $cCaraOtorisasi
        ];
        Deposito::where('Rekening', $cRekening)->update($vaArray);
        if ($cCaraSetoran == 'T') {
            $vaData = Deposito::where('Rekening', $cRekening)
                ->first();
            if ($vaData) {
                $cRekTabungan = $vaData->RekeningTabungan;
                $cPenarikanPB = GetterSetter::getDBConfig('msKodePenarikanPB');
                // self::updMutasiTabungan($cFaktur, $dTgl, $cRekTabungan, $cPenarikanPB, $cKeterangan, $nNominal, '', true);
            }
        }
    }

    public static function updMutasiDeposito($bPembukaan = true, $cJenis = '1', $cFaktur, $cRekening, $cCabangEntry = '', $dTgl, $dJthTmp, $nSetoranPlafond = 0, $nPencairanPlafond = 0, $nBunga = 0, $nPajak = 0, $nKoreksiBunga = 0, $nKoreksiPajak = 0, $nPinalty = 0, $cKas = 'K', $cKeterangan = '', $bUpdBukuBesar = true, $nAccrual = 0, $nFee = 0, $cRekeningAkuntansi = '', $cUsername = '')
    {
        try {
            $bungaNetto = $nBunga - $nPajak;
            if ($cUsername == '') {
                $cUsername = GetterSetter::getDBConfig('msKodeCabang');
            } else {
                $cUsername = $cUsername;
            }
            if ($dJthTmp == '') {
                $dJthTmp = '1900-12-31';
            } else {
                $dJthTmp = $dJthTmp;
            }
            // if (empty($cCabangEntry)) {
            $cCabangEntry = GetterSetter::getDBConfig('msKodedCabang');
            // }
            if ($cKas == 'A') {
                $nSetoranPlafond = $bungaNetto;
            }
            $vaArray = [
                'Jenis' => $cJenis,
                'Faktur' => $cFaktur,
                'Rekening' => $cRekening,
                'CabangEntry' => $cCabangEntry,
                'Tgl' => $dTgl,
                'Jthtmp' => $dJthTmp,
                'SetoranPlafond' => $nSetoranPlafond,
                'PencairanPlafond' => $nPencairanPlafond,
                'Bunga' => $nBunga,
                'Pajak' => $nPajak,
                'KoreksiPajak' => $nKoreksiPajak,
                'KoreksiBunga' => $nKoreksiBunga,
                'Pinalty' => $nPinalty,
                'Kas' => $cKas,
                'UserName' => $cUsername,
                'DateTime' => Carbon::now(),
                'Accrual' => $nAccrual,
                'Fee' => $nFee,
                'RekeningAkuntansi' => $cRekeningAkuntansi
            ];
            MutasiDeposito::create($vaArray);
            // if ($bUpdBukuBesar) {
            // self::updRekMutasiDeposito($cFaktur);
            // }
            if ($bPembukaan == false) {
                if ($cKas == 'T') {
                    $vaData = DB::table('deposito')
                        ->select('RekeningTabungan')
                        ->where('Rekening', '=', $cRekening)
                        ->first();
                    if ($vaData) {
                        $cRekTabungan = $vaData->RekeningTabungan;
                        $cSetoranPB = GetterSetter::getDBConfig('msKodeSetoranPB');
                        $cPenarikanPB = GetterSetter::getDBConfig('msKodePenarikanPB');
                        if ($nPencairanPlafond > 0) {
                            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTabungan, $cSetoranPB, $cKeterangan, $nPencairanPlafond, '', true);
                            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTabungan, $cPenarikanPB, $cKeterangan, $nPinalty, '', true);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public static function updRekMutasiDeposito($cFaktur)
    {
        $cRekeningKas = '';
        $cStatus = self::msDeposito;
        self::deleteBukuBesar($cStatus, $cFaktur);
        $vaData
            = DB::table('mutasideposito as m')
            ->select(
                'm.Rekening',
                'm.Kas',
                'm.ID',
                'm.SetoranPlafond',
                'm.PencairanPlafond',
                'm.Bunga',
                'm.Pajak',
                'm.KoreksiBunga',
                'm.Pinalty',
                'm.Fee',
                'm.DTitipan',
                'm.KTitipan',
                'm.Tgl',
                'r.Nama',
                'd.RekeningTabungan',
                'm.UserName',
                'm.CabangEntry',
                'm.Accrual',
                'g.RekeningBunga',
                'g.RekeningPajakBunga',
                'g.CadanganBunga',
                'g.RekeningPinalti',
                'g.RekeningAkuntansi',
                'g.RekeningAccrual',
                'g.RekeningFeeDeposito',
                'm.rekeningakuntansi as RekeningPemindahBukuan'
            )
            ->leftJoin('deposito as d', 'd.rekening', '=', 'm.Rekening')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->leftJoin('golongandeposito as g', function ($join) {
                $join->on('g.Kode', '=', DB::raw('IFNULL((select golongandeposito from deposito_perubahangolongan where Rekening = d.Rekening and tgl <= m.Tgl order by tgl desc limit 1), d.GolonganDeposito)'));
            })->where('m.faktur', $cFaktur)
            ->addSelect(DB::raw("IFNULL((select CabangEntry from deposito_cabang where Rekening = d.Rekening and tgl <= m.Tgl order by tgl desc limit 1), d.CabangEntry) as CabangNasabah"))->get();
        foreach ($vaData as $d) {
            $rekening = $d->Rekening;
            if ($d->Kas == 'K') {
                $cRekeningKas = GetterSetter::getRekeningKasTeller($d->UserName, $d->Tgl);
            } else if ($d->Kas == 'P') {
                $cRekeningKas = GetterSetter::getDBConfig("msRekeningPB");
                if ($d->RekeningPemindahBukuan <> '') {
                    $cRekeningKas = $d->RekeningPemindahBukuan;
                }
            } else if ($d->Kas == 'C') {
                $cRekeningKas = $d->CadanganBunga;
            } else if ($d->Kas == 'A') {
                $cRekeningKas = $d->RekeningAkuntansi;
            } else {
                $crekeningKas = GetterSetter::getDBConfig("msRekeningPB");
            }
            $nTotal = $d->SetoranPlafond - $d->PencairanPlafond - $d->Bunga + $d->Pajak + $d->Pinalty;
            $cDKas = $nTotal > 0 ? $nTotal : 0;
            $cKKas = $nTotal < 0 ? $nTotal * -1 : 0;
            $cCabangEntry = $d->CabangEntry;
            $cCabangNasabah = $d->CabangNasabah;
            $d->Accrual = 0;
            $nBunga = $d->Bunga - $d->Accrual;
            if ($cDKas > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $d->Tgl, $cRekeningKas, 'Setoran Dep. [' . $rekening . '] ' . $d->Nama, $cDKas, 0, $d->UserName, $d->Kas);
            }
            if ($d->SetoranPlafond > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningAkuntansi, 'Setoran Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->SetoranPlafond, $d->UserName, $d->Kas);
            }

            if ($d->PencairanPlafond > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningAkuntansi, 'Pencairan Dep. [' . $rekening . '] ' . $d->Nama, $d->PencairanPlafond, 0, $d->UserName, $d->Kas);
            }

            if ($cKKas > 0 && $d->PencairanPlafond > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $cRekeningKas, 'Pencairan Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->PencairanPlafond, $d->UserName, $d->Kas);
            }

            if ($nBunga > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningBunga, 'Bunga Dep. [' . $rekening . '] ' . $d->Nama, $nBunga, 0, $d->UserName, $d->Kas);
                self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $d->Tgl, $cRekeningKas, 'Bunga Dep. [' . $rekening . '] ' . $d->Nama, 0, $nBunga, $d->UserName, $d->Kas);
            }

            if ($d->Accrual > 0) {
                self::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningAccrual, 'Accrual Dep. [' . $rekening . '] ' . $d->Nama, $d->Accrual, 0, $d->UserName, $d->Kas);
            }

            if ($d->Pajak > 0) {
                Upd::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningPajakBunga, 'Pajak Bunga Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->Pajak, $d->UserName, $d->Kas);
            }

            if ($d->Pinalty) {
                Upd::updBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $d->Tgl, $d->RekeningPinalti, 'Pinalty Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->Pinalty, $d->UserName, $d->Kas);
            }
        }
    }

    public static function updPencairanDeposito($cRekening, $dTglCair)
    {
        $vaArray = [
            'TglCair' => $dTglCair,
            'Status' => '1'
        ];
        Deposito::where('Rekening', $cRekening)->update($vaArray);
    }

    public static function updWriteOff($dTgl, $cFaktur, $dRekening, $nPlafond, $nBakiDebet)
    {
        if ($nPlafond + $nBakiDebet <> 0) {
            $vaArray = [
                'TglWriteOff' => $dTgl,
                'FakturWriteOff' => $cFaktur,
                'BakiDebetWriteOff' => $nBakiDebet
            ];
            Debitur::where('Rekening', $dRekening)->update($vaArray);
            // updRekWriteOff ngarah ke bukubesar
        }
    }

    public static function deleteBukuBesar($cStatus, $cFaktur)
    {
        if (!empty($cFaktur)) {
            BukuBesar::where('Status', '=', $cStatus)
                ->where('Faktur', 'LIKE', $cFaktur . '%')
                ->delete();
        }
    }

    public static function updAngsuranKredit($cStatus, $cFaktur, $dTgl, $cRekening, $cKeterangan, $nDPokok = 0, $nKPokok = 0, $nDBunga = 0, $nKBunga = 0, $nDenda = 0, $cCabangEntry = '', $cKas = 'K', $bUpdBukuBesar = true, $nDTitipan = 0, $nKTitipan = 0, $nAdministrasi = 0, $cRekonsiliasi = 'T', $nRRA = 0, $nPinalti = 0, $cRekeningPB = '', $cUsername = '', $nSimpananWajib = 0, $cJenisPelunasan = 0)
    {
        $cRekTab = '';
        $nRRAygDibayar = 0;
        $nPotBunga = 0;
        $nIptw = 0;
        if (empty($cCabangEntry)) {
            $cCabangEntry = GetterSetter::getCabang($cUsername, $dTgl);
        }
        $dTgl = Carbon::parse($dTgl);
        $nBayarBunga = $nKBunga;
        $dTglAwal = $dTgl->firstOfMonth(); //Func::BOM(Func::String2Date($dTgl));
        $dTglAwalString = Func::Date2String($dTglAwal);
        $dTglAkhir = Func::Date2String($dTgl);
        $vaData = DB::table('angsuran')
            ->select(DB::raw('SUM(RRA) as nRRA'))
            ->where('Rekening', $cRekening)
            ->whereBetween('Tgl', [$dTglAwalString, $dTglAkhir])
            ->groupBy('Rekening')
            ->first();
        if ($vaData) {
            $nRRAygDibayar = Func::String2Number($vaData->nRRA);
        }
        $nKewajibanRRA = max(GetterSetter::getRRA($cRekening, $dTgl) - Func::String2Number($nRRAygDibayar), 0);
        $nPYAD = $nKewajibanRRA;
        $nPYAD = 0;
        if ($nPYAD > $nBayarBunga) {
            $nPYAD = $nBayarBunga;
        }
        if ($nRRA == 0) {
            $nRRA = $nPYAD;
        }
        if ($nRRA > $nKBunga) {
            $nRRA = $nKBunga;
        }
        if ($nDPokok <> 0 || $nKPokok <> 0 || $nDBunga <> 0 || $nDenda <> 0 || $nDTitipan <> 0 || $nRRA <> 0 || $nAdministrasi <> 0) {
            $vaArray = [
                "Faktur" => $cFaktur,
                "Status" => $cStatus,
                "Tgl" => $dTgl->format('Y-m-d'),
                "Rekening" => $cRekening,
                "Keterangan" => $cKeterangan,
                "DPokok" => $nDPokok,
                "KPokok" => $nKPokok,
                "DBunga" => $nDBunga,
                "KBunga" => $nKBunga,
                "PotonganBunga" => $nPotBunga,
                "Denda" => $nDenda,
                "DTitipan" => $nDTitipan,
                "KTitipan" => $nKTitipan,
                "Administrasi" => $nAdministrasi,
                "BungaPinalty" => $nPinalti,
                "nRRA" => $nRRA,
                "SimpananWajib" => $nSimpananWajib,
                "JenisPelunasan" => $cJenisPelunasan,
                "CabangEntry" => $cCabangEntry,
                "Kas" => $cKas,
                "UserName" => $cUsername,
                "DateTime" => Carbon::now(),
                "IPTW" => $nIptw,
                "Rekonsiliasi" => $cRekonsiliasi,
                "RekeningPB" => $cRekeningPB
            ];
            Angsuran::create($vaArray);
            if ($nIptw > 0) {
                $vaData2 = DB::table('debitur')
                    ->select('RekeningTabungan')
                    ->where('Rekening', '=', $cRekening)
                    ->first();
                if ($vaData2) {
                    $cRekTab = $vaData2->RekeningTabungan;
                    self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, GetterSetter::getDBConfig("msKodeSetoranPB"), 'IPTW ' . $cKeterangan, $nIptw, '', true);
                }
            }
        }
        if ($bUpdBukuBesar) {
            self::updRekAngsuranPembiayaan($cFaktur);
        }
        if ($cKas == 'T' && $cStatus == '5') {
            $vaData3 = DB::table('debitur')
                ->select('RekeningTabungan')
                ->where('Rekening', '=', $cRekening)
                ->first();
            if ($vaData3) {
                $cRekTab = $vaData3->RekeningTabungan;
            }
            $cPenarikanPB = GetterSetter::getDBConfig("msKodePenarikanPB");
            $cKodeAngsuranPokok = GetterSetter::getDBConfig("msKodeAngsuranPokok");
            $cKodeAngsuranBunga = GetterSetter::getDBConfig("msKodeAngsuranBunga");
            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, $cKodeAngsuranPokok, 'Pokok ' . $cKeterangan, $nKPokok, '', true);
            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, $cKodeAngsuranBunga, 'Bunga ' . $cKeterangan, $nKBunga, '', true);
            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, $cKodeAngsuranBunga, 'Pinalti ' . $cKeterangan, $nPinalti, '', true);
            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, $cPenarikanPB, 'Denda ' . $cKeterangan, $nDenda, '', true);
            self::updMutasiTabungan($cFaktur, $dTgl, $cRekTab, $cPenarikanPB, 'Administrasi ' . $cKeterangan, $nAdministrasi, '', true);
        }
        if ($nSimpananWajib > 0) {
            $cKode = GetterSetter::getKode($cRekening);
            self::updMutasiAnggota($cFaktur, $dTgl, $cKode, "Simpanan Wajib " . $cKeterangan, 'K', $nSimpananWajib, true, $cCabangEntry, $cKas, $cRekTab, $cRekeningPB, $cUsername);
        }

        $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTgl);
        if ($nKPokok > 0 && $nBakiDebet == 0) {
            $dBom = $dTgl->firstOfMonth();
            $dBomParse = Carbon::parse($dBom);
            $dEomBulanLalu = $dBomParse->subDay()->toDateString();
            $vaProvisi = GetterSetter::getAmortisasiDebitur($cRekening, 'Provisi', $dEomBulanLalu);
            $nSisaProvisi = $vaProvisi['Sisa'];
            $vaAdministrasi = GetterSetter::getAmortisasiDebitur($cRekening, 'Administrasi', $dEomBulanLalu);
            $nSisaAdministrasi = $vaAdministrasi['Sisa'];
            $cGolongan = GetterSetter::getGolongan($cRekening);
            $cRekeningProvisi = '';
            $cRekeningProvisi4 = '';
            $cRekeningAdministrasi = '';
            $cRekeningAdministrasi4 = '';
            $vaData4 = DB::table('golongankredit')
                ->select(
                    'RekeningProvisi',
                    'RekeningPendapatanProvisi',
                    'RekeningAdministrasi',
                    'RekeningPendapatanAdministrasi'
                )
                ->where('Kode', '=', $cGolongan)
                ->first();
            if ($vaData4) {
                $cRekeningProvisi = $vaData4->RekeningProvisi;
                $cRekeningProvisi4 = $vaData4->RekeningPendapatanProvisi;
                $cRekeningAdministrasi = $vaData4->RekeningAdministrasi;
                $cRekeningAdministrasi4 = $vaData4->RekeningPendapatanAdministrasi;
            }
            if ($nSisaProvisi + $nSisaAdministrasi <> 0) {
            }
        }
    }

    public static function updRekAngsuranPembiayaan($cFaktur)
    {
        $cStatus = self::msAngsuranKredit;
        self::DeleteBukuBesar($cStatus, $cFaktur);
        $nDKas = 0;
        $nKKas = 0;
        $nKPokok = 0;
        $nKBunga = 0;
        $nDenda = 0;
        $nBungaPinalty = 0;
        $nAdministrasi = 0;

        $vaData = DB::table('angsuran as a')
            ->selectRaw(
                "d.CabangEntry as CabangNasabah,
                a.BungaPinalty,
                a.DateTime,
                a.ID,
                a.Status,
                a.Rekening,
                a.Tgl,
                a.KPokok,
                a.KBunga,
                a.DPokok,
                a.DBunga,
                a.PotonganBunga,
                a.CabangEntry,
                a.Denda,
                a.Tabungan,
                a.DTitipan,
                a.KTitipan,
                a.Administrasi,
                a.RRA,
                a.KBungaRK,
                a.Kas,
                r.Nama as NamaDebitur,
                g.Rekening as RekeningPokok,
                g.RekeningCadanganBunga,
                g.RekeningDenda,
                d.TglWriteOff,
                g.RekeningHapusBuku,
                g.RekeningBungaHapusBuku,
                g.RekeningBunga as RekeningPendapatanBunga,
                g.RekeningTitipan,
                '' as RekeningAdministrasi,
                g.RekeningBungaPinalty,
                a.UserName,
                a.Keterangan,
                a.Rekonsiliasi,
                RekeningIPTW,
                IFNULL(
                    (select CabangLama from debitur_cabang where Rekening = d.Rekening order by tgl desc limit 1),
                    d.CabangEntry
                ) as CabangNasabah,
                d.UserName"
            )
            ->leftJoin('debitur as d', 'd.rekening', '=', 'a.rekening')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->leftJoin('golongankredit as g', function ($join) {
                $join->on('g.kode', '=', DB::raw('ifnull((select GolonganKredit_Baru from debitur_golongankredit where Rekening = d.Rekening and tgl <= a.tgl order by tgl desc limit 1), d.GolonganKredit)'));
            })
            ->where('a.faktur', $cFaktur)
            ->where('a.status', '5')
            ->groupBy('a.Rekening', 'a.Faktur', 'a.Tgl', 'a.ID')
            ->get();

        foreach ($vaData as $d) {
            $cUser = $d->UserName;
            $dTgl = Carbon::parse($d->Tgl);
            $cCabangNasabah = $d->CabangNasabah;
            $cCabangEntry = $d->CabangEntry;
            $vaCab = GetterSetter::getRekeningAntarKantor($d->CabangEntry, $cCabangNasabah);
            $cRekeningAKP = $vaCab['RekeningAKP'];
            $cRekeningAKA = $vaCab['RekeningAKA'];
            $cRekeningAKEntry = $vaCab['RekeningAKEntry'];
            $cRekeningAKLawan = $vaCab['RekeningAKLawan'];
            $d->RRA = 0;
            $nAngsuran = $d->KPokok + $d->KBunga + $d->KBungaRK + $d->Denda - $d->KTitipan + $d->DTitipan - $d->DPokok - $d->DBunga - $d->PotonganBunga + $d->Administrasi + $d->BungaPinalty;
            $nDKas = $nAngsuran > 0 ? $nAngsuran : 0;
            $nKKas = $nAngsuran < 0 ? $nAngsuran * -1 : 0;
            $cRekKas = $d->Kas !== 'K' ? GetterSetter::getDBConfig("msRekeningPB") : GetterSetter::getKasTeller($d->UserName, $d->Tgl);
            $rekPokok = $d->RekeningPokok;
            $rekBunga = $d->RekeningPendapatanBunga;
            $rekDenda = $d->RekeningDenda;
            $rekPinalti = $d->RekeningBungaPinalty;
            $rekAdministrasi = $d->RekeningAdministrasi;
            $cKeterangan = $d->Keterangan;
            $cCabang = $d->CabangEntry;
            $cRekonsiliasi = $d->Rekonsiliasi;
            $nKPokok = $d->KPokok;
            $nKBunga = $d->KBunga;
            $nDenda = $d->Denda;
            $nBungaPinalty = $d->BungaPinalty;
            $nAdministrasi = $d->Administrasi;

            if ($d->Kas == 'P') {
                $cRekKas = $d->RekeningPB;
                if (empty($cRekKas)) {
                    $cRekKas =
                        GetterSetter::getDBConfig("msRekeningPB");
                }
            }

            if ($d->TglWriteOff < $d->Tgl) {
                $d->RekeningPokok = $d->RekeningHapusBuku;
                $d->RekeningPendapatanBunga = $d->RekeningBungaHapusBuku;
            }

            if (GetterSetter::getCabangInduk($d->CabangEntry, $cUser) !== GetterSetter::getCabangInduk($cCabangNasabah, $cUser)) {
                // UpdBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningAKEntry, $cKeterangan, 0, $nDKas, $d->UserName, $d->Kas);
                // UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $cRekeningAKLawan, $cKeterangan, $nDKas, 0, $d->UserName, $d->Kas);
            }
        }
        if ($nDKas > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekKas, $cKeterangan, $nDKas, 0, $d->UserName, $d->Kas);
        }
        if ($nKKas > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $cRekKas, $cKeterangan, $nKKas, 0, $d->UserName, $d->Kas);
        }
        if ($nKPokok > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $rekPokok, 'Pokok ' . $cKeterangan, 0, $d->KPokok, $d->UserName, $d->Kas);
        }
        if ($nKBunga > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $rekBunga, 'Bunga ' . $cKeterangan, 0, $d->KBunga - $d->RRA, $d->UserName, $d->Kas);
        }
        if ($nDenda > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $rekDenda, 'Denda ' . $cKeterangan, 0, $d->Denda, $d->UserName, $d->Kas);
        }
        if ($nBungaPinalty > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $rekPinalti, 'Pinalti ' . $cKeterangan, 0, $d->BungaPinalty, $d->UserName, $d->Kas);
        }
        if ($nAdministrasi > 0) {
            self::UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, $rekAdministrasi, 'Adm. ' . $cKeterangan, 0, $d->Administrasi, $d->UserName, $d->Kas);
        }


        // if ($d->TglWriteOff < $d->Tgl) {
        // UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, '6.640.01', 'Pokok ' . $cKeterangan, 0, $d->KPokok, $d->UserName, $d->Kas);
        // UpdBukuBesar($cStatus, $cFaktur, $cCabangNasabah, $dTgl, '6.640.02', 'Bunga ' . $cKeterangan, 0, $d->KBunga, $d->UserName, $d->Kas);
        // }
    }

    public static function updJurnalLainLain($dTgl, $cFaktur, $cRekeningPerkiraan, $cKeterangan, $nDebet = 0, $nKredit = 0, $lUpdateBukuBesar = true, $cNoReff = '', $cJenisJurnal = '', $cKas = 'N', $cCabangEntry = '', $cUserName)
    {
        $cDateTime = Carbon::now();

        if (empty($cCabangEntry)) {
            $cCabangEntry = GetterSetter::getDBConfig("msKodeCabang");
        }

        if ($cRekeningPerkiraan == GetterSetter::getDBConfig("msRekeningKas")) {
            $cCabangEntry = GetterSetter::getCabangInduk($cCabangEntry, $cUserName);
        }

        if (substr($cFaktur, 0, 2) == 'KK' || substr($cFaktur, 0, 2) == 'KM') {
            $cKas = 'K';
        }

        $cKeterangan = $cKeterangan;

        if ($nDebet + $nKredit !== 0) {
            $va = [
                "Tgl" => Func::Date2String($dTgl),
                "Faktur" => $cFaktur,
                "Rekening" => $cRekeningPerkiraan,
                'DateTime' => $cDateTime,
                "Keterangan" => $cKeterangan,
                "Debet" => Func::String2Number($nDebet),
                "Kredit" => Func::String2Number($nKredit),
                "UserName" => $cUserName,
                "NoReff" => $cNoReff,
                "Rekonsiliasi" => $cJenisJurnal,
                "Kas" => $cKas,
                "CabangEntry" => $cCabangEntry
            ];

            DB::table('jurnal')->insert($va);

            if ($lUpdateBukuBesar) {
                self::UpdRekJurnal($cFaktur);
            }
        }
    }

    public static function updRekJurnal($cFaktur)
    {
        $cStatus = self::msJurnalLain;
        self::DeleteBukuBesar($cStatus, $cFaktur);

        $vaData = DB::table('jurnal')
            ->select('Tgl', 'Rekening', 'Keterangan', 'Debet', 'Kredit', 'Kas', 'CabangEntry', 'UserName')
            ->where('faktur', $cFaktur)
            ->get();

        foreach ($vaData as $d) {
            self::UpdBukuBesar($cStatus, $cFaktur, $d->CabangEntry, Func::String2Date($d->Tgl), $d->Rekening, $d->Keterangan, $d->Debet, $d->Kredit, '', $d->Kas);

            if (substr($cFaktur, 0, 2) == 'PB') {
                // $cRekeningKas = aCfg("msRekeningPB");
                self::UpdBukuBesar($cStatus, $cFaktur, $d->CabangEntry, Func::String2Date($d->Tgl), GetterSetter::getDBConfig("msRekeningPB"), $d->Keterangan, $d->Kredit, $d->Debet, '', $d->Kas);
            }

            $cCabangUser = GetterSetter::getCabang($d->UserName, $d->Tgl);

            if ($cCabangUser !== $d->CabangEntry && substr($cFaktur, 0, 2) != 'CA' && substr($cFaktur, 0, 2) != 'GJ' && substr($cFaktur, 0, 2) != 'PA' && substr($cFaktur, 0, 2) != 'PV') {
                $vaCab = GetterSetter::getRekeningAntarKantor($cCabangUser, $d->CabangEntry);
                $cRekeningAKP = $vaCab['RekeningAKP'];
                $cRekeningAKA = $vaCab['RekeningAKA'];

                // UpdBukuBesar($cStatus, $cFaktur, $d->CabangEntry, String2Date($d->Tgl), $cRekeningAKA, $d->Keterangan, $d->Kredit, $d->Debet, '', 'N');
                // UpdBukuBesar($cStatus, $cFaktur, $cCabangUser, String2Date($d->Tgl), $cRekeningAKP, $d->Keterangan, $d->Debet, $d->Kredit, '', 'N');
            }
        }
    }

    public static function UpdRekeningRealisasiKredit($cFaktur)
    {
        $cStatus = self::msRealisasiKredit;
        self::DeleteBukuBesar($cStatus, $cFaktur);

        $vaData = DB::table('angsuran as a')
            ->select(
                'd.Lama',
                'd.CaraPerhitungan',
                'd.Faktur',
                'd.SukuBunga',
                'd.PencairanPokok',
                'd.ID',
                'd.StatusPencairan',
                'd.CaraPencairan',
                'd.Rekening',
                'd.BiayaTaksasi',
                'd.Lainnya',
                'a.Tgl',
                'd.RekeningTabungan',
                'a.DPokok',
                'd.Administrasi',
                'd.Materai',
                'd.Notaris',
                'd.Asuransi',
                'd.TotalBunga',
                'd.Provisi',
                'd.BiayaTransaksi',
                'd.Angsuran1',
                'd.PKBawahTangan',
                'a.Status',
                'g.rekening as RekeningRealisasi',
                'g.RekeningAdministrasi',
                'g.Administrasi as LimitAdministrasi',
                'g.RekeningPendapatanAdministrasi',
                'g.RekeningBiayaTaksasi',
                'g.RekeningMaterai',
                'g.RekeningNotaris',
                'g.RekeningAsuransi',
                'g.RekeningProvisi',
                'a.Keterangan',
                'a.CabangEntry',
                'r.nama as NamaDebitur',
                'n.RekeningTabungan as RekeningTabunganNotaris',
                'g.rekeningnotaris as RekeningTitipanNotaris',
                'g.RekeningPendapatanProvisi',
                'g.RekeningBiayaTransaksi',
                'g.RekeningTitipanBiayaTransaksi',
                'g.RekeningPajakBiayaTransaksi',
                's.RekeningTabungan as RekeningTabunganAsuransi',
                's.RekeningTitipan as RekeningTitipanAsuransi',
                'a.UserName',
                'g.RekeningBiayaLainnya'
            )
            ->leftJoin('debitur as d', 'd.rekening', '=', 'a.rekening')
            ->leftJoin('golongankredit as g', 'g.kode', '=', 'd.golongankredit')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->leftJoin('notaris as n', 'n.kode', '=', 'd.kodenotaris')
            ->leftJoin('asuransi as s', 's.kode', '=', 'd.kodeasuransi')
            ->where('a.Faktur', '=', $cFaktur)
            ->first();

        if ($vaData) {
            if ($vaData->CaraPencairan == '1' || $vaData->CaraPencairan == 'K') {
                $cRekeningKas = GetterSetter::getDBConfig("msRekeningKasTeller");
                echo "<h1>" . $cFaktur . " - " . $vaData->StatusPencairan . "</h1>";
            } else {
                $cRekeningKas = GetterSetter::getDBConfig("msRekeningPB");
            }

            $nLama = $vaData->Lama;
            $cStatus = $vaData->Status;
            $nPembebanan = $vaData->DPokok;
            $cNama = $vaData->NamaDebitur;
            $cRekening = $vaData->Rekening;
            $nAdministrasi = $vaData->Administrasi;
            $nProvisi = $vaData->Provisi;
            $nMaterai = $vaData->Materai;
            $nAsuransi = $vaData->Asuransi;
            $nBiayaTaksasi = $vaData->BiayaTaksasi;
            $nNotaris = $vaData->Notaris;

            $cSetoranPB = GetterSetter::getDBConfig("msKodeSetoranPB");
            $cPenarikanPB = GetterSetter::getDBConfig("msKodePenarikanPB");
            $dTgl = $vaData->Tgl;
            $cKeterangan = $vaData->Keterangan;

            $cCabangEntry = $vaData->CabangEntry;
            $cRekeningPB = GetterSetter::getDBConfig("msRekeningPB");
            $nTotalPencairan = $nPembebanan;

            $nPotongan = $nAdministrasi + $nProvisi + $nMaterai + $nAsuransi + $nBiayaTaksasi + $nNotaris;
            if ($cStatus == "2") {
                self::deleteBukuBesar('2', $cFaktur);
                self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningRealisasi, 'Pencairan ' . $cKeterangan, $nPembebanan, 0, $vaData->UserName, '');
                self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Pencairan ' . $cKeterangan, 0, $nPembebanan, $vaData->UserName, '');
                // if ($vaData->StatusPencairan <> 1) {
                // administrasi
                if ($vaData->Administrasi > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Administrasi ' . $cKeterangan, $vaData->Administrasi, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningAdministrasi, 'Administrasi ' . $cKeterangan, 0, $vaData->Administrasi, $vaData->UserName, '');
                }
                // notaris
                if ($vaData->Notaris > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Notaris ' . $cKeterangan, $vaData->Notaris, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningNotaris, 'Notaris ' . $cKeterangan, 0, $vaData->Notaris, $vaData->UserName, '');
                }
                // materai
                if ($vaData->Materai > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Materai' . $cKeterangan, $vaData->Materai, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningMaterai, 'Materai' . $cKeterangan, 0, $vaData->Materai, $vaData->UserName, '');
                }
                // asuransi
                if ($vaData->Asuransi > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Asuransi' . $cKeterangan, $vaData->Asuransi, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningAsuransi, 'Asuransi' . $cKeterangan, 0, $vaData->Asuransi, $vaData->UserName, '');
                }
                // provisi
                if ($vaData->Provisi > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'Provisi' . $cKeterangan, $vaData->Provisi, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningProvisi, 'Provisi' . $cKeterangan, 0, $vaData->Provisi, $vaData->UserName, '');
                }
                // biaya taksasi
                if ($vaData->BiayaTaksasi > 0) {
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $cRekeningKas, 'BiayaTaksasi' . $cKeterangan, $vaData->BiayaTaksasi, 0, $vaData->UserName, '');
                    self::updBukuBesar($cStatus, $cFaktur, $cCabangEntry, $dTgl, $vaData->RekeningBiayaTaksasi, 'BiayaTaksasi' . $cKeterangan, 0, $vaData->BiayaTaksasi, $vaData->UserName, '');
                }
                // }
            }
        }
    }
}
