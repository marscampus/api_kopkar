<?php

namespace App\Helpers;

use App\Helpers\Func\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerhitunganDeposito
{
    public static function getNamaDeposan($cRekening)
    {
        $result = DB::table('deposito as d')
            ->leftJoin('registernasabah as r', 'd.Kode', '=', 'r.Kode')
            ->select('r.Nama')
            ->where('d.Rekening', '=', $cRekening)
            ->first();

        $cNama = $result ? $result->Nama : null;

        return $cNama;
    }

    public static function getTglJthTmpDepositoOld($cRekening, $dTgl)
    {
        $result = DB::table('mutasideposito')
            ->select('JthTmp')
            ->where('Rekening', '=', $cRekening)
            ->where('Tgl', '<=', $dTgl)
            ->groupBy('JthTmp')
            ->orderBy('JthTmp')
            ->limit(2)
            ->get();

        if ($result->count() > 1) {
            $dTgl = $result[1]->JthTmp;
        } else {
            $result = DB::table('deposito')
                ->select('Tgl')
                ->where('rekening', $cRekening)
                ->first();

            if ($result) {
                $dTgl = $result->Tgl;
            }
        }

        return $dTgl;
    }

    public static function getFee($cRekening)
    {
        $nRetval = 0;

        $result = DB::table('deposito')
            ->select('FeeDeposito')
            ->where('Rekening', $cRekening)
            ->first();

        if ($result) {
            $nRetval = $result->FeeDeposito;
        }

        return $nRetval;
    }

    public static function getNominalDepositoLain($dTgl, $cRekening)
    {
        $cKode = GetterSetter::GetKode($cRekening);
        $dTgl = Func::Date2String($dTgl);

        $result = DB::table('deposito as d')
            ->leftJoin('mutasideposito as m', 'm.rekening', '=', 'd.rekening')
            ->select(DB::raw('SUM(m.setoranplafond - m.pencairanplafond) as saldo'))
            ->where('d.kode', $cKode)
            ->where('m.rekening', '<>', $cRekening)
            ->first();

        $nSaldo = $result ? $result->saldo : 0;

        return $nSaldo;
    }

    public static function getWajibPajakPerorangan($cRekening)
    {
        $cStatusPajak = "";
        $cGolonganDeposito = "";

        $resultDeposito = DB::table('deposito')
            ->select('StatusPajak', 'GolonganDeposito')
            ->where('Rekening', $cRekening)
            ->first();

        if ($resultDeposito) {
            $cStatusPajak = $resultDeposito->StatusPajak;
            $cGolonganDeposito = $resultDeposito->GolonganDeposito;
        }

        $resultGolonganDeposito = DB::table('golongandeposito')
            ->select('WajibPajak')
            ->where('Kode', $cGolonganDeposito)
            ->first();

        if ($resultGolonganDeposito) {
            if ($cStatusPajak == '0') {
                $cStatusPajak = $resultGolonganDeposito->WajibPajak;
            }
        }

        return $cStatusPajak;
    }

    public static function getPencairanDeposito($cRekening, $dTgl, $nNominal = false)
    {
        $dTglKemaren = date('Y-m-d', Date::nextDay(Func::Tgl2Time($dTgl), -1));
        $va['Bunga'] = 0;
        $va['Pajak'] = 0;
        $va['Nominal'] = 0;
        $va['BungaYadib'] = 0;
        $va['TotalBungaKonversi'] = 0;
        $va['TotalPajakKonversi'] = 0;
        $va['TotalBungaDiberikan'] = 0;
        $va['TotalPajakDiberikan'] = 0;
        $va['SelisihBunga'] = 0;
        $va['SelisihPajak'] = 0;
        $va['Accrual'] = 0;
        $va['Fee'] = 0;
        $va['BungaYangTidakDiakui'] = 0;
        $nTotalBungaDiberikan = 0;
        $nTotalPajakDiberikan = 0;
        $nTotalBungaKonversi = 0;
        $nTotalPajakKonversi = 0;

        $vaAro = self::getAro($cRekening);
        $cCaraPerpanjangan = $vaAro['CaraPerpanjangan'];
        $nDeposito = 0;
        $nBunga = 0;
        $nAccrual = 0;
        $nFee = 0;
        $nPajak = 0;
        $nBungaYangTidakDiAkui = 0;
        // $nPersenPajak = Func::number2String(GetterSetter::getDBConfig('msTarifPajak'));
        $nPersenPajak = GetterSetter::getDBConfig('msTarifPajak');
        $cKode = self::getGolonganDeposito($cRekening);
        $dTglBilyet = self::getTglBilyet($cRekening);
        $nJangkaWaktu = self::getLamaDeposito($cKode);
        $dJthTmp = self::getTglJthTmpDeposito($cRekening, $dTgl);
        $dTglValuta = self::getTglJthTmpDepositoOld($cRekening, $dTgl);
        $nBagiHari = date('d', Func::Tgl2Time($dTgl));
        if ($cCaraPerpanjangan == '1') {
            $dEOMValuta = Func::EOM(Func::String2Date($dTglValuta));
            $dTglValuta = Func::getTglIdentik($dTglBilyet, $dEOMValuta);
        }
        $dTglMutasiTerakhir = self::getTglMutasiTerakhir($cRekening, 'mutasideposito', $dTgl);
        $dTglPencairanBulanIni = Func::getTglIdentik($dTglValuta, $dTgl);
        if ($cCaraPerpanjangan == '1') {
            $dTglPencairanBulanIni = Func::getTglIdentik($dTglBilyet, $dTgl);
        }
        $dTglPencairanBulanLalu = date('Y-m-d', Date::nextMonth(strtotime($dTglPencairanBulanIni), -1, 0));
        $bReturn = false;

        $vaData = DB::table('mutasideposito')
            ->select('Tgl')
            ->where('Rekening', '=', $cRekening)
            ->where('Bunga', '>', 0)
            ->where('Tgl', '>=', $dTglPencairanBulanLalu)
            ->where('Tgl', '<=', $dTgl)
            ->get();
        if ($vaData->count() > 0) {
            if ($dTgl >= $dTglPencairanBulanIni) {
                $vaData2  = DB::table('mutasideposito')
                    ->select('Tgl')
                    ->where('Rekening', '=', $cRekening)
                    ->where('Bunga', '>', 0)
                    ->where('Tgl', '>=', $dTglPencairanBulanIni)
                    ->where('Tgl', '<=', $dTgl)
                    ->get();
                if ($vaData2->count() == 0) {
                    $bReturn = true;
                }
            }
        } else {
            $bReturn = true;
        }

        if ($dTglPencairanBulanIni == $dTglValuta) {
            $bReturn = false;
        }
        if ($dTgl < $dTglPencairanBulanIni) {
            $bReturn = false;
        }

        $nDeposito = self::getNominalDeposito($cRekening, $dTglKemaren);
        $cKode1 = GetterSetter::getKode($cRekening);
        $nJumlahHari = self::getJumlahHariDeposito($dTgl, $dTglBilyet);

        $dBOM = Func::BOM($dTglPencairanBulanIni);
        $dEOMKemaren = date('Y-m-d', Date::nextDay(Func::Tgl2Time(($dBOM)), -1));
        $nPersenBunga = GetterSetter::getRate($dEOMKemaren, $cRekening);
        $nPersenBunga = GetterSetter::getRate($dTgl, $cRekening);

        if ($bReturn && $dTglMutasiTerakhir < $dJthTmp) {
            $cCaraPerhitungan = self::getCaraPerhitunganDeposito($cRekening);
            $nPersenFee = self::getFee($cRekening);
            $nBungaHarian = $nPersenBunga * $nDeposito / 100 / 365;
            $nDayDeposito = date('d', Func::Tgl2Time($dTglValuta));
            if ($cCaraPerhitungan) {
                $nBunga = $nPersenBunga * $nDeposito / 100 / 12;
                $nBungaHarian = $nBunga / $nJumlahHari;
            } else {
                $nBunga = round($nJumlahHari * $nBungaHarian);
            }
            $nFee = $nPersenFee * $nDeposito / 100;
            $nSelisihTanggal = $nJumlahHari - ($nDeposito);
            $nBungaYangTidakDiAkui = $nDayDeposito * $nBungaHarian;

            $nAccrual = round(max(0, ($nJumlahHari - $nDayDeposito + 1)) * $nBungaHarian);
            $nAccrual = 0;
            $nDepositoLain = self::getNominalDepositoLain($dTglPencairanBulanIni, $cRekening);
            $nTotalTabungan = PerhitunganTabungan::getTotalTabungan($dTglPencairanBulanIni, $cKode1);

            $vaData3 = DB::table('deposito')
                ->select('RekeningTabungan')
                ->where('Rekening', '=', $cRekening)
                ->first();
            if ($vaData3) {
                $cRekeningTabungan = $vaData3->RekeningTabungan;
            }
            $nGetSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($cRekeningTabungan, $dTgl);
            $cKode = GetterSetter::getKode($cRekening);
            $cKodeInduk = Func::getKodeInduk($cKode);
            $nTotalKekayaan = GetterSetter::getTotalKekayaan($cKodeInduk, $dTgl);
            if ($nTotalKekayaan > 7500000) {
                $nPajak = round($nPersenPajak * $nBunga / 100);
            } else {
                $nPajak = 0;
            }
            $nPajak = 0;
            $nBunga = round(($nBunga));

            if ($nBunga > 240000) {
                $nPajak = round(10 * $nBunga / 100);
            }
            $nFee = Func::pembulatan($nFee);
            $nPajak = round($nPajak);

            $cStatusPajak = PerhitunganDeposito::getWajibPajakPerorangan($cRekening);
            // return $cStatusPajak;
            if ($cStatusPajak == "Y") {
                $nPajak = $nPajak;
            } else {
                $nPajak = 0;
            }

            $cWajibPajak = self::getWajibPajak(self::getGolonganDeposito($cRekening));
            if ($cWajibPajak == "Y") {
                $nPajak = $nPajak;
            } else {
                $nPajak = 0;
            }
        }
        if ($nNominal) {
            $va['Nominal'] = self::getNominalDeposito($dTgl, $cRekening);
        }
        $va['Rate'] = $nPersenBunga;
        $va['Bunga'] = $nBunga;
        $va['Pajak'] = $nPajak;
        $va['Accrual'] = $nAccrual;
        $va['Fee'] = $nFee;
        $va['BungaYangTidakDiAkui'] = $nBungaYangTidakDiAkui;

        return $va;
    }

    public static function getPencairanDepositoAsli($cRekening, $dTgl, $nNominal = false)
    {
        $dTglKemaren = date('Y-m-d', Date::nextDay(Func::Tgl2Time($dTgl), -1));
        $va['Bunga'] = 0;
        $va['Pajak'] = 0;
        $va['Nominal'] = 0;
        $va['BungaYadib'] = 0;
        $va['TotalBungaKonversi'] = 0;
        $va['TotalPajakKonversi'] = 0;
        $va['TotalBungaDiberikan'] = 0;
        $va['TotalPajakDiberikan'] = 0;
        $va['SelisihBunga'] = 0;
        $va['SelisihPajak'] = 0;
        $va['Accrual'] = 0;
        $va['Fee'] = 0;
        $va['BungaYangTidakDiakui'] = 0;
        $nTotalBungaDiberikan = 0;
        $nTotalPajakDiberikan = 0;
        $nTotalBungaKonversi = 0;
        $nTotalPajakKonversi = 0;

        $vaAro = self::getAro($cRekening);
        $cCaraPerpanjangan = $vaAro['CaraPerpanjangan'];
        $nDeposito = 0;
        $nBunga = 0;
        $nAccrual = 0;
        $nFee = 0;
        $nPajak = 0;
        $nBungaYangTidakDiAkui = 0;
        $nPersenPajak = Func::number2String(GetterSetter::getDBConfig('msTarifPajak'));
        $cKode = self::getGolonganDeposito($cRekening);
        $dTglBilyet = self::getTglBilyet($cRekening);
        $nJangkaWaktu = self::getLamaDeposito($cKode);
        $dJthTmp = self::getTglJthTmpDeposito($cRekening, $dTgl);
        $dTglValuta = self::getTglJthTmpDepositoOld($cRekening, $dTgl);
        $nBagiHari = date('d', Func::Tgl2Time($dTgl));
        if ($cCaraPerpanjangan == '1') {
            $dEOMValuta = Func::EOM(Func::String2Date($dTglValuta));
            $dTglValuta = Func::getTglIdentik($dTglBilyet, $dEOMValuta);
        }
        $dTglMutasiTerakhir = self::getTglMutasiTerakhir($cRekening, 'mutasideposito', $dTgl);
        $dTglPencairanBulanIni = Func::getTglIdentik($dTglValuta, $dTgl);
        if ($cCaraPerpanjangan == '1') {
            $dTglPencairanBulanIni = Func::getTglIdentik($dTglBilyet, $dTgl);
        }
        $dTglPencairanBulanLalu = date('Y-m-d', Date::nextMonth(strtotime($dTglPencairanBulanIni), -1, 0));
        $bReturn = false;

        $vaData = DB::table('mutasideposito')
            ->select('Tgl')
            ->where('Rekening', '=', $cRekening)
            ->where('Bunga', '>', 0)
            ->where('Tgl', '>=', $dTglPencairanBulanLalu)
            ->where('Tgl', '<=', $dTgl)
            ->get();
        if ($vaData->count() > 0) {
            if ($dTgl >= $dTglPencairanBulanIni) {
                $vaData2  = DB::table('mutasideposito')
                    ->select('Tgl')
                    ->where('Rekening', '=', $cRekening)
                    ->where('Bunga', '>', 0)
                    ->where('Tgl', '>=', $dTglPencairanBulanIni)
                    ->where('Tgl', '<=', $dTgl)
                    ->get();
                if ($vaData2->count() == 0) {
                    $bReturn = true;
                }
            }
        } else {
            $bReturn = true;
        }

        if ($dTglPencairanBulanIni == $dTglValuta) {
            $bReturn = false;
        }
        if ($dTgl < $dTglPencairanBulanIni) {
            $bReturn = false;
        }

        $nDeposito = self::getNominalDeposito($cRekening, $dTglKemaren);
        $cKode1 = GetterSetter::getKode($cRekening);
        $nJumlahHari = self::getJumlahHariDeposito($dTgl, $dTglBilyet);

        $dBOM = Func::BOM($dTglPencairanBulanIni);
        $dEOMKemaren = date('Y-m-d', Date::nextDay(Func::Tgl2Time(($dBOM)), -1));
        $nPersenBunga = GetterSetter::getRate($dEOMKemaren, $cRekening);
        $nPersenBunga = GetterSetter::getRate($dTgl, $cRekening);

        // echo "<".$cRekening ."> [". $dTglMutasiTerakhir ."] {". $dJthTmp."}<BR>";
        if ($bReturn && $dTglMutasiTerakhir < $dJthTmp) {
            // return 'masup';
            $cCaraPerhitungan = self::getCaraPerhitunganDeposito($cRekening);
            $nPersenFee = self::getFee($cRekening);
            $nBungaHarian = $nPersenBunga * $nDeposito / 100 / 365;
            $nDayDeposito = date('d', Func::Tgl2Time($dTglValuta));
            if ($cCaraPerhitungan) {
                $nBunga = $nPersenBunga * $nDeposito / 100 / 12;
                $nBungaHarian = $nBunga / $nJumlahHari;
            } else {
                $nBunga = round($nJumlahHari * $nBungaHarian);
            }
            $nFee = $nPersenFee * $nDeposito / 100;
            $nSelisihTanggal = $nJumlahHari - ($nDeposito);
            $nBungaYangTidakDiAkui = $nDayDeposito * $nBungaHarian;

            $nAccrual = round(max(0, ($nJumlahHari - $nDayDeposito + 1)) * $nBungaHarian);
            $nAccrual = 0;
            $nDepositoLain = self::getNominalDepositoLain($dTglPencairanBulanIni, $cRekening);
            $nTotalTabungan = PerhitunganTabungan::getTotalTabungan($dTglPencairanBulanIni, $cKode1);

            $vaData3 = DB::table('deposito')
                ->select('RekeningTabungan')
                ->where('Rekening', '=', $cRekening)
                ->first();
            if ($vaData3) {
                $cRekeningTabungan = $vaData3->RekeningTabungan;
            }
            $nGetSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($cRekeningTabungan, $dTgl);
            $cKode = GetterSetter::getKode($cRekening);
            $cKodeInduk = Func::getKodeInduk($cKode);
            $nTotalKekayaan = GetterSetter::getTotalKekayaan($cKodeInduk, $dTgl);
            if ($nTotalKekayaan > 7500000) {
                $nPajak = round($nPersenPajak * $nBunga / 100);
            } else {
                $nPajak = 0;
            }
            $nPajak = 0;
            $nBunga = round(($nBunga));

            if ($nBunga > 240000) {
                $nPajak = round(10 * $nBunga / 100);
            }
            $nFee = Func::pembulatan($nFee);
            $nPajak = round($nPajak);

            $cStatusPajak = PerhitunganDeposito::getWajibPajakPerorangan($cRekening);
            // return $cStatusPajak;
            if ($cStatusPajak == "Y") {
                $nPajak = $nPajak;
            } else {
                $nPajak = 0;
            }

            $cWajibPajak = self::getWajibPajak(self::getGolonganDeposito($cRekening));
            if ($cWajibPajak == "Y") {
                $nPajak = $nPajak;
            } else {
                $nPajak = 0;
            }
        }
        if ($nNominal) {
            $va['Nominal'] = self::getNominalDeposito($dTgl, $cRekening);
        }
        $va['Rate'] = $nPersenBunga;
        $va['Bunga'] = $nBunga;
        $va['Pajak'] = $nPajak;
        $va['Accrual'] = $nAccrual;
        $va['Fee'] = $nFee;
        $va['BungaYangTidakDiAkui'] = $nBungaYangTidakDiAkui;

        return $va;
    }

    public static function getAro($cRekening)
    {
        $vaData = DB::table('deposito')
            ->select(
                'ARO',
                'CaraPerhitungan',
                'CaraPerpanjangan'
            )
            ->where('Rekening', '=', $cRekening)
            ->first();
        $va['ARO'] = "T";
        $va['CaraPerhitungan'] = "1";
        $va['CaraPerpanjangan'] = "1";
        if ($vaData) {
            $va['ARO'] = $vaData->ARO;
            $va['CaraPerhitungan'] = $vaData->CaraPerhitungan;
            $va['CaraPerpanjangan'] = $vaData->CaraPerpanjangan;
        }
        return $va;
    }

    public static function getGolonganDeposito($rekening)
    {
        $vaData = DB::table('deposito')
            ->select('GolonganDeposito')
            ->where('Rekening', '=', $rekening)
            ->first();
        if ($vaData) {
            $golDeposito = $vaData->GolonganDeposito;
        }
        return $golDeposito;
    }

    public static function getTglBilyet($cRekening)
    {
        $vaData = DB::table('deposito')
            ->select('Tgl')
            ->where('Rekening', '=', $cRekening)
            ->first();
        if ($vaData) {
            $dTgl = $vaData->Tgl;
        }
        return $dTgl;
    }

    public static function getLamaDeposito($cKode)
    {
        $nLama = 0;
        $vaData = DB::table('golongandeposito')
            ->select('Lama')
            ->where('Kode', '=', $cKode)
            ->first();
        if ($vaData) {
            $nLama = $vaData->Lama;
        }
        return $nLama;
    }

    public static function getTglJthTmpDeposito($cRekening, $dTgl)
    {
        $dTgl = Func::Date2String($dTgl);
        $dbData = DB::table('mutasideposito')
            ->select('JthTmp')
            ->where('Rekening', '=', $cRekening)
            ->where('Tgl', '<=', $dTgl)
            ->orderByDesc('JthTmp')
            ->limit(1)
            ->first();
        if ($dbData) {
            $db = DB::table('deposito')
                ->select('Tgl')
                ->where('Rekening', '=', $cRekening)
                ->first();

            if ($db) {
                $dTglMasuk = $db->Tgl;
            }
            $dJthTmp = $dbData->JthTmp;
        } else {
            $dbData = DB::table('deposito')
                ->select('Tgl', 'JthTmp')
                ->where('Rekening', '=', $cRekening)
                ->first();

            if ($dbData) {
                $dTglMasuk = $dbData->Tgl;
                $dJthTmp = $dbData->JthTmp;
            } else {
                $dJthTmp = '';
            }
        }

        if ($dJthTmp > $dTgl) {
            $dTgl = $dJthTmp;
        } else {
            $nGolonganDeposito = self::getGolonganDeposito($cRekening);
            $nLama = self::getLamaDeposito($nGolonganDeposito);
            $n = $nLama;
            $dTglHitung = $dTglMasuk;

            for ($i = $dTglMasuk; $i <= $dTglHitung; $i = date('Y-m-d', Date::nextMonth(strtotime($dTglMasuk), $n))) {
                $n += $nLama;
                $dTgl = date('Y-m-d', Date::nextMonth(strtotime($dTglMasuk), $n));
            }
        }

        return $dTgl;
    }


    public static function getTglMutasiTerakhir($cRekening, $cTable, $dTgl)
    {
        $vaData = DB::table($cTable)
            ->select('Tgl')
            ->where('Rekening', '=', $cRekening)
            ->where('Tgl', '=', $dTgl)
            ->orderByDesc('Tgl')
            ->first();
        if ($vaData) {
            $dTgl = $vaData->Tgl;
        }
        return $dTgl;
    }

    public static function getNominalDeposito($cRekening, $dTgl)
    {
        $bLain = false;
        $cKode = GetterSetter::getKode($cRekening);
        if ($bLain) {
            $query = DB::table('deposito as d')
                ->leftJoin('mutasideposito as m', 'm.rekening', '=', 'd.rekening')
                ->where('d.kode', '=', $cKode)
                ->where('m.tgl', '<', $dTgl)
                ->where('m.rekening', '<>', $cRekening)
                ->sum(DB::raw('setoranplafond - pencairanplafond'));
        } else {
            $query = DB::table('mutasideposito')
                ->where('rekening', '=', $cRekening)
                ->where('tgl', '<=', $dTgl)
                ->sum(DB::raw('setoranplafond - pencairanplafond'));
        }

        if (!is_null($query)) {
            $nSaldo = $query;
        }

        return $nSaldo;
    }

    public static function getJumlahHariDeposito($dTgl, $dTglValuta = '')
    {
        if (empty($dTglValuta)) {
            $dTglValuta = $dTgl;
        }

        $dTgl = Func::Date2String($dTgl);

        $dCarbonTgl = Carbon::createFromFormat('Y-m-d', $dTgl);
        $nJumlah = $dCarbonTgl->daysInMonth;

        $dCarbonTglValuta = Carbon::parse($dTglValuta);
        $nHariValuta = $dCarbonTglValuta->day;

        $dCarbonTglSekarang = Carbon::parse($dTgl); // Perbaiki di sini, gunakan $dTgl
        $nJumlahSekarang = $dCarbonTglSekarang->day;

        if ($nHariValuta > $nJumlahSekarang || $nHariValuta == 31) {
            $nJumlah = $nJumlahSekarang;
        }

        return $nJumlah;
    }

    public static function getCaraPerhitunganDeposito($cRekening)
    {
        $vaData = DB::table('deposito')
            ->select('CaraPerhitungan')
            ->where('rekening', '=', $cRekening)
            ->first();

        if ($vaData) {
            $bReturn = false;
            if ($vaData->CaraPerhitungan === '1') {
                $bReturn = true;
            }
        }
        return $bReturn;
    }

    public static function getWajibPajak($cKode)
    {
        $result = DB::table('golongandeposito')
            ->where('kode', $cKode)
            ->value('WajibPajak');

        if ($result !== null) {
            return $result;
        }
    }
}
