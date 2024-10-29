<?php

namespace App\Helpers;

use App\Func\Helpers\Date;
use App\Helpers\Func\Date as FuncDate;
use App\Models\fun\Adendum;
use App\Models\fun\Cabang;
use App\Models\fun\Config;
use App\Models\fun\DetailSukuBunga;
use App\Models\fun\MutasiTabungan;
use App\Models\fun\NomorFaktur;
use App\Models\fun\TglTransaksi;
use App\Models\fun\UrutFaktur;
use App\Models\fun\Username;
use App\Models\fun\UsernameKantorKas;
use App\Models\master\GolonganSimpananBerjangka;
use App\Models\master\RegisterNasabah;
use App\Models\pinjaman\Debitur;
use App\Models\pinjaman\DebiturSukuBunga;
use App\Models\simpanan\Tabungan;
use App\Models\simpananberjangka\Deposito;
use App\Models\simpananberjangka\DepositoSukuBunga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetterSetter
{
    public static function getGolongan($cRekening)
    {
        $vaGolongan = ['simpanan', 'tabungan', 'deposito', 'kredit'];
        $vaTable = ['registernasabah', 'tabungan', 'deposito', 'debitur'];

        $cGolonganIndex = substr($cRekening, 3, 1);
        $cField = 'golongan' . $vaGolongan[$cGolonganIndex];
        $cTable = $vaTable[$cGolonganIndex];

        $vaData = DB::table($cTable)
            ->select($cField)
            ->where('Rekening', $cRekening)
            ->first();

        if (!$vaData || $vaData->$cField == '0') {
            $cGolongan = '02';
        } else {
            $cGolongan = $vaData->$cField;
        }
        return $cGolongan;
    }

    public static function getKode($cRekening)
    {
        $cKode = "";
        $vaArray = ["tabungan", "deposito", "debitur"];

        foreach ($vaArray as $value) {
            if (empty($cKode)) {
                $vaData = DB::table($value)
                    ->select('Kode')
                    ->where('Rekening', $cRekening)
                    ->first();

                if ($vaData) {
                    $cKode = $vaData->Kode;
                }
            }
        }
        return $cKode;
    }

    public static function getLastFaktur($cKey, $cLen)
    {
        try {
            $tgl = str_replace("-", "", self::getTglTransaksi());
            $cValueReturn = self::getLastKodeRegister($cKey, $cLen);
            $cKey = str_replace(" ", "", $cKey) . '101' . $tgl;
            $cValueReturn = $cKey . $cValueReturn;
            return $cValueReturn;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function setLastFaktur($cKey)
    {
        try {
            $vaData = DB::table('nomorfaktur')
                ->select('Kode', 'ID')
                ->where('Kode', '=', $cKey)
                ->first();

            if ($vaData) {
                $nId = $vaData->ID;
                $nId++;
                // Perbarui nilai ID di database menggunakan metode update
                NomorFaktur::where('Kode', $cKey)->update(['ID' => $nId]);
            } else {
                $nId = 1;

                // Buat record baru jika tidak ada record dengan Kode yang sesuai
                NomorFaktur::create([
                    'Kode' => $cKey,
                    'ID' => $nId
                ]);
            }
        } catch (\Exception $ex) {
            // Tambahkan logging atau tangani exception sesuai kebutuhan Anda
            return response()->json(['status' => 'error']);
        }

        return response()->json(['status' => 'success']);
    }


    public static function setLastKodeRegister($cKode)
    {
        try {
            $vaData = DB::table('nomorfaktur')
                ->select('Kode', 'ID')
                ->where('Kode', '=', '$cKode')
                ->first();
            if ($vaData) {
                $nId = $vaData->ID;
                $nId++;
                $vaData->ID = $nId;
                $vaData->save();
            } else {
                $nId = 1;
                $vaData = NomorFaktur::create([
                    'Kode' => $cKode,
                    'ID' => $nId
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function getTglTransaksi()
    {
        $cValueReturn = "";
        $dTglTransaksi = "";
        try {
            $vaData = DB::table('tgltransaksi')
                ->select('Tgl')
                ->where('Status', '=', '0')
                ->first();
            if ($vaData) {
                $dTglTransaksi = $vaData->Tgl;
            }
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error']);
        }
        $cValueReturn = (string)$dTglTransaksi;

        return $cValueReturn;
    }

    public static function getLastKodeRegister($cKey, $cLen)
    {
        $cValueReturn = '';
        $nId = 0;
        try {
            $vaData = DB::table('nomorfaktur')
                ->select('Kode', 'ID')
                ->where('Kode', '=', str_replace(' ', '', $cKey))
                ->first();
            if ($vaData) {
                $nId = $vaData->ID;
                $nId++;
            } else {
                $cValue = str_replace(' ', '', $cKey);
                NomorFaktur::create(['Kode' => $cValue]);
                $vaData =  DB::table('nomorfaktur')
                    ->select('Kode', 'ID')
                    ->where('Kode', $cValue)
                    ->first();
                if ($vaData) {
                    $nId = $vaData->ID;
                    $nId++;
                }
            }

            $cValueReturn = (string) $nId;
            $cValueReturn = str_pad($cValueReturn, $cLen, '0', STR_PAD_LEFT);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error']);
        }

        return $cValueReturn;
    }

    public static function getLastIdRegister($key)
    {
        $valueReturn = 0;
        try {
            $query = NomorFaktur::select('Kode', 'Id')->where('Kode', str_replace(' ', '', $key))
                ->first();

            if ($query) {
                $valueReturn = (float) $query->Id;
                $valueReturn++;
            } else {
                $value = str_replace(' ', '', $key);
                NomorFaktur::create(['Kode' => $value]);
                $query = NomorFaktur::select('Kode', 'Id')->where('Kode', $value)->first();

                if ($query) {
                    $valueReturn = (float) $query->Id;
                }
            }
        } catch (\Exception $ex) {
            // Tangani kesalahan dan kembalikan respons JSON dengan status error
            return response()->json(['status' => 'error']);
        }

        return $valueReturn;
    }


    public static function getDBConfig($KEY)
    {
        try {
            $result = '';
            $query = Config::where('KODE', $KEY)->first();
            if ($query) {
                $result = $query->Keterangan;
            }
            return $result;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function setDBConfig($key, $value)
    {
        try {
            $data = [
                "Keterangan" => $value
            ];
            Config::where('Kode', $key)->update($data);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function getTabungan(
        $REKENING,
        $TGLAWAL,
        $TGLAKHIR
    ) {
        $instance = new self();
        $RETVAL = [];
        if (empty($RETVAL)) {
            $startDate = Carbon::parse($TGLAWAL);
            $endDate = Carbon::parse($TGLAKHIR);

            $dTglHarian = $startDate;
            // while ($dTglHarian->lte($endDate)) {
            //     $formattedDate = $dTglHarian->format('Y-m-d');
            //     $RETVAL['Mutasi'][$formattedDate]['Debet'] = 0;
            //     $RETVAL['Mutasi'][$formattedDate]['Kredit'] = 0;
            //     $dTglHarian->addDay();
            // }
            while ($dTglHarian->lte($endDate)) {
                $formattedDate = $dTglHarian->format('Y-m-d');
                $RETVAL['Mutasi'][$formattedDate]['Debet'] = 0;
                $RETVAL['Mutasi'][$formattedDate]['Kredit'] = 0;
                $RETVAL['Mutasi'][$formattedDate]['Bunga'] = 0; // Tambahkan inisialisasi Bunga
                $RETVAL['Mutasi'][$formattedDate]['Pajak'] = 0; // Tambahkan inisialisasi Pajak
                $dTglHarian->addDay();
            }
        }
        $RETVAL['Bunga'] = 0;
        $RETVAL['Pajak'] = 0;
        $RETVAL['Saldo Awal'] = 0;
        $RETVAL['Saldo Akhir'] = 0;
        // Saldo Awal
        $query = DB::table('tabungan as t')
            ->select(
                'g.SaldoMinimumDapatBunga',
                DB::raw("IFNULL(SUM(IF(m.Tgl < '$TGLAWAL', m.Kredit - m.Debet, 0)), 0) as Saldo"),
                't.GolonganTabungan',
                'g.AdministrasiTahunan',
                'g.AdministrasiBulanan',
                DB::raw('g.AdminPasif as AdministrasiPasif'),
                'g.CaraPerhitungan',
                'g.WajibPajak',
                DB::raw("IFNULL(r.kodeinduk, '') as KodeInduk"),
                'r.Kode'
            )
            ->leftJoin('golongantabungan as g', 'g.kode', '=', 't.golongantabungan')
            ->leftJoin('mutasitabungan as m', function ($join) use ($TGLAWAL) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<', $TGLAWAL);
            })
            ->leftJoin('registernasabah as r', 'r.kode', '=', 't.kode')
            ->where('t.rekening', '=', $REKENING)
            ->groupBy(
                'g.SaldoMinimumDapatBunga',
                't.GolonganTabungan',
                'g.AdministrasiTahunan',
                'g.AdministrasiBulanan',
                'g.AdminPasif',
                'g.CaraPerhitungan',
                'g.WajibPajak',
                'r.kodeinduk',
                'r.Kode'
            )
            ->limit(1)
            ->get();
        if ($query) {
            $nSaldoAwal = 0;
            $nSaldoAkhir = 0;
            $nSaldoMinimum = 0;
            $nSaldoMinimumDapatBunga = 0;
            $nAdmBulanan = 0;
            $nAdmPasif = 0;
            $nAdmTahunan = 0;
            $nSaldoAkhirBulanKemarin = 0;
            $nBunga = 0;
            $nPajak = 0;
            $nPersenPajak = 20;
            $cKodeInduk = '';
            $nPersenPajak = 20;
            $nSaldoAwal = $query->first()->Saldo;
            $nAdmTahunan = $query->first()->AdministrasiTahunan;
            $nSaldoMinimum = $query->first()->Saldo;
            $nSaldoMinimumDapatBunga = $query->first()->SaldoMinimumDapatBunga;
            $nAdmBulanan = $query->first()->AdministrasiBulanan;
            $nAdmPasif = $query->first()->AdministrasiPasif;
            $wajibPajak = $query->first()->WajibPajak;
            $caraPerhitungan = $query->first()->CaraPerhitungan;
            $golTabungan = $query->first()->GolonganTabungan;
            $kodeInduk = $query->first()->KodeInduk;
            if (empty($kodeInduk)) {
                $kodeInduk = $query->first()->Kode;
            }
        }
        // Mutasi Tabungan
        $queryResult = DB::table('mutasitabungan as m')
            ->select(
                'm.KodeTransaksi',
                'm.Tgl',
                DB::raw('SUM(m.Debet) as Debet'),
                DB::raw('SUM(m.Kredit) as Kredit'),
                't.GolonganTabungan',
                't.GolonganNasabah'
            )
            ->leftJoin('tabungan as t', 't.rekening', '=', 'm.Rekening')
            ->where('m.Rekening', '=', $REKENING)
            ->whereBetween('m.Tgl', [
                $TGLAWAL,
                $TGLAKHIR
            ])
            ->groupBy('m.KodeTransaksi', 'm.Tgl', 't.GolonganTabungan', 't.GolonganNasabah')
            ->orderBy('m.Tgl', 'asc')
            ->get();
        foreach ($queryResult as $m) {
            $kodeTransaksi = $m->KodeTransaksi;
            if ($kodeTransaksi !== $instance->getDBConfig('msKodeBungaTabungan') && $kodeTransaksi !== $instance->getDBConfig('msKodePajakBungaTabungan')) {
                $tglMutasi = $m->Tgl;
                $RETVAL['Mutasi'][$tglMutasi]['Debet'] += $m->Debet;
                $RETVAL['Mutasi'][$tglMutasi]['Kredit'] += $m->Kredit;
            }
        }
        $saldoTabungan = $nSaldoAwal;
        foreach ($RETVAL['Mutasi'] as $key => $value) {
            $tglHarian = $key;
            $saldoTabungan += $value['Kredit'] - $value['Debet'];
            $sukuBunga = $instance->getSukuBungaTabungan($golTabungan, $tglHarian, $saldoTabungan);
            // $sukuBunga = 50;
            $nBungaHarian = $saldoTabungan * $sukuBunga / 100 / 365;
            $nPajakHarian = 0;
            // Saldo tabungan > 7500000 kena pajak
            if ($saldoTabungan > 7500000) {
                $nTotalKekayaan = $saldoTabungan;
            } else {
                $nTotalKekayaan = $instance->getTotalKekayaan($kodeInduk, $tglHarian, $REKENING);
            }
            if ($nTotalKekayaan > 75000000) {
                $nPajakHarian = $nBungaHarian * $nPersenPajak / 100;
            }
            if ($nBunga > 240000) {
                $nPajak = $nBunga * 10 / 100;
            }
            if ($saldoTabungan < $nSaldoMinimumDapatBunga) {
                $nBungaHarian = 0;
                $nPajakHarian = 0;
            }
            $RETVAL['Mutasi'][$key]['Bunga'] = $nBungaHarian;
            $RETVAL['Mutasi'][$key]['Pajak'] = $nPajakHarian;
            $RETVAL['Mutasi'][$key]['Saldo Akhir'] = $saldoTabungan;
            $RETVAL['Mutasi'][$key]['Suku Bunga'] = $sukuBunga;
            $nBunga += $nBungaHarian;
            $nPajak += $nPajakHarian;
        }
        if ($caraPerhitungan == '2') {
            $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR);
            $nSaldoTabunganRataRata = $instance->GetSaldoRataRata($REKENING, $TGLAKHIR);
            $sukuBunga = $instance->getSukuBungaTabungan($golTabungan, $TGLAKHIR, $saldoTabungan);
            $nBunga = $nSaldoTabunganRataRata * $sukuBunga / 100 / 12;
            // Jika bunga diatas 240000 kena pajak
            $nTotalKekayaan = $instance->getTotalKekayaan($kodeInduk, $TGLAKHIR, $REKENING);

            if ($nBunga > 240000) {
                $nPajak = $nBunga * 10 / 100;
            }
            if ($saldoTabungan < $nSaldoMinimumDapatBunga) {
                $nBunga = 0;
                $nPajak = 0;
            }
        }
        $nPajak = 0;
        if ($nBunga > 240000) {
            $nPajak = $nBunga * 10 / 100;
        }
        $nBunga = round($nBunga, 0);
        $nPajak = round($nPajak, 0);
        // jika saldo tabungan  = 0
        if ($saldoTabungan == 0) {
            $nBunga = 0;
            $nPajak = 0;
        }
        // wajib pajak atau tidak
        if ($wajibPajak == "T") {
            $nPajak = 0;
        }
        // $RETVAL['Bunga'] = $nBunga;
        $RETVAL['Bunga'] = PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR) * self::getDBConfig('msPersenBungaSimpanan') / 100;
        // echo self::getDBConfig('msPersenBungaSimpanan');
        // $RETVAL['Pajak'] = $nPajak;
        if (PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR) > 7500000) {
            $nPajak = (PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR) * self::getDBConfig('msPersenBungaSimpanan') / 100) * 20 / 100;
            // echo "Woul...";
        }
        $RETVAL['Pajak'] = $nPajak;
        // echo $nPajak ."---------------wiiiii";
        // $RETVAL['Pajak'] = self::getSaldoTabungan($REKENING, $TGLAKHIR);
        // if(PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR) > 7500000){
        //     $RETVAL['Pajak'] = PerhitunganTabungan::getSaldoTabungan($REKENING, $TGLAKHIR)*20/100;
        // }
        $RETVAL['Saldo Awal'] = $nSaldoAwal;
        $RETVAL['Saldo Akhir'] = $saldoTabungan;
        return $RETVAL;
    }

    public static function getSaldoRataRata($REKENING, $TGL)
    {
        $instance = new self();
        $cTglAwal = Carbon::parse($TGL)->startOfMonth()->format('Y-m-d');
        $cTglAkhir = Carbon::parse($TGL)->format('Y-m-d');
        $nHari = Carbon::parse($cTglAkhir)->day;
        $nSaldoAkhirBulanKemarin = 0;
        $nSaldoMinimum = 0;
        $nSaldoAkhir = 0;

        $saldoAkhirBulanKemarin = DB::table('mutasitabungan')
            ->select(DB::raw('SUM(Kredit - Debet) as Saldo'))
            ->where('rekening', '=', $REKENING)
            ->where('Tgl', '<', $cTglAwal)
            ->groupBy('rekening')
            ->first();

        if ($saldoAkhirBulanKemarin) {
            $nSaldoAkhirBulanKemarin = $saldoAkhirBulanKemarin->Saldo;
        }

        $nSaldoAkhir = $nSaldoAkhirBulanKemarin;
        $nSaldoMinimum = $nSaldoAkhir;

        $cKodeTransaksiBunga = $instance->getDBConfig("msKodeBungaTabungan");
        $cKodeTransaksiPajak = $instance->getDBConfig("msKodePajakBungaTabungan");

        $queryMutasiTab = DB::table('mutasitabungan as m')
            ->leftJoin('tabungan as t', 't.rekening', '=', 'm.rekening')
            ->select('m.Tgl', DB::raw('SUM(m.Debet) as Debet'), DB::raw('SUM(m.Kredit) as Kredit'))
            ->where('m.Rekening', '=', $REKENING)
            ->where('m.Tgl', '>=', $cTglAwal)
            ->where('m.Tgl', '<=', $cTglAkhir)
            ->whereNotIn('m.kodetransaksi', [$cKodeTransaksiBunga, $cKodeTransaksiPajak])
            ->groupBy('m.Tgl')
            ->get();

        foreach ($queryMutasiTab as $dbRow) {
            $nSaldoAkhir += $dbRow->Kredit - $dbRow->Debet;
        }

        $nSaldoRataRata = $nSaldoAkhir / $nHari;
        return $nSaldoRataRata;
    }


    public static function getTotalKekayaan($cKodeInduk, $dTgl, $cRekening = '')
    {
        $nTotalKekayaan = 0;
        $vaData = DB::table('registernasabah')
            ->select('Kode')
            ->where('KodeInduk', $cKodeInduk)
            ->orWhere('Kode', $cKodeInduk)
            ->first();
        if ($vaData) {
            $cKode = $vaData->Kode;
            $cTable = ['tabungan', 'deposito'];
            foreach ($cTable as $key => $value) {
                if (!empty($cKode)) {
                    $vaData2 = DB::table($value)
                        ->select('Rekening')
                        ->where('Kode', $cKode)
                        ->where('rekening', '<>', $cRekening)
                        ->get();
                    foreach ($vaData2 as $qt) {
                        $rekening = $qt->Rekening;
                        if ($value == 'deposito') {
                            $nSaldo = PerhitunganDeposito::getNominalDeposito($rekening, $dTgl);
                        } else if ($value == 'tabungan') {
                            $nSaldo = PerhitunganTabungan::getSaldoTabungan($rekening, $dTgl);
                        }
                        $nTotalKekayaan += $nSaldo;
                        if ($nTotalKekayaan > 7500000) {
                            return $nTotalKekayaan;
                        }
                    }
                }
            }
        }
    }

    public static function getSukuBungaTabungan($GOLTAB, $TGL, $SALDOTAB)
    {
        $retval = 0;
        $query = DetailSukuBunga::where('Kode', $GOLTAB)
            ->where('Tgl', $TGL)
            ->where('Maximum', '>=', $SALDOTAB)
            ->orderByDesc('Tgl')
            ->first();
        if ($query) {
            $retval = $query->SukuBunga;
        }
        return $retval;
    }

    public static function getSaldoSimpananAnggota($REKENING)
    {
        $query = DB::table('mutasianggota')
            ->select(
                'Kode',
                DB::raw('IFNULL(SUM(kreditpokok - debetpokok), 0) AS Pokok'),
                DB::raw('IFNULL(SUM(kreditwajib - debetwajib), 0) AS Wajib')
            )
            ->where('Kode', $REKENING)
            ->groupBy('Kode')
            ->first();
        // if ($query) {
        //     $retval = $query->SukuBunga;
        // }
        return $query;
    }

    public static function getPasif($REKENING, $TGL)
    {
        $instance = new self();
        $return = 1;
        $TGL = Func::Date2String($TGL);
        $kodeBungaTabungan = $instance->getDBConfig('msKodeBungaTabungan');
        $kodePajakTabungan = $instance->getDBConfig('msKodePajakBungaTabungan');
        $kodeAdmBulanan = $instance->getDBConfig('msKodeAdmBulanan');
        $kodeAdmPasif = $instance->getDBConfig('msKodeAdmPasif');
        $lama = $instance->getLamaPasif($REKENING) * -1;
        $golTabungan = $instance->getGolongan($REKENING);
        $saldoPasif = $instance->getKeterangan($golTabungan, 'SaldoPasif', 'GolonganTabungan');
        $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($REKENING, $TGL);
        $tglAwal = Carbon::createFromFormat('Y-m-d', $TGL)->addMonths($lama)->format('Y-m-d');
        $tglAwal = Carbon::createFromFormat('Y-m-d', $tglAwal)->endOfMonth()->format('Y-m-d');
        $query = MutasiTabungan::where('Tgl', '>', $tglAwal)
            ->where('Tgl', '<=', $TGL)
            ->where('KodeTransaksi', '<>', $kodeBungaTabungan)
            ->where('KodeTransaksi', '<>', $kodePajakTabungan)
            ->where('KodeTransaksi', '<>', $kodeAdmBulanan)
            ->where('KodeTransaksi', '<>', $kodeAdmPasif)
            ->where('Rekening', $REKENING)
            ->first();
        if ($query > 0 || $saldoTabungan > $saldoPasif) {
            return 0;
        }

        return 1;
    }

    public static function getLamaPasif($REKENING)
    {
        $query = Tabungan::with('golsimpanan')
            ->where('Rekening', $REKENING)
            ->first();
        if ($query) {
            $lama = $query->golsimpanan->Lama;
        }
        return $lama;
    }

    // DARI NOMOR FAKTUR
    // public static function getRekening($key, $len)
    // {
    //     try {
    //         return $key;
    //         // 1 => TABUNGAN; 2 => DEPOSITO, 3 => KREDIT, 0 => ANGGOTA
    //         $type = 0;
    //         if ($key == '1') {
    //             $type = 1;
    //         } else if ($key == '2') {
    //             $type = 2;
    //         } else if ($key == '3') {
    //             $type = 3;
    //         } else if ($key == '0') {
    //             $type = 0;
    //         }
    //         $cabang = self::getDBConfig('msKodeCabang');
    //         $valueReturn = str_pad(self::getLastKodeRegister($key, $len), $len, '0', STR_PAD_LEFT);
    //         $valueReturn = $cabang . $type . $valueReturn;
    //         return $valueReturn;
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'error']);
    //     }
    // }

    public static function getRekening($key, $len)
    {
        try {
            $type = self::getTypeFromKey($key);
            $cabang = self::getDBConfig('msKodeCabang');
            $valueReturn = self::generatePaddedValue($key, $len);
            $valueReturn = $cabang . $type . $valueReturn;
            return $valueReturn;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    private static function getTypeFromKey($key)
    {
        // 1 => TABUNGAN; 2 => DEPOSITO, 3 => KREDIT, 0 => ANGGOTA
        $type = 0;
        if ($key == '1') {
            $type = 1;
        } else if ($key == '2') {
            $type = 2;
        } else if ($key == '3') {
            $type = 3;
        } else if ($key == '0') {
            $type = 0;
        }
        return $type;
    }

    private static function generatePaddedValue($key, $len)
    {
        $value = str_pad(self::getLastKodeRegister($key, $len), $len, '0', STR_PAD_LEFT);
        return $value;
    }

    public static function setRekening($key)
    {
        try {
            $result = NomorFaktur::select('Kode', 'ID')->where('Kode', $key)->first();
            if ($result) {
                $id = $result->ID;
                $id++;
                $result->ID = $id;
                $result = NomorFaktur::where('Kode', $key)->update(['ID' => $id]);
            } else {
                $id = 1;
                $result = NomorFaktur::create([
                    'Kode' => $key,
                    'ID' => $id
                ]);
            }
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    // public static function setRekening($key)
    // {
    //     try {
    //         $result = NomorFaktur::select('Kode', 'ID')->where('Kode', $key)->first();

    //         if ($result) {
    //             $id = $result->ID + 1;
    //             $id++;
    //             $result->update(['ID' => $id]);
    //         } else {
    //             $id = 1;
    //             NomorFaktur::create([
    //                 'Kode' => $key,
    //                 'ID' => $id
    //             ]);
    //         }
    //     } catch (\Exception $ex) {
    //         dd($ex);
    //         return response()->json(['status' => 'error']);
    //     }

    //     return response()->json(['status' => 'success']);
    // }


    // TABEL
    // public static function getRekening($RANDOM, $TYPE, $CABANG)
    // {
    //     try {
    //         $instance = new self();
    //         if (empty($CABANG)) {
    //             $CABANG = $instance->getDBConfig('msKodeCabang');
    //         }
    //         $len = 7;
    //         if ($TYPE == 0) {
    //             $len = 8;
    //         }
    //         $table = ['registernasabah2', 'tabungan', 'deposito', 'debitur'];
    //         $indexTable = $table[$TYPE];

    //         // Check if any records exist in the table
    //         $exists = DB::table($indexTable)
    //             ->where('kode', 'LIKE', $CABANG . '%')
    //             ->exists();

    //         if (empty($RANDOM) || !$exists) {
    //             // If RANDOM is empty or no records exist, take the latest value from the database
    //             $queryLastRekening = DB::table($indexTable)
    //                 ->select(DB::raw("MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)) as lastrekening"))
    //                 ->where('kode', 'LIKE', $CABANG . '%')
    //                 ->value('lastrekening');

    //             $RANDOM = ($queryLastRekening ?? 0) + 1;
    //         }

    //         $RANDOM = str_pad($RANDOM, $len, '0', STR_PAD_LEFT);

    //         return $CABANG . $TYPE . $RANDOM;
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'error']);
    //     }
    // }

    // public static function getRekening($RANDOM, $TYPE, $CABANG)
    // {
    //     try {
    //         $instance = new self();

    //         // Jika CABANG kosong, ambil dari konfigurasi database
    //         if (empty($CABANG)) {
    //             $CABANG = $instance->getDBConfig('msKodeCabang');
    //         }

    //         $len = 7;
    //         // Jika TYPE adalah 0, gunakan panjang 8
    //         if ($TYPE == 0) {
    //             $len = 8;
    //         }

    //         $table = ['registernasabah2', 'tabungan', 'deposito', 'debitur'];
    //         $indexTable = $table[$TYPE];

    //         // Periksa apakah ada record di tabel
    //         $exists = DB::table($indexTable)
    //             ->where('kode', 'LIKE', $CABANG . '%')
    //             ->exists();

    //         if (empty($RANDOM) || !$exists) {
    //             // Jika RANDOM kosong atau tidak ada record, ambil nilai terakhir dari database
    //             $queryLastRekening = DB::table($indexTable)
    //                 ->select(DB::raw("MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)) as lastrekening"))
    //                 ->where('kode', 'LIKE', $CABANG . '%')
    //                 ->value('lastrekening');

    //             // Ambil nilai terakhir, tambahkan 1 (jika ada data) atau setel ke 1 (jika tidak ada data)
    //             $RANDOM = (empty($RANDOM) && $queryLastRekening !== null) ? $queryLastRekening + 1 : $RANDOM;
    //         }
    //         // Format RANDOM dengan panjang yang sesuai
    //         $RANDOM = str_pad($RANDOM, $len, '0', STR_PAD_LEFT);

    //         // Gabungkan CABANG, TYPE, dan RANDOM untuk menghasilkan nomor rekening
    //         return $CABANG . $TYPE . $RANDOM;
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'error']);
    //     }
    // }
    // END TABEL

    public static function getKeterangan($KODE, $FIELD, $TABLE)
    {
        // dd($KODE." ".$FIELD . " " . $TABLE);
        $table = strtolower($TABLE);
        $keterangan = '';
        $query = DB::table($table)
            ->select($FIELD . ' as Keterangan')
            ->where('Kode', '=', $KODE)
            ->first();
        if ($query) {
            $keterangan = $query->Keterangan;
        }
        return $keterangan;
    }

    public static function getSaldoKasTeller($rekeningTeller, $tgl)
    {
        $saldoTeller = 0;
        $data
            = DB::table('bukubesar')
            ->select(DB::raw('sum(debet - kredit) as Saldo'))
            ->where('Rekening', $rekeningTeller)
            ->where('Tgl', '<=', $tgl)
            ->groupBy('Rekening')
            ->first();
        if ($data) {
            $saldoTeller = $data->Saldo;
        }
        return $saldoTeller;
    }

    public static function getRekeningAntarKantor($cabangEntry, $cabangNasabah)
    {
        $va = [
            "RekeningAKP" => "",
            "RekeningAKA" => "",
            "RekeningAKLawan" => "",
            "RekeningAKEntry" => ""
        ];
        if ($cabangNasabah !== $cabangEntry) {
            $data = Cabang::where('Kode', $cabangNasabah)->first();
            if ($data) {
                $va['RekeningAKP'] = $data->RekeningAKP;
                $va['RekeningAKLawan'] = $data->RekeningAKP;
                if ($cabangEntry == 100) {
                    $va['RekeningAKLawan'] = $data->RekeningAKA;
                }
            }
            $data2 = Cabang::where('Kode', $cabangEntry)->first();
            if ($data2) {
                $va['RekeningAKA'] = $data2->RekeningAKP;
                $va['RekeningAKEntry'] = $data2->RekeningAKP;
                if ($cabangNasabah == 100) {
                    $va['RekeningAKEntry'] = $data2->RekeningAKA;
                }
            }
        }
        return $va;
    }

    public static function getKasTeller($username, $tgl = "0000-00-00")
    {
        $instance = new self();
        $kasTeller = "";
        if ($tgl == "0000-00-00") {
            $tgl = $instance->getTglTransaksi();
        }
        $data = Username::where('UserName', $username)->first();
        if ($data) {
            $kasTeller = $data->KasTeller;
        }
        $data2 = UsernameKantorKas::where('UserName', $username)
            ->where('Tgl', $tgl)
            ->orderByDesc('Tgl')
            ->first();
        if ($data2) {
            $kasTeller = $data->KasTeller;
        }
        return $kasTeller;
    }

    public static function getUrutFaktur($faktur)
    {
        $valueReturn = [
            'USERNAME' => '',
            'DATETIME' => Carbon::now(),
            'ID' => '',
        ];

        // Data Setelah 12 Bulan Bisa di Hapus biar tidak terlalu besar
        $dTglAwal = now()->subMonths(12)->format('Y-m-d');
        UrutFaktur::where('tgl', '=', $dTglAwal)->delete();

        try {
            $result = UrutFaktur::select('ID', 'UserName', 'DateTime')
                ->where('Faktur', $faktur)
                ->first();

            if ($result) {
                $valueReturn['USERNAME'] = $result->UserName;
                $valueReturn['DATETIME'] = $result->DateTime;
                $valueReturn['ID'] = $result->ID;
            } else {
                $value = [
                    'TGL' => now()->format('Y-m-d'),
                    'FAKTUR' => $faktur,
                    'DATETIME' => $valueReturn['DATETIME'],
                    'USERNAME' => $valueReturn['USERNAME'],
                ];

                UrutFaktur::insert($value);

                $result = UrutFaktur::selectRaw('IFNULL(MAX(ID), 1) as ID')
                    ->first();

                $valueReturn['ID'] = $result->ID;
            }
        } catch (\Exception $ex) {
            // Handle the exception
            return response()->json(['status' => 'error']);
        }

        return $valueReturn;
    }

    public static function getCabang($username, $tgl)
    {
        $data = Username::where('Username', $username)->first();
        if ($data) {
            $CABANG = $data->Cabang;
            $data2 = UsernameKantorKas::where('Username', $username)
                ->where('Tgl', $tgl)
                ->orderByDesc('Tgl')
                ->first();
            if ($data2) {
                $CABANG = $data2->Cabang;
            }
        }
        if (empty($CABANG)) {
            $CABANG = '101';
        }
        return $CABANG;
    }

    public static function getRate($tgl, $rekening)
    {
        $sukuBunga = 0;
        $data = DepositoSukuBunga::where('Rekening', $rekening)
            ->where('Tgl', '<=', $tgl)
            ->orderByDesc('Tgl')
            ->first();
        if (empty($data)) {
            $data2 = Deposito::where('Rekening', $rekening)->first();
            if ($data2) {
                $sukuBunga = $data2->SukuBunga;
            }
        } else {
            $sukuBunga = $data->Sukubunga;
        }
        return $sukuBunga;
    }

    public static function getTglMutasiRekening($rekening, $table, $tgl)
    {
        $data = DB::table($table)->where('Rekening', $rekening)
            ->where('Tgl', '<=', $tgl)
            ->orderByDesc('Tgl')
            ->first();
        if ($data) {
            $tgl = $data->Tgl;
        }
        return $tgl;
    }

    public static function getNamaRegisterNasabah($rekening)
    {
        $jenis = substr($rekening, 3, 1);
        // return $jenis;
        $jenisRek = 'debitur';
        if ($jenis == 1) {
            $jenisRek = 'tabungan';
        } else if ($jenis == 2) {
            $jenisRek = 'deposito';
        }
        $data = DB::table($jenisRek . ' as d')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->where('d.rekening', $rekening)
            ->select('r.Nama')
            ->first();
        if ($data) {
            $nama = $data->Nama;
        } else {
            $nama = '';
        }
        return $nama;
    }


    public static function getDataPencairanDeposito($rekening, $tgl, $nominal = false)
    {
        // $tgl = '2015-01-01'; //GetterSetter::getTglTransaksi();
        // $rekening = $request->Rekening;
        $data = DB::table('deposito AS d')
            ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongandeposito AS g', 'g.Kode', '=', 'd.GolonganDeposito')
            ->select(
                'd.CaraPerhitungan',
                'r.Nama',
                'r.Alamat',
                'g.Lama',
                'g.Bunga',
                'd.Tgl',
                'd.JthTmp',
                'd.RekeningTabungan'
            )
            ->where('d.Rekening', '=', $rekening)
            ->first();
        if ($data) {
            $caraPerhitungan = $data->CaraPerhitungan;
            $nama = $data->Nama;
            $alamat = $data->Alamat;
            $jangkaWaktu = $data->Lama;
            $nominalDeposito = PerhitunganDeposito::getNominalDeposito($rekening, $tgl);
            // $sukuBunga = GetterSetter::getRate($tgl, $rekening);
            $sukuBunga = $data->Bunga;
            $sukuBungaPersen = $sukuBunga / 100;
            // $jthTmp = Carbon::parse(PerhitunganDeposito::getTglJthTmpDeposito($rekening, $tgl));
            // $tglValuta = $jthTmp->copy()->subMonths($jangkaWaktu);
            $tglValuta = $data->Tgl;
            $jthTmp = $data->JthTmp;
            $jthTmpFormatted = date("d-m-Y", strtotime($jthTmp));
            $tglValutaFormatted = date("d-m-Y", strtotime($tglValuta));
            $rekTabungan = $data->RekeningTabungan;
            if (!empty($rekTabungan)) {
                $namaTabungan = GetterSetter::getNamaRegisterNasabah($rekTabungan);
                $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($rekTabungan, $tgl);
            } else {
                $namaTabungan = "";
                $saldoTabungan = "";
            }
            $cadanganBunga = 0;
            // $vaPencairan = GetPencairanDeposito($cRekening, $dTgl);
            // $nAccrual = round($vaPencairan['Accrual']);
            // $nBunga   = $vaPencairan['Bunga'];
            $bunga = intval($nominalDeposito * $sukuBungaPersen / 12);
            // $nPajak   = $vaPencairan['Pajak'];
            // $nNominal = $vaPencairan['Nominal'];
            // $nTotal   = $vaPencairan['Nominal'] + $vaPencairan['Bunga'] + $nCadanganBunga - $vaPencairan['Pajak'];
            // $nCadanganBunga = $nCadanganBunga;
            $nPinalti = "0.00";
            // $nTotal   = $nTotal;
            // $dTgl = String2Date($dTgl);
            $cKeterangan = "Mutasi Deposito [" . $rekening . "] A.N " . $nama;
        }
        $array = [
            'Faktur' => GetterSetter::getLastFaktur("DP", 6),
            'NominalDeposito' => $nominalDeposito,
            'SukuBunga' => $sukuBunga,
            'JangkaWaktu' => $jangkaWaktu,
            'TglValuta' => $tglValutaFormatted,
            'JthTmp' => $jthTmpFormatted,
            'RekTabungan' => $rekTabungan,
            'SaldoTabungan' => $saldoTabungan,
            'NamaTabungan' => $namaTabungan,
            'Nominal' => $nominalDeposito,
            'Bunga' => $bunga
        ];
        return response()->json($array);
    }

    public static function GetTglIdentik($dTglAwal, $dTglAkhir)
    {
        $i = "";
        $n = 0;
        $dTglAwal = Carbon::parse($dTglAwal)->format('Y-m-d');
        $dTglAkhir = Carbon::parse($dTglAkhir)->format('Y-m-d');
        $dTgl = $dTglAwal;

        if ($dTglAwal <= $dTglAkhir) {
            for ($i = $dTglAwal; $i <= $dTglAkhir; $i = Carbon::parse($dTglAwal)->addMonthsNoOverflow($n)->format('Y-m-d')) {
                if ($i <= $dTglAkhir) {
                    $dTgl = $i;
                }
                $n++;
            }
        }
        return $dTgl;
    }

    public static function getBakiDebet($rekening, $tgl)
    {
        $tgl = Carbon::parse($tgl)->format('Y-m-d');
        $nSaldo = 0;
        $data = DB::table('angsuran')
            ->selectRaw('SUM(DPokok - KPokok) as Saldo')
            ->where('Rekening', $rekening)
            ->whereDate('Tgl', '<=', $tgl)
            ->groupBy('Rekening') // Menambahkan GROUP BY clause
            ->orderBy('Rekening')
            ->first();

        if ($data) {
            $nSaldo = $data->Saldo;
        }
        return $nSaldo;
    }

    public static function getAdendum($cRekening, $dTgl = "0000-00-00")
    {
        $instance = new self();
        $nSystemPlafond = '';
        $dTgl = Carbon::parse($dTgl); // Mengubah tanggal menjadi objek Carbon

        // Mengambil data debitur
        $debitur = DB::table('debitur')
            ->select('Rekening', 'Tgl', 'CaraPerhitungan', 'Lama', 'Plafond', 'Musiman', 'SukuBunga', 'Provisi')
            ->where('rekening', $cRekening)
            ->orderBy('Rekening')
            ->first();
        if ($debitur) {
            $va = [
                'Tgl' => $debitur->Tgl,
                'TglRealisasiAwal' => $debitur->Tgl,
                'CaraPerhitungan' => $debitur->CaraPerhitungan,
                'Lama' => $debitur->Lama,
                'LamaKredit' => $debitur->Lama,
                'Plafond' => $debitur->Plafond,
                'Adendum' => false,
                'Musiman' => $debitur->Musiman,
                'SukuBunga' => $debitur->SukuBunga,
                'TglAdendum' => '0000-00-00',
                'Provisi' => $debitur->Provisi,
            ];
        }

        // Mengambil data suku bunga dari tabel debitur_sukubunga
        $sukuBungaRow = DB::table('debitur_sukubunga')
            ->select('sukubunga', 'tgl')
            ->where('rekening', $cRekening)
            ->whereDate('tgl', '<=', $dTgl)
            ->orderByDesc('tgl')
            ->first();

        if ($sukuBungaRow) {
            $va['SukuBunga'] = $sukuBungaRow->sukubunga;
        }

        // Menentukan tanggal realisasi awal
        $vaTglRealisasi = Carbon::parse($va['Tgl'])->toArray();

        if (isset($vaTglRealisasi)) {
            $va['Adendum'] = false;

            // Mengambil data adendum
            $adendum = DB::table('adendum')
                ->select('Tgl', 'CaraPerhitungan', 'Lama', 'Plafond', 'Acuan', 'SukuBunga', 'Provisi', 'SystemPlafond')
                ->where('Rekening', $cRekening)
                ->whereDate('Tgl', '<=', $dTgl)
                ->where('faktur', '<>', '')
                ->orderByDesc('Tgl')
                ->first();

            if ($adendum) {
                $vaTglAdendum = Carbon::parse($adendum->Tgl)->toArray();
                $va['TglAdendum'] = $adendum->Tgl;
                $va['Acuan'] = $adendum->Acuan;

                if ($adendum->Acuan == 1) {
                    $va['Tgl'] = $adendum->Tgl;
                }

                if ($adendum->Acuan == 0) {
                    $va['Lama'] += $adendum->Lama;
                } else {
                    $va['Lama'] = $adendum->Lama;
                }

                $va['SukuBunga'] = $adendum->SukuBunga;
                $va['Plafond'] += $adendum->Plafond;
                $nSystemPlafond = $adendum->SystemPlafond;

                if ($adendum->SystemPlafond && $va['CaraPerhitungan'] != 11) {
                    // Mendapatkan nilai getBakiDebet, yang harus Anda implementasikan sesuai dengan kebutuhan Anda
                    $va['Plafond'] = $instance->getBakiDebet($cRekening, $adendum->Tgl);
                }

                $va['Adendum'] = true;
                $va['Provisi'] = $adendum->Provisi;
            }
        }

        $dJTHTMP = Carbon::parse($va['Tgl'])->addMonths($va['Lama']);
        $nLamaKredit = $dJTHTMP->diffInMonths(Carbon::parse($va['TglRealisasiAwal']));
        $va['LamaKredit'] = $nLamaKredit;

        if ($nSystemPlafond) {
            $va['LamaKredit'] = $va['Lama'];
        }

        $va['SystemPlafond'] = $nSystemPlafond;

        return $va;
    }

    public static function getDetailJaminan($cRekening, $cNo, $cJaminan, $dTgl = "", $nID = "")
    {
        $cWhereJaminan = "a.Status=1";
        if (!empty($dTgl)) {
            $dTgl = Func::Date2String($dTgl);
            $cWhereJaminan .= " AND a.Tgl <='$dTgl' AND a.TglGanti > '$dTgl'";
        }
        if (!empty($nID)) {
            $cWhereJaminan .= " AND a.ID = '$nID'";
        }
        $vaArray = [];
        if ($cJaminan == '5') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'a.Kode',
                    'a.No',
                    'a.Tgl',
                    'a.Rekening',
                    'a.M_BPKB',
                    'a.Nama as AtasNama',
                    'a.M_Alamat',
                    'a.M_NoRangka',
                    'a.M_Type',
                    'a.M_Silinder',
                    'a.M_NoMesin',
                    'a.M_NoPolisi',
                    'a.M_Merk',
                    'a.M_Model',
                    'a.M_Tahun',
                    'a.M_Warna',
                    'a.NilaiJaminan',
                    'a.NoRegBPKB',
                    'a.L_Note',
                    'r.Nama as NamaDebitur',
                    'r.Alamat as AlamatDebitur',
                    'a.NilaiJaminan as THU',
                    'a.NilaiYangDiPerhitungkan as THLS',
                    'a.S_JenisPengikatan'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();

            if ($vaData) {
                $cIsiSilinder = trim($vaData->M_Silinder);
                if (!empty($cIsiSilinder)) {
                    $cIsiSilinder .= ' CC';
                }

                $cKeteranganJenisPengikatan = $vaData->S_JenisPengikatan . " - " . self::getKeterangan($vaData->S_JenisPengikatan, 'Keterangan', 'jenispengikatanjaminan');
                $cAtasNama = $vaData->AtasNama;
                if (empty($cAtasNama)) {
                    $cAtasNama = $vaData->AtasNama;
                }

                $vaArray[1] = [
                    "Merk" => $vaData->M_Merk,
                    "Model" => $vaData->M_Model,
                    "Type" => $vaData->M_Type,
                    "Tahun" => $vaData->M_Tahun,
                    "NoBPKB" => $vaData->M_BPKB,
                    "NoPolisi" => $vaData->M_NoPolisi,
                    "NoRangka" => $vaData->M_NoRangka,
                    "NoMesin" => $vaData->M_NoMesin,
                    "Warna" => $vaData->M_Warna,
                    "AtasNama" => $cAtasNama,
                    "Alamat" => $vaData->M_Alamat,
                ];

                $vaArray[2] = [
                    " " => "Dengan nilai penjaminan sebesar Rp. " . Func::getZFormat($vaData->THLS) . " (" . Func::Terbilang($vaData->THLS) . ")",
                ];

                $lNote = trim($vaData->L_Note);
                $LNOTE = trim(preg_replace('/\s\s+/', ' ', $vaData->L_Note));

                if (!empty($lNote)) {
                    $vaArray[1]['Keterangan'] = $LNOTE;
                }
            }
        } else if ($cJaminan == '6') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'r.Nama',
                    'a.Tgl',
                    'a.Kode',
                    'a.Rekening',
                    'a.L_Note',
                    'a.S_NIB',
                    'a.S_Nomor',
                    'a.S_Tgl',
                    'a.S_Agraria',
                    'a.S_NoDWG',
                    'a.S_TglDWG',
                    'a.S_Alamat',
                    'a.Nama as AtasNama',
                    'a.S_Provinsi',
                    'a.S_JenisSurat',
                    'a.S_Desa',
                    'a.S_Kecamatan',
                    'a.S_Kota',
                    'a.S_Luas',
                    'a.S_Keadaan',
                    'a.NilaiJaminan',
                    'a.NilaiYangDiPerhitungkan',
                    'a.S_Jenis',
                    'a.BatasUtara',
                    'a.BatasTimur',
                    'a.BatasSelatan',
                    'a.BatasBarat'
                )
                ->leftJoin('pengajuankredit as p', 'p.Jaminan', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();
            if ($vaData) {
                if ($vaData->S_Jenis == 1) {
                    $cJenis = "SHM NO";
                    $cAtas = "Sebidang Tanah Hak Milik";
                } else {
                    $cJenis = "SHGB NO";
                    $cAtas = "Sebidang Tanah Hak Guna Bangunan";
                }

                if ($vaData->S_JenisSurat == 1) {
                    $cJenisSurat = "GAMBAR SITUASI TANGGAL";
                } else {
                    $cJenisSurat = "Tanggal Surat Ukur ";
                }

                $cLuas = $vaData->S_Luas . " M2";
                $cLetakTanah = $vaData->S_Alamat . " " . $vaData->S_Desa . "  " . $vaData->S_Kecamatan . " " . $vaData->S_Kota . " " . $vaData->S_Provinsi;

                if ((!empty($S_Nomor)) || (!empty($S_NoDWG)) || (!empty($vaData->S_Luas)) || (!empty($vaData->AtasNama))) {
                    $vaArray[1] = [
                        $cJenis => $vaData->S_Nomor,
                        "NamaPemegangHak" => $vaData->AtasNama,
                        "NIB" => $vaData->S_NIB,
                        "TerletakDi" => $cLetakTanah,
                        $cJenisSurat => Func::GetFullDate(Func::String2Date($vaData->S_TglDWG)),
                        "No" => $vaData->S_NoDWG,
                        "Luas" => $cLuas,
                        "NilaiJaminan" => "Rp" . " " . Func::getZFormat($vaData->NilaiJaminan, 2),
                        "NilaiYangDiperhitungkan" => "Rp" . " " . Func::getZFormat($vaData->NilaiYangDiPerhitungkan, 2),
                        "  " => "DAN MELIPUTI PULA SEGALA YANG ADA DI ATAS TANAH TERSEBUT BAIK YANG SAAT INI ADA MAUPUN YANG ADA DI KEMUDIAN HARI",
                    ];
                    $vaArray[2] = [
                        " " => "Dengan nilai penjaminan sebesar Rp. " . func::getZFormat($vaData->NilaiYangDiPerhitungkan) . " (" . Func::Terbilang($vaData->NilaiYangDiPerhitungkan) . ")",
                    ];

                    $lNote = trim($vaData->L_Note);
                    $LNOTE = trim(preg_replace('/\s\s+/', ' ', $vaData->L_Note));

                    if (!empty($lNote)) {
                        $vaArray[1]['Keterangan'] = $LNOTE;
                    }
                } else {
                    $vaArray[1]  = ["Keterangan" => Func::String2SQL($vaData->L_Note)];
                }
            }
        } else if ($cJaminan == '2' || $cJaminan == '3') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'r.Nama',
                    'a.Tgl',
                    'a.Kode',
                    'a.Rekening',
                    'a.Nama as AtasNama',
                    'a.NilaiJaminan as Nominal',
                    'a.D_Rekening',
                    'a.D_Nominal',
                    'a.D_Jenis',
                    'a.D_Bilyet',
                    'a.L_Note',
                    'a.Alamat'
                )
                ->leftJoin('pengajuankredit as p', 'p.Jaminan', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();
            if ($vaData) {
                if ($vaData->D_Jenis == "1") {
                    $cGolongan = self::GetGolongan($vaData->D_Rekening);
                    $cKeterangan = "Rekening " . self::GetKeterangan($cGolongan, "Keterangan", "golongantabungan") . " ";
                } else {
                    $cKeterangan = "DEPOSITO BERJANGKA";
                }

                if ((!empty($vaData->D_Bilyet)) || (!empty($vaData->D_Rekening)) || (!empty($vaData->AtasNama))) {
                    $vaArray[1] = [
                        "Jenis" => $cKeterangan,
                        "No. Rekening" => $vaData->D_Rekening,
                        "No. Bilyet" => $vaData->D_Bilyet,
                        "Atas Nama" => $vaData->AtasNama,
                        "Nominal" => " Rp." . Func::getZFormat($vaData->D_Nominal, 0) . " (" . Func::Terbilang($vaData->D_Nominal) . " ) ",
                        "Dikeluarkan Oleh" => self::getDBConfig("msNama"),
                    ];
                    $vaArray[2] = [
                        " " => "Dengan nilai penjaminan sebesar Rp. " . func::getZFormat($vaData->THLS) . " (" . Func::Terbilang($vaData->THLS) . ")",
                    ];
                } else {
                    $lNote = trim($vaData->L_Note);
                    $LNOTE = trim(preg_replace('/\s\s+/', ' ', $vaData->L_Note));
                    $vaArray[1]  = ["Keterangan" => Func::ReplaceKarakterKhusus($LNOTE)];
                }
            }
        } else if ($cJaminan == '4') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'r.Nama',
                    'a.Tgl',
                    'a.Kode',
                    'a.Rekening',
                    'a.Nama as AtasNama',
                    'a.NilaiJaminan as Nominal',
                    'a.P_Uraian',
                    'a.P_Berat',
                    'a.P_Jumlah',
                    'a.P_Kadar'
                )
                ->leftJoin('pengajuankredit as p', 'p.Jaminan', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();
            if ($vaData) {
                $vaArray[1] = [
                    "Jenis" => "Perhiasan",
                    "Keterangan" => $vaData->P_Uraian,
                    "Berat" => $vaData->P_Berat,
                    "Jumlah" => $vaData->P_Jumlah,
                    "Kadar/Karat" => $vaData->P_Kadar,
                    "Atas Nama" => $vaData->AtasNama,
                    "Nominal" => Func::getZFormat($vaData->Nominal),
                ];
                $vaArray[2] = [
                    " " => "Dengan nilai penjaminan sebesar Rp. " . func::getZFormat($vaData->THLS) . " (" . Func::Terbilang($vaData->THLS) . ")",
                ];
            }
        } else if ($cJaminan == '8') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'r.Nama',
                    'a.Tgl',
                    'a.Kode',
                    'a.Rekening',
                    'a.Nama as AtasNama',
                    'a.NilaiJaminan as Nominal',
                    'a.D_Rekening',
                    'a.L_Note'
                )
                ->leftJoin('pengajuankredit as p', 'p.Jaminan', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();
            if ($vaData) {
                $L_Note = str_replace(array("\n", "\r\n", "\r"), '', $vaData->L_Note);
                $vaArray[1]  = ["Keterangan" => Func::ReplaceKarakterKhusus($L_Note)];
            }
        } else if ($cJaminan == '9') {
            $vaData = DB::table('agunan as a')
                ->select(
                    'a.ID',
                    'r.Nama',
                    'a.Tgl',
                    'a.Kode',
                    'a.Rekening',
                    'a.Nama as AtasNama',
                    'a.NilaiJaminan as Nominal',
                    'a.D_Rekening',
                    'a.L_Note',
                    'a.H_Chk1',
                    'a.H_Chk2',
                    'a.H_Chk3'
                )
                ->leftJoin('pengajuankredit as p', 'p.Jaminan', '=', 'a.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'a.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.No', '=', $cNo)
                ->where('a.Jaminan', '=', $cJaminan)
                ->whereRaw($cWhereJaminan)
                ->first();
            if ($vaData) {
                $L_Note = str_replace(array("\n", "\r\n", "\r"), '', $vaData->L_Note);
                $cKetJaminan = '';
                if ($vaData->H_Chk1 == '1') {
                    $cKetJaminan .= "1. SPPH | ";
                }
                if ($vaData->H_Chk2 == '1') {
                    $cKetJaminan .= "2. Buku Tabungan | ";
                }
                if ($vaData->H_Chk3 == '1') {
                    $cKetJaminan .= "3. Setoran BPIH | ";
                }
                $vaArray[1]  = [
                    "Kelengkapan" => $cKetJaminan,
                    "Keterangan" => Func::ReplaceKarakterKhusus($L_Note)
                ];
            }
        }
        return $vaArray;
    }

    public static function getAngsuranPokok($cRekening, $nKe, $dTglDiskonto = '')
    {
        $instance = new self();
        $dbJ = DB::table('jadwalangsuran')
            ->select('Pokok')
            ->where('Rekening', $cRekening)
            ->where('ke', $nKe)
            ->first();

        if ($dbJ) {
            $nRetval1 = $dbJ->Pokok;
            return $nRetval1;
        }

        $dbData = DB::table('debitur')
            ->select('Tgl', 'GracePeriod', 'Musiman', 'Lama', 'Plafond', 'GracePeriodPokokAwal', 'CaraPerhitungan', 'SukuBunga', 'instansi')
            ->where('Rekening', $cRekening)
            ->orderBy('Rekening')
            ->first();

        $nRetval = 0;

        if ($dbData) {
            $dTglRealisasi = $dbData->Tgl;
            $dTgl = $instance->getTglTransaksi();
            $vaRealisasi = $instance->GetAdendum($cRekening, $dTgl);
            $dbData->Tgl = $vaRealisasi['Tgl'];
            $dTglAdendum = $vaRealisasi['TglAdendum'];
            $dbData->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
            $cCaraPerhitungan = $dbData->CaraPerhitungan;
            $dbData->Lama = $vaRealisasi['Lama'];
            $dbData->SukuBunga = $vaRealisasi['SukuBunga'];
            $nSukuBunga = $dbData->SukuBunga;
            $dbData->Plafond = $vaRealisasi['Plafond'];
            $dTglRealisasi = $dbData->Tgl;
            $dJTHTMP = Carbon::createFromTimestamp(strtotime($dTglRealisasi))->addMonths($dbData->Lama);
            $dTglBunga = Carbon::createFromTimestamp(strtotime($dTglRealisasi))->addMonths($nKe);
            $vaSukuBunga = $instance->GetDebiturSukuBunga($cRekening, $dTglBunga);
            $dbData->SukuBunga = $vaSukuBunga['SukuBunga'];
            $dbData->Plafond = $vaSukuBunga['Plafond'];
            $dTglBungaDebitur = $vaSukuBunga['TglBungaDebitur'];
            $nGracePeriodPokokAwal = $dbData->GracePeriodPokokAwal;
            $nLama = $dbData->Lama - $nGracePeriodPokokAwal;
            $nMusiman = max(intval($nLama / max($dbData->Musiman, 1)), 1);
            $nTotalBunga = $dbData->Plafond * $dbData->SukuBunga / 12 / 100 * $nLama;
            $nBungaBulanan = $dbData->Plafond * $dbData->SukuBunga / 100 / 12;
            $nAngsuranPokok = ($dbData->Plafond / $nMusiman);
            $cInstansi = $dbData->instansi;
            $nPembulatan = config('app.msPembulatanAngsuranPokok', 50);

            $lJadwalPokok = $vaRealisasi['Adendum'];

            if (!$lJadwalPokok) {
                $db = DB::table('jadwalangsuran')
                    ->select('pokok')
                    ->where('rekening', $cRekening)
                    ->where('ke', $nKe)
                    ->first();

                if ($db) {
                    $nRetval = $db->pokok;
                } else {
                    $lJadwalPokok = true;
                }
            }

            if ($lJadwalPokok) {
                if ($cCaraPerhitungan !== 6) {
                    $nBungaBulanan = ceil($nBungaBulanan / $nPembulatan) * $nPembulatan;
                    $nAngsuranPokok = round($nAngsuranPokok);
                }

                $nGrace = max($dbData->GracePeriod, 1);
                $nLamaAngsuran = max($dbData->Lama - $nGrace, 1);

                if ($dbData->CaraPerhitungan == '1') {
                    $nSelisih = $nAngsuranPokok % 100;
                    if ($nSelisih < 50) {
                        $nAngsuranPokok -= $nSelisih;
                    } else {
                        $nRetval = ceil($nAngsuranPokok / $nPembulatan) * $nPembulatan;
                    }
                    $nRetval = $nAngsuranPokok;
                    $nRetval = ceil($nAngsuranPokok / $nPembulatan) * $nPembulatan;
                }

                if (($dbData->CaraPerhitungan == '2' || $dbData->CaraPerhitungan == '5')) {
                    $nRetval = ceil($dbData->Plafond - ($nAngsuranPokok * ($dbData->Lama - 1)) / $nPembulatan) * $nPembulatan;
                }

                if ($dbData->CaraPerhitungan == '3' || $dbData->CaraPerhitungan == '4') {
                    $nRetval = 0;
                }

                $nAngsuranBulanan = $nBungaBulanan + $nAngsuranPokok;

                if ($dbData->CaraPerhitungan == '10') {
                    $nBungaEfektif = $dbData->SukuBunga;
                    $nAngsuranBulanan = $instance->GetAnuitas($nBungaEfektif / 12 / 100, $dbData->Plafond, $dbData->Lama);
                    $nAngsuranBulanan = ceil($nAngsuranBulanan / 100) * 100;
                }

                if ($dbData->Musiman > 1) {
                    $nRetval = ceil($dbData->Plafond / $nMusiman / $nPembulatan) * $nPembulatan;
                    $nKe1 = $nKe - $nGracePeriodPokokAwal;
                    if ($nKe1 % max($dbData->Musiman, 1) !== 0) {
                        $nRetval = 0;
                    }
                }

                if ($dbData->CaraPerhitungan == '6' || $dbData->CaraPerhitungan == '7' || $dbData->CaraPerhitungan == '8' || $dbData->CaraPerhitungan == '10') {
                    if ($dbData->CaraPerhitungan == '8') {
                        $db = DB::table('debitur_diskonto')
                            ->select('Nominal')
                            ->where('rekening', $cRekening)
                            ->where('jthtmp', '<=', $dTglDiskonto)
                            ->orderBy('tgl', 'desc')
                            ->first();

                        if ($db) {
                            $nRetval = $db->Nominal;
                        }
                    } else {
                        $nRetval = ($nAngsuranBulanan - $instance->GetAngsuranBunga($cRekening, $nKe));
                        if ($nKe >= $dbData->Lama) {
                            $nLunas = 0;
                            for ($n = 1; $n < $dbData->Lama; $n++) {
                                $nLunas += ($instance->GetAngsuranPokok($cRekening, $n));
                            }
                            $nRetval = $dbData->Plafond - $nLunas;
                        }
                    }
                } else {
                    if ($nKe >= $dbData->Lama + $nGracePeriodPokokAwal and $dbData->Musiman > 1) {
                        $nRetval = $dbData->Plafond - ($nRetval * $nMusiman) + $nRetval;
                    } else if ($nGrace >= 1 and $nKe >= $dbData->Lama + $nGracePeriodPokokAwal) {
                        $nRetval = $dbData->Plafond - ($nRetval * $nLamaAngsuran);
                    } else if ($nKe >= $dbData->Lama + $nGracePeriodPokokAwal and $dbData->Musiman <= 1 and $nGrace <= 1) {
                        $nRetval = $dbData->Plafond - ($nRetval * $nMusiman) + $nRetval;
                    }
                }
            }
        }

        if ($nKe <= $nGracePeriodPokokAwal) {
            $nRetval = 0;
        }

        return $nRetval;
    }
    public static function getDebiturSukuBunga($rekening, $tgl)
    {
        $exists = DebiturSukuBunga::where('Rekening', $rekening)
            // ->where('tgl', $tgl)
            ->orderByDesc('tgl')
            ->exists();
        if ($exists) {
            $data = DebiturSukuBunga::where('Rekening', $rekening)
                // ->where('tgl', $tgl)
                ->orderByDesc('tgl')
                ->first();
            if ($data) {
                $nSukuBunga = $data->Sukubunga;
                $dTglBakiDebet = Carbon::parse($data->tgl)->subDay()->format('Y-m-d');
                $nPlafond = GetterSetter::getBakiDebet($rekening, $dTglBakiDebet);
                $vaArray = [
                    "SukuBunga" => $nSukuBunga,
                    "Plafond" => $nPlafond,
                    "TglBungaDebitur" => $data->tgl,
                ];
            }
        } else {
            $data2 = Debitur::where('Rekening', $rekening)->first();
            $tgl = $data2->Tgl;
            $sukubunga = $data2->SukuBunga;
            $plafond = $data2->Plafond;
            $lama = $data2->Lama;
            $dJTHTMP = Carbon::parse($tgl)->addMonths($lama)->format('Y-m-d');
            $dTglBungaDebitur = Carbon::parse($dJTHTMP)->addDay()->format('Y-m-d');
            $vaArray = array("SukuBunga" => $sukubunga, "Plafond" => $plafond, "TglBungaDebitur" => $dTglBungaDebitur);
        }

        return $vaArray;
    }

    public static function getAnuitas($nSukuBunga, $nPlafond, $nLama)
    {
        $nRetval = 0;
        if ($nSukuBunga != 0) {
            $nRetval = ($nSukuBunga * $nPlafond) / (1 - (1 / pow(1 + $nSukuBunga, $nLama)));
        }
        return $nRetval;
    }

    public static function getAngsuranBunga($cRekening, $nKe, $nBakiDebet = 0, $dTglDiskonto = '')
    {
        $instance = new self();
        $nRetval = 0;
        $nRetval1 = 0;
        $cError = '';

        $dbJ = DB::table('jadwalangsuran')
            ->select('Bunga')
            ->where('rekening', $cRekening)
            ->where('ke', $nKe)
            ->first();

        if ($dbJ) {
            $nRetval1 = $dbJ->Bunga;

            return $nRetval1;
        }

        $dbData = DB::table('debitur')
            ->select('Tgl', 'SukuBunga', 'GracePeriod', 'Musiman', 'GracePeriodPokokAwal', 'GracePeriodBungaAwal', 'Lama', 'Plafond', 'CaraPerhitungan', 'instansi')
            ->where('rekening', $cRekening)
            ->orderBy('rekening')
            ->first();

        if ($dbData) {
            $dTglRealisasi = $dbData->Tgl;
            $dTgl = Func::Date2String(GetterSetter::GetTglTransaksi()); // date("Y-m-d",NextMonth(Tgl2Time($dTglRealisasi),$nKe));//"0000-00-00" ;
            $vaRealisasi = $instance->GetAdendum($cRekening, $dTgl);
            $dbData->Tgl = $vaRealisasi['Tgl'];
            $dTglRealisasi = $dbData->Tgl;
            $dTglAdendum = $vaRealisasi['TglAdendum'];
            $dbData->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
            $dbData->Lama = $vaRealisasi['Lama'];
            $dbData->SukuBunga = $vaRealisasi['SukuBunga'];
            $nSukuBunga = $dbData->SukuBunga;
            $dbData->Plafond = $vaRealisasi['Plafond'];
            $dTglRealisasi = $dbData->Tgl;
            $dJTHTMP = Carbon::createFromTimestamp(strtotime($dTglRealisasi))
                ->addMonths($dbData->Lama)
                ->format('Y-m-d');
            $dTglBunga = Carbon::createFromTimestamp(strtotime($dTglRealisasi))
                ->addMonths($nKe)
                ->format('Y-m-d');
            $vaSukuBunga = $instance->GetDebiturSukuBunga($cRekening, $dTglBunga);
            $dbData->SukuBunga = $vaSukuBunga['SukuBunga'];
            $dbData->Plafond = $vaSukuBunga['Plafond'];
            $dTglBungaDebitur = $vaSukuBunga['TglBungaDebitur'];
            $nPembulatan = $instance->getDBConfig("msPembulatanAngsuran", 1);
            $lJadwalBunga = $vaRealisasi['Adendum'];

            if (!$lJadwalBunga) {
                $dbJ = DB::table('jadwalangsuran')
                    ->select('bunga')
                    ->where('rekening', $cRekening)
                    ->where('ke', $nKe)
                    ->first();

                if ($dbJ) {
                    $nRetval = $dbJ->bunga;
                } else {
                    $lJadwalBunga = true;
                }
            }

            if ($lJadwalBunga) {
                $nGracePeriodBungaAwal = $dbData->GracePeriodBungaAwal;
                $nLama = $dbData->Lama - $nGracePeriodBungaAwal;
                $nGracePeriod = intval($dbData->Lama / max($dbData->GracePeriod, 1));

                if ($dbData->CaraPerhitungan == 1) { // Bunga Flat
                    $nTotBunga = round($dbData->Plafond * $dbData->SukuBunga / 100 / 12 * $dbData->Lama, 0);

                    $nRetval1 = Func::PembulatanKeatas(Func::Devide($nTotBunga, $nLama), 0.34);

                    if ($nKe == $dbData->Lama) {
                        $nTotAgsBunga = $nRetval1 * ($nLama - 1);
                        $nRetval1 = $nTotBunga - $nTotAgsBunga;
                    }
                } elseif ($dbData->CaraPerhitungan == 2) { // Kredit Karyawan
                    $nBunga1 = $dbData->SukuBunga * $dbData->Plafond / 100;
                    $nPokok1 = $dbData->Plafond - (round(Func::Devide($dbData->Plafond, $dbData->Lama) * ($dbData->Lama - 1)));
                    $nBungaAkhir = $dbData->SukuBunga * $nPokok1 / 100;
                    $nRetval1 = round(($nBunga1 + $nBungaAkhir) / 2, 0);
                } elseif ($dbData->CaraPerhitungan == 3 || $dbData->CaraPerhitungan == 5 || $dbData->CaraPerhitungan == 4) { // Sliding
                    $nRetval1 = round($nBakiDebet * $dbData->SukuBunga / 12 / 100, 0);
                } elseif ($dbData->CaraPerhitungan == 6 || $dbData->CaraPerhitungan == 7) { // Anuitasi
                    $nBungaEfektif = $instance->BungaEfektif($dbData->SukuBunga, $dbData->Plafond, 0, $dbData->Lama);
                    $nSisaPokok = $dbData->Plafond;
                    $nTotBunga = ($dbData->Plafond * $dbData->SukuBunga / 100 / 12 * $dbData->Lama);
                    $nAgsPokok = ($dbData->Plafond / $dbData->Lama);
                    $nAgsBunga = ($nTotBunga / $dbData->Lama);
                    $nAngsuranBulanan = $nAgsPokok + $nAgsBunga;
                    $nTotPelunasanBunga = 0;

                    for ($n = 1; $n <= $nKe; $n++) {
                        $nRetval1 = round($nSisaPokok * $nBungaEfektif / 100 / 12, 0);
                        $nSisaPokok -= ($nAngsuranBulanan - $nRetval1);

                        if ($dbData->CaraPerhitungan == 7) {
                            $nFaktor = ($nLama * (1 + $nLama)) / 2;
                            $nRetval1 = ($nLama + 1 - $n) / $nFaktor * $nTotBunga;
                        }

                        if ($n < $dbData->Lama) {
                            $nTotPelunasanBunga += $nRetval1;
                        }
                    }

                    if ($nKe >= $dbData->Lama) {
                        $nRetval1 = $nTotBunga - $nTotPelunasanBunga;
                    }
                } elseif ($dbData->CaraPerhitungan == '8') {
                    $db = DB::table('debitur_diskonto')
                        ->select('Bunga')
                        ->where('rekening', $cRekening)
                        ->where('jthtmp', '<=', $dTglDiskonto)
                        ->orderBy('tgl', 'desc')
                        ->limit(1)
                        ->first();

                    if ($db) {
                        $nRetval1 = $db->Bunga;
                    }
                } elseif ($dbData->CaraPerhitungan == '10') {
                    $nBungaEfektif = $dbData->SukuBunga;
                    $nSisaPokok = $dbData->Plafond;
                    $nAngsuranBulanan = $instance->getAnuitas($nBungaEfektif / 12 / 100, $dbData->Plafond, $dbData->Lama);
                    $nAngsuranBulanan = Func::RoundUp($nAngsuranBulanan, 100);
                    $nTotal = $nAngsuranBulanan * $dbData->Lama;
                    $nTotBunga = $nTotal - $dbData->Plafond;
                    $nTotPelunasanBunga = 0;

                    for ($n = 1; $n <= $nKe; $n++) {
                        $nRetval1 = floor($nSisaPokok * $nBungaEfektif / 100 / 12);
                        $nSisaPokok -= ($nAngsuranBulanan - $nRetval1);

                        if ($n < $dbData->Lama) {
                            $nTotPelunasanBunga += $nRetval1;
                        }
                    }
                }

                if ($dbData->GracePeriod > 1) {
                    $nRetval = $instance->modAngsuran($nRetval1 * $dbData->GracePeriod);
                    $nKe1 = $nKe - $nGracePeriodBungaAwal;

                    if ($nKe1 % max($dbData->GracePeriod, 1) !== 0 || $nKe1 <= 0) {
                        $nRetval = 0;
                    }
                } else {
                    $nRetval = $nRetval1;
                    $nKe1 = $nKe - $nGracePeriodBungaAwal;

                    if ($nKe1 % max($dbData->GracePeriod, 1) !== 0 || $nKe1 <= 0) {
                        $nRetval = 0;
                    }
                }
            }
        }

        if ($nKe <= $nGracePeriodBungaAwal) {
            $nRetval = 0;
        }

        if ($dbData->CaraPerhitungan == '10' || $dbData->CaraPerhitungan == '6') {
            //$nRetval = round($nRetval) ;
        } else {
            $nRetval = Func::RoundUp($nRetval, $nPembulatan);
        }

        return $nRetval;
    }

    public static function BungaEfektif($nSukuBungaPA, $nPlafond, $nBunga = 0, $nLama = 0)
    {
        $instance = new self();
        $nAngsuranPA = 0;
        $nAngsuranEfektif = 0;
        $nEfektifBulanan = 0;

        $nSkip = 50;
        $nEfektif = 50;

        if ($nBunga == 0) {
            $nBunga = round($nPlafond * $nSukuBungaPA / 100 / 12 * $nLama, 0);
        }

        $nAngsuranPA = round((($nPlafond + $nBunga) / $nLama), 1);

        while (round($nAngsuranPA, 1) <> round($nAngsuranEfektif, 1)) {
            $nEfektifBulanan = $nEfektif / 12 / 100;
            $nAngsuranEfektif = round($instance->getAnuitas($nEfektifBulanan, $nPlafond, $nLama, 10), 1);

            $nSkip = $nSkip / 2;

            if ($nAngsuranEfektif < $nAngsuranPA) {
                $nEfektif = $nEfektif + $nSkip;
            } else if ($nAngsuranEfektif > $nAngsuranPA) {
                $nEfektif = $nEfektif - $nSkip;
            }
        }

        return $nEfektif;
    }

    public static function modAngsuran($nNumber)
    {
        $nRoundUp = config('msPembulatanAngsuran', 1); // Gantilah dengan cara mendapatkan nilai dari konfigurasi yang sesuai di Laravel Anda

        if ($nRoundUp == 0) return $nNumber;
        if ($nNumber == 0) return $nNumber;

        $nNumber = $nNumber;

        if ($nNumber > 0) {
            $nSelisih = $nNumber % $nRoundUp;
            if ($nSelisih <> 0) {
                $nNumber += ($nRoundUp - $nSelisih);
            }
        }

        return $nNumber;
    }

    public static function getKe($dTglRealisasi, $dTgl, $nLama)
    {
        ini_set('max_execution_time', '0');
        $nTglRealisasi = Func::Tgl2Time($dTglRealisasi);
        $nTgl = Func::Tgl2Time($dTgl);
        $nKe = 0;
        $x = 0;

        while ($x <= $nTgl) {
            $nKe++;
            $x = FuncDate::nextMonth($nTglRealisasi, $nKe);
        }

        $nKe--;
        return min(max($nKe, 0), $nLama);
    }

    public static function getKeowaowa($dTglRealisasi, $dTgl, $nLama)
    {
        ini_set('max_execution_time', '0');
        $nTglRealisasi = Carbon::parse($dTglRealisasi);
        $nTgl = Carbon::parse($dTgl);
        $nKe = 0;

        while ($nTglRealisasi->addMonth() <= $nTgl) {
            $nKe++;
        }
        return min(max($nKe, 0), $nLama);
    }


    public static function getAlamatRegisterNasabah($rekening)
    {
        $cJenis = substr($rekening, 3, 1);
        $cJenisRekening = "debitur";

        if ($cJenis == 1) {
            $cJenisRekening = "tabungan";
        } elseif ($cJenis == 2) {
            $cJenisRekening = "deposito";
        }

        $query = DB::table($cJenisRekening . ' as d')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->select('r.Alamat', 'r.RTRW', 'r.Kodya', 'r.Kecamatan', 'r.Kelurahan')
            ->where('d.rekening', $rekening);

        $cAlamat = "";

        $dbRow = $query->first();

        if ($dbRow) {
            $cKodya = Func::SeekDaerah($dbRow->Kodya);
            $cKecamatan = Func::SeekDaerah($dbRow->Kodya . "." . $dbRow->Kecamatan);
            $cKelurahan = Func::SeekDaerah($dbRow->Kodya . "." . $dbRow->Kecamatan . "." . $dbRow->Kelurahan);

            $cAlamat = $dbRow->Alamat . " Rt/Rw " . $dbRow->RTRW . " " . $cKelurahan . " " . $cKecamatan . " " . $cKodya;
            $cAlamat = $dbRow->Alamat . " " . $cKelurahan . " " . $cKecamatan . " " . $cKodya;
        }

        if ($cJenis == 0) {
            $dbData = DB::table('registernasabah')
                ->select('Alamat', 'RTRW', 'Kodya', 'Kecamatan', 'Kelurahan')
                ->where('kode', $rekening)
                ->first();

            if ($dbData && trim($dbData->Alamat) != "") {
                $cKodya = Func::SeekDaerah($dbData->Kodya);
                $cKecamatan = Func::SeekDaerah($dbData->Kodya . "." . $dbData->Kecamatan);
                $cKelurahan = Func::SeekDaerah($dbData->Kodya . "." . $dbData->Kecamatan . "." . $dbData->Kelurahan);

                $cAlamat = $dbData->Alamat . " Rt/Rw " . $dbData->RTRW . " " . $cKelurahan . " " . $cKecamatan . " " . $cKodya;
                $cAlamat = $dbData->Alamat . " " . $cKelurahan . " " . $cKecamatan . " " . $cKodya;
            }
        }

        return $cAlamat;
    }

    public static function getTglARO($cRekening, $dTgl)
    {
        $instance = new self();
        $dTgl = Carbon::parse($dTgl);

        $row = DB::table('deposito')
            ->select('tgl')
            ->where('rekening', $cRekening)
            ->first();

        if ($row) {
            $dTglValuta = $row->tgl;
        }

        $nGolonganDeposito = PerhitunganDeposito::getGolonganDeposito($cRekening);
        $nLama = PerhitunganDeposito::getLamaDeposito($nGolonganDeposito);
        $n = 0;
        $dTglARO = $dTglValuta;
        $dTglAROSebelumnya = $dTglARO;
        $cError = $cRekening . "|" . $dTglValuta . "|" . $dTglARO . "|" . $dTglAROSebelumnya . '\n';

        while ($dTglARO <= $dTgl) {
            $n += $nLama;
            $dTglARO = Carbon::parse($dTglValuta)->addMonthsNoOverflow($n)->startOfMonth()->toDateString();

            if ($dTglARO <= $dTgl) {
                $dTglAROSebelumnya = $dTglARO;
            }
            $cError .= $dTglARO . "|" . $dTglAROSebelumnya . '\n';
        }

        // Jika Anda ingin melakukan tindakan tertentu atau debugging, Anda dapat melakukannya di sini
        // if(!empty($cError)) echo('alert("'.$cError.'");');

        return $dTglAROSebelumnya;
    }

    public static function getTotalPembayaranKredit($rekening, $tgl)
    {
        $instance = new self();
        $va['PembayaranPokok'] = 0;
        $va['PembayaranBunga'] = 0;
        $va['Titipan'] = 0;
        $vaAdendum = $instance->getAdendum($rekening, $tgl);
        $tglRealisasi = $vaAdendum['Tgl'];
        $adendum = $vaAdendum['Adendum'];
        $data = DB::table('angsuran')
            ->select(
                DB::raw('SUM(kpokok - dpokok) as Pokok'),
                DB::raw('SUM(kbunga - dbunga) as Bunga'),
                DB::raw('SUM(krra) as RRA'),
                DB::raw('SUM(denda) as Denda'),
                DB::raw('SUM(DTitipan - KTitipan) as Titipan')
            )
            ->where('status', '<>', '2')
            ->whereBetween('tgl', ['2009-01-01', '2023-09-11'])
            ->where('rekening', '10130003093')
            ->first();
        if ($data) {
            $va['PembayaranPokok'] = $data->Pokok;
            $va['PembayaranBunga'] = $data->Bunga;
            $va['PembayaranDenda'] = $data->Denda;
            $va['Titipan'] = $data->Titipan;
        }
        if ($adendum) {
            $bulanAdendum = Carbon::parse($tglRealisasi)->format('Y-m');
            $data2 = Adendum::where('Rekening', $rekening)
                ->where('Tgl', '<=', $tgl)
                ->orderByDesc('Tgl')
                ->first();
            if ($data2) {
                $tglTransaksiAdendum = $data2->Tgl;
                if ($data->Acuan == 0) {
                    $tglTransaksiAdendum = $tglRealisasi;
                }
            }
            $va['PembayaranBunga'] = 0;
            $va['PembayaranPokok'] = 0;
            $data3 = DB::table('angsuran')
                ->select(
                    'tgl',
                    DB::raw('SUM(kpokok - dpokok) as Pokok'),
                    DB::raw('SUM(kbunga - dbunga) as Bunga'),
                    DB::raw('SUM(krra) as RRA'),
                    DB::raw('SUM(denda) as Denda'),
                    DB::raw('SUM(KTitipan) as Titipan')
                )
                ->where('status', '<>', '2')
                ->where('tgl', '>', '2009-01-01')
                ->where('tgl', '<=', '2023-09-11')
                ->where('rekening', '10130003093')
                ->groupBy('rekening', 'tgl')
                ->get();
            if ($data3) {
                $va['PembayaranPokok'] = $data3->Pokok;
                $va['PembayaranBunga'] = $data3->Bunga;
            }
        }
        return $va;
    }

    public static function getSukuBungaTabungan3($rekening, $tgl)
    {
        $instance = new self();
        $kode = $instance->getKode($rekening);
        $sukuBunga = '';
        $data = DetailSukuBunga::where('Kode', $kode)
            ->where('Tgl', '<=', $tgl)
            ->orderByDesc('Tgl')
            ->first();
        if ($data) {
            $sukuBunga = $data->SukuBunga ?? 0;
        }
        return $sukuBunga;
    }

    public static function getMutasiTabungan($rekening, $tgl)
    {
        $instance = new self();
        $setoran = 0;
        $penarikan = 0;
        $bunga = 0;
        $pajak = 0;
        $adm = 0;
        $tglAwal = Carbon::parse($tgl)->startOfMonth()->format('Y-m-d');
        $tglAkhir = Carbon::parse($tgl)->endOfMonth()->format('Y-m-d');
        $kodeBunga = $instance->getDBConfig('msKodeBungaTabungan');
        $kodeAdministrasi = $instance->getDBConfig('msKodeAdmBulanan');
        $kodePajak = $instance->getDBConfig('msKodePajakBungaTabungan');
        $data = MutasiTabungan::select(DB::raw('IFNULL(SUM(Kredit), 0) as Setoran'), DB::raw('IFNULL(SUM(Debet), 0) as Penarikan'))
            ->where('Rekening', $rekening)
            ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->whereNotIn('KodeTransaksi', [$kodeBunga, $kodePajak, $kodeAdministrasi])
            ->first();
        if ($data) {
            $setoran = $data->Setoran;
            $penarikan = $data->Penarikan;
        }
        $data2 = MutasiTabungan::select(DB::raw('IFNULL(SUM(Kredit), 0) as Bunga'))
            ->where('Rekening', $rekening)
            ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->where('KodeTransaksi', $kodeBunga)
            ->where('Faktur', 'Like', 'BT%')
            ->first();
        if ($data2) {
            $bunga = $data2->Bunga;
        }
        $data3 = MutasiTabungan::select(DB::raw('IFNULL(SUM(Debet), 0) as Pajak'))
            ->where('Rekening', $rekening)
            ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->where('KodeTransaksi', $kodePajak)
            ->where('Faktur', 'like', 'BT%')
            ->first();
        if ($data3) {
            $pajak = $data3->Pajak;
        }
        $data4 = MutasiTabungan::select(DB::raw('IFNULL(SUM(Debet), 0) as Adm'))
            ->where('Rekening', $rekening)
            ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->where('KodeTransaksi', $kodeAdministrasi)
            ->where('Faktur', 'like', 'BT%')
            ->first();
        if ($data4) {
            $adm = $data4->Adm;
        }

        $va['Setoran'] = $setoran;
        $va['Penarikan'] = $penarikan;
        $va['Bunga'] = $bunga;
        $va['Pajak'] = $pajak;
        $va['Admin'] = $adm;

        return $va;
    }

    public static function PembulatanDeposito($nNominal)
    {
        $instance = new self();
        $nNominal1 = intval($nNominal);
        $msPembulatanDeposito = $instance->getDBConfig("msPembulatanDeposito", "1");

        if ($msPembulatanDeposito == "1") {
            if (($nNominal - $nNominal1) > 0.49) {
                $nRetval = $nNominal1 + 1;
            } else {
                $nRetval = $nNominal1;
            }
        } else {
            $nRetval = $nNominal1;
        }

        return $nRetval;
    }
    public static function getJaminanNasabah($cRekening)
    {
        $instance = new self();
        $result = DB::table('agunan as a')
            ->select('a.Rekening', DB::raw('count(*) as Qty'), 'a.Jaminan', 'j.keterangan as NamaJaminan', DB::raw('sum(a.NilaiJaminan) as NilaiJaminan'), 'a.S_JenisPengikatan')
            ->leftJoin('debitur as d', 'd.RekeningJaminan', '=', 'a.rekening')
            ->leftJoin('jaminan as j', 'j.kode', '=', 'a.jaminan')
            ->where('d.rekening', '=', $cRekening)
            ->groupBy(
                'a.Rekening',
                'a.Jaminan',
                'j.keterangan',
                'a.NilaiJaminan',
                'a.S_JenisPengikatan'
            )
            ->orderBy('a.NilaiJaminan', 'desc')
            ->get();

        $cNamaJaminan = '';
        $va = [
            'Jaminan' => null,
            'JenisPengikatan' => null,
            'NilaiJaminan' => 0,
            'RekeningJaminan' => null,
            'NamaJaminan' => null,
        ];

        foreach ($result as $dbRow) {
            $va['Jaminan'] = $dbRow->Jaminan;
            $cSplit = '';
            if (!empty($cNamaJaminan)) {
                $cSplit = ', ';
            }
            $cNamaJaminan .= $cSplit . $dbRow->NamaJaminan;
            $va['JenisPengikatan'] = $instance->GetKeterangan($dbRow->S_JenisPengikatan, "Keterangan", "jenispengikatanjaminan");
            $va['NilaiJaminan'] += $dbRow->NilaiJaminan;
            $va['RekeningJaminan'] = $dbRow->Rekening;
        }
        $va['NamaJaminan'] = $cNamaJaminan;
        return $va;
    }

    public static function getKewajibanBunga($rekening, $tgl, $tanpaAngsuran = false)
    {
        $instance = new self();
        $retval = 0;
        $data = DB::table('debitur as d')
            ->select(
                'd.Lama',
                'd.Tgl',
                'd.Plafond',
                'd.SukuBunga',
                'd.CaraPerhitungan',
                DB::raw('IFNULL(SUM(a.KBunga), 0) as PembayaranBunga'),
                DB::raw('SUM(KRRA) as RRA')
            )
            ->leftJoin('angsuran as a', function ($join) use ($tgl) {
                $join->on('a.Rekening', '=', 'd.Rekening')
                    ->where('a.Tgl', '<', $tgl);
            })
            ->where('d.Rekening', $rekening)
            ->groupBy('d.Rekening')
            ->first();
        if ($data) {
            $vaRealisasi = GetterSetter::getAdendum($rekening, $tgl);
            $data->Tgl = $vaRealisasi['Tgl'];
            $tglAdendum = $vaRealisasi['TglAdendum'];
            $data->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
            $data->Lama = $vaRealisasi['Lama'];
            $data->SukuBunga = $vaRealisasi['SukuBunga'];
            $data->Plafond = $vaRealisasi['Plafond'];
            $ke = $instance->GetKe($data->Tgl, $tgl, $data->Lama);
            $vaPembayaran = $instance->getTotalPembayaranKredit($rekening, $tgl);
            $pembayaranBunga = $vaPembayaran['PembayaranBunga'];
            $totalBunga = round($data->Plafond * $data->SukuBunga / 100 / 12 * $data->Lama, 0);
            $angsuranBungaFlat = round(Func::Devide($totalBunga, $data->Lama), 0);
            for ($n = 1; $n <= $ke; $n++) {
                // Jika Reguler/Sliding
                if ($data->CaraPerhitungan == '3' || $data->CaraPerhitungan == '4' || $data->CaraPerhitungan == '5') {
                    $tglRealisasi = $data->Tgl;
                    $date = Carbon::parse($tglRealisasi)->copy()->addMonths(1)->subDay();
                    $bakiDebet = GetterSetter::getBakiDebet($rekening, $date);
                    $bunga = Func::modAngsuran($bakiDebet * $data->SukuBunga / 100 / 12);
                } else {
                    $bunga = $instance->getAngsuranBunga($rekening, $n);
                }
                if ($data->CaraPerhitungan == '6') {
                    $bunga = $instance->getAngsuranBunga($rekening, $n);
                }
                if ($data->CaraPerhitungan == '10') {
                    $tglRealisasi = $data->Tgl;
                    $date = Carbon::parse($tglRealisasi)->copy()->addMonths(1)->subDay();
                    $bakiDebet = GetterSetter::getBakiDebet($rekening, $date);
                    $bunga = $instance->getAngsuranBunga($rekening, $n, $bakiDebet);
                }
                $vaJadwal[$n] = $bunga;
                $retval += $bunga;
            }
            if (!$tanpaAngsuran) {
                $retval -= $pembayaranBunga;
            }
        }
        return $retval;
    }

    public static function GetKeteranganKol($dTgl = '')
    {
        $dTgl = Func::Date2String($dTgl);
        $va = [
            '',
            '1. LANCAR',
            '2. DPK',
            '3. KURANG LANCAR',
            '4. DIRAGUKAN',
            '5. MACET',
        ];
        return $va;
    }

    public static function getTunggakan($rekening, $tgl)
    {
        $periode = date('Ym', strtotime($tgl));
        $data = DB::table('debitur_masternominatifkredit as d')
            ->select(
                'd.ID',
                'd.Periode',
                'd.Cabang',
                'd.Rekening',
                'd.Baki_Debet_Awal',
                'd.Baki_Debet_Akhir',
                'd.T_Pokok_Awal',
                'd.T_Pokok_Akhir',
                'd.T_Bunga_Awal',
                'd.T_Bunga_Akhir',
                'd.Tunggakan_Awal',
                'd.Tunggakan_Akhir',
                'd.Denda_Awal',
                'd.Denda_Akhir',
                'd.FR_P_Awal',
                'd.FR_P_Akhir',
                'd.FR_B_Awal',
                'd.FR_B_Akhir',
                'd.FR_Awal',
                'd.FR_Akhir',
                'd.Hari_T_Awal',
                'd.Hari_T_Akhir',
                'd.Hari_P_Awal',
                'd.Hari_P_Akhir',
                'd.Hari_B_Awal',
                'd.Hari_B_Akhir',
                'd.Kol_Awal',
                'd.Kol_Akhir',
                'a.Jaminan',
                'a.S_JenisPengikatan AS JenisPengikatan',
                'a.NilaiYangDiPerhitungkan',
            )
            ->leftJoin('debitur as de', 'de.Rekening', '=', 'd.Rekening')
            ->leftJoin('agunan as a', 'a.Rekening', '=', 'de.RekeningJaminan')
            ->where('de.Rekening', $rekening)
            ->first();

        $defaultData = [
            'ID' => 0,
            'Periode' => 0,
            'Cabang' => 0,
            'Rekening' => 0,
            'Baki_Debet_Awal' => 0,
            'Baki_Debet_Akhir' => 0,
            'T_Pokok_Awal' => 0,
            'T_Pokok_Akhir' => 0,
            'T_Bunga_Awal' => 0,
            'T_Bunga_Akhir' => 0,
            'Tunggakan_Awal' => 0,
            'Tunggakan_Akhir' => 0,
            'Denda_Awal' => 0,
            'Denda_Akhir' => 0,
            'FR_P_Awal' => 0,
            'FR_P_Akhir' => 0,
            'FR_B_Awal' => 0,
            'FR_B_Akhir' => 0,
            'FR_Awal' => 0,
            'FR_Akhir' => 0,
            'Hari_T_Awal' => 0,
            'Hari_T_Akhir' => 0,
            'Hari_P_Awal' => 0,
            'Hari_P_Akhir' => 0,
            'Hari_B_Awal' => 0,
            'Hari_B_Akhir' => 0,
            'Kol_Awal' => 0,
            'Kol_Akhir' => 0,
            'Jaminan' => '',
            'JenisPengikatan' => '',
            'NilaiYangDiPerhitungkan' => 0
        ];
        if ($data) {
            $defaultData = [
                'ID' => $data->ID,
                'Periode' => $data->Periode,
                'Cabang' => $data->Cabang,
                'Rekening' => $data->Rekening,
                'Baki_Debet_Awal' => $data->Baki_Debet_Awal,
                'Baki_Debet_Akhir' => $data->Baki_Debet_Akhir,
                'T_Pokok_Awal' => $data->T_Pokok_Awal,
                'T_Pokok_Akhir' => $data->T_Pokok_Akhir,
                'T_Bunga_Awal' => $data->T_Bunga_Awal,
                'T_Bunga_Akhir' => $data->T_Bunga_Akhir,
                'Tunggakan_Awal' => $data->Tunggakan_Awal,
                'Tunggakan_Akhir' => $data->Tunggakan_Akhir,
                'Denda_Awal' => $data->Denda_Awal,
                'Denda_Akhir' => $data->Denda_Akhir,
                'FR_P_Awal' => $data->FR_P_Awal,
                'FR_P_Akhir' => $data->FR_P_Akhir,
                'FR_B_Awal' => $data->FR_B_Awal,
                'FR_B_Akhir' => $data->FR_B_Akhir,
                'FR_Awal' => $data->FR_Awal,
                'FR_Akhir' => $data->FR_Akhir,
                'Hari_T_Awal' => $data->Hari_T_Awal,
                'Hari_T_Akhir' => $data->Hari_T_Akhir,
                'Hari_P_Awal' => $data->Hari_P_Awal,
                'Hari_P_Akhir' => $data->Hari_P_Akhir,
                'Hari_B_Awal' => $data->Hari_B_Awal,
                'Hari_B_Akhir' => $data->Hari_B_Akhir,
                'Kol_Awal' => $data->Kol_Awal,
                'Kol_Akhir' => $data->Kol_Akhir,
                'Jaminan' => $data->Jaminan,
                'JenisPengikatan' => $data->JenisPengikatan,
                'NilaiYangDiPerhitungkan' => $data->NilaiYangDiPerhitungkan
            ];
        } else {
            $defaultData = [
                'ID' => 0,
                'Periode' => 0,
                'Cabang' => 0,
                'Rekening' => 0,
                'Baki_Debet_Awal' => 0,
                'Baki_Debet_Akhir' => 0,
                'T_Pokok_Awal' => 0,
                'T_Pokok_Akhir' => 0,
                'T_Bunga_Awal' => 0,
                'T_Bunga_Akhir' => 0,
                'Tunggakan_Awal' => 0,
                'Tunggakan_Akhir' => 0,
                'Denda_Awal' => 0,
                'Denda_Akhir' => 0,
                'FR_P_Awal' => 0,
                'FR_P_Akhir' => 0,
                'FR_B_Awal' => 0,
                'FR_B_Akhir' => 0,
                'FR_Awal' => 0,
                'FR_Akhir' => 0,
                'Hari_T_Awal' => 0,
                'Hari_T_Akhir' => 0,
                'Hari_P_Awal' => 0,
                'Hari_P_Akhir' => 0,
                'Hari_B_Awal' => 0,
                'Hari_B_Akhir' => 0,
                'Kol_Awal' => 0,
                'Kol_Akhir' => 0,
                'Jaminan' => '',
                'JenisPengikatan' => '',
                'NilaiYangDiPerhitungkan' => 0
            ];
        }
        return $defaultData;
    }

    public static function getTunggakanKolHarian($rekening, $tgl)
    {
        $periode = date('Ym', strtotime($tgl));
        $data = DB::table('debitur_kol_harian as d')
            ->select(
                'd.Periode',
                'd.Rekening',
                'd.TglRealisasi',
                'd.Kol',
                'd.Plafond',
                'd.BakiDebet',
                'd.TPokok',
                'd.TBunga',
                'd.FR',
                'd.FRPokok',
                'd.FRBunga',
                'd.FRTunggakan',
                'd.HariTelat',
                'd.HariTelatPokok',
                'd.HariTelatBunga',
                'd.Denda',
                'd.PPAP',
                'd.ProsentaseProyeksi',
                'd.TotalJaminan',
                'd.CaraAngsuran',
                'd.JTHTMP',
                'd.UserName',
                'd.DateTime',
                'a.Jaminan',
                'a.S_JenisPengikatan AS JenisPengikatan',
                'a.NilaiYangDiPerhitungkan',
            )
            ->leftJoin('debitur as de', 'de.Rekening', '=', 'd.Rekening')
            ->leftJoin('agunan as a', 'a.Rekening', '=', 'de.RekeningJaminan')
            ->where('de.Rekening', $rekening)
            ->first();

        $defaultData = [
            'Periode' => 0,
            'Rekening' => 0,
            'TglRealisasi' => 0,
            'Kol' => 0,
            'Plafond' => 0,
            'BakiDebet' => 0,
            'TPokok' => 0,
            'TBunga' => 0,
            'FR' => 0,
            'FRPokok' => 0,
            'FRBunga' => 0,
            'FRTunggakan' => 0,
            'HariTelat' => 0,
            'HariTelatPokok' => 0,
            'HariTelatBunga' => 0,
            'Denda' => 0,
            'PPAP' => 0,
            'ProsentaseProyeksi' => 0,
            'TotalJaminan' => 0,
            'CaraAngsuran' => 0,
            'JTHTMP' => 0,
            'UserName' => 0,
            'DateTime' => 0,
            'Kol_Akhir' => 0,
            'Jaminan' => '',
            'JenisPengikatan' => '',
            'NilaiYangDiPerhitungkan' => 0
        ];
        if ($data) {
            $defaultData = [
                'Periode' => $data->Periode,
                'Rekening' => $data->Rekening,
                'TglRealisasi' => $data->TglRealisasi,
                'Kol' => $data->Kol,
                'Plafond' => $data->Plafond,
                'BakiDebet' => $data->BakiDebet,
                'TPokok' => $data->TPokok,
                'TBunga' => $data->TBunga,
                'FR' => $data->FR,
                'FRPokok' => $data->FRPokok,
                'FRBunga' => $data->FRBunga,
                'FRTunggakan' => $data->FRTunggakan,
                'HariTelat' => $data->HariTelat,
                'HariTelatPokok' => $data->HariTelatPokok,
                'HariTelatBunga' => $data->HariTelatBunga,
                'Denda' => $data->Denda,
                'PPAP' => $data->PPAP,
                'ProsentaseProyeksi' => $data->ProsentaseProyeksi,
                'TotalJaminan' => $data->TotalJaminan,
                'CaraAngsuran' => $data->CaraAngsuran,
                'JTHTMP' => $data->JTHTMP,
                'UserName' => $data->UserName,
                'DateTime' => $data->DateTime,
                'Kol_Akhir' => $data->Kol_Akhir,
                'Jaminan' => $data->Jaminan,
                'JenisPengikatan' => $data->JenisPengikatan,
                'NilaiYangDiPerhitungkan' => $data->NilaiYangDiPerhitungkan
            ];
        } else {
            $defaultData = [
                'Periode' => 0,
                'Rekening' => 0,
                'TglRealisasi' => 0,
                'Kol' => 0,
                'Plafond' => 0,
                'BakiDebet' => 0,
                'TPokok' => 0,
                'TBunga' => 0,
                'FR' => 0,
                'FRPokok' => 0,
                'FRBunga' => 0,
                'FRTunggakan' => 0,
                'HariTelat' => 0,
                'HariTelatPokok' => 0,
                'HariTelatBunga' => 0,
                'Denda' => 0,
                'PPAP' => 0,
                'ProsentaseProyeksi' => 0,
                'TotalJaminan' => 0,
                'CaraAngsuran' => 0,
                'JTHTMP' => 0,
                'UserName' => 0,
                'DateTime' => 0,
                'Kol_Akhir' => 0,
                'Jaminan' => '',
                'JenisPengikatan' => '',
                'NilaiYangDiPerhitungkan' => 0
            ];
        }
        return $defaultData;
    }

    public static function getJaminanKlasifikasi($cRekening)
    {
        $cError = '';
        $va = [
            'RekeningJaminan' => 0,
            'AlamatJaminan' => 0,
            'JenisJaminan' => 0,
            'JenisPengikatan' => 0,
            'NilaiJaminan' => 0,
            'NilaiYangDiPerhitungkan' => 0,
            'NilaiJaminanKlasifikasi' => 0,
            'Agunan' => [],
        ];

        $dbData = DB::select("
            SELECT 
                j.*, 
                j.NO, 
                j.Jaminan, 
                j.NilaiYangDiPerhitungkan, 
                j.NilaiJaminan,
                (j.NilaiYangDiPerhitungkan * n.PROSENTASE / 100) AS NilaiJaminanKlasifikasi,
                n.Prosentase, 
                j.S_JenisPengikatan, 
                j.Rekening as RekeningJaminan, 
                j.Alamat
            FROM debitur d
            LEFT JOIN agunan j ON j.Rekening = d.rekeningjaminan
            LEFT JOIN jenispengikatanjaminan n ON n.Kode = j.S_JenisPengikatan
            LEFT JOIN jaminan jm ON jm.Kode = j.Jaminan
            WHERE d.rekening = ? AND j.status = 1
            ORDER BY NilaiJaminanKlasifikasi, NilaiYangDiPerhitungkan
        ", [$cRekening]);

        $nJaminanKlasifikasi = 0;
        $nTotJaminan = 0;

        foreach ($dbData as $dbRow) {
            $cJenisPengikatan = $dbRow->S_JenisPengikatan;
            $nJaminanKlasifikasi = round(intval($dbRow->NilaiYangDiPerhitungkan) * intval($dbRow->Prosentase) / 100, 0);

            if ($dbRow->NilaiJaminan == 0) {
                $dbRow->NilaiJaminan = $dbRow->NilaiYangDiPerhitungkan;
            }

            $cAlamat = trim("{$dbRow->Alamat} {$dbRow->M_Alamat} {$dbRow->S_Alamat}");

            $va['Agunan'][$dbRow->NO] = [
                'RekeningJaminan' => $dbRow->RekeningJaminan,
                'AlamatJaminan' => $cAlamat,
                'JenisJaminan' => $dbRow->Jaminan,
                'JenisPengikatan' => $dbRow->S_JenisPengikatan,
                'StatusKeluarMasuk' => $dbRow->KeluarMasuk,
                'NilaiJaminan' => $dbRow->NilaiJaminan,
                'NilaiYangDiPerhitungkan' => $dbRow->NilaiYangDiPerhitungkan,
                'NilaiJaminanKlasifikasi' => $nJaminanKlasifikasi,
            ];

            $va['RekeningJaminan'] = $dbRow->RekeningJaminan;
            $va['AlamatJaminan'] = $cAlamat;
            $va['JenisJaminan'] = $dbRow->Jaminan;
            $va['JenisPengikatan'] = $dbRow->S_JenisPengikatan;

            if ($cRekening == 10130008498) {
                //$cError .= $va['AlamatJaminan'];
            }

            $lTotal = true;

            if (!$lTotal) {
                $va['NilaiJaminan'] = 0;
                $va['NilaiYangDiPerhitungkan'] = 0;
                $va['NilaiJaminanKlasifikasi'] = 0;
            }

            $va['NilaiJaminan'] += $dbRow->NilaiJaminan;
            $va['NilaiYangDiPerhitungkan'] += $dbRow->NilaiYangDiPerhitungkan;
            $va['NilaiJaminanKlasifikasi'] += $nJaminanKlasifikasi;
        }

        if (!empty($cError)) {
            echo ('alert("' . $cError . '");');
        }

        return $va;
    }

    public static function GetMusiman($cRekening)
    {
        $result = DB::table('debitur')
            ->select('Musiman', 'GracePeriod')
            ->where('Rekening', $cRekening)
            ->first();

        if ($result) {
            return [
                'Musiman' => $result->Musiman,
                'GracePeriod' => $result->GracePeriod,
            ];
        } else {
            return null; // or handle the case when no record is found
        }
    }

    public static function GetTunggakanHitung($cRekening, $dTgl, $dTglRealisasi, $cCaraPerhitungan, $nLama, $nPlafond, $nPembayaranPokok, $nSukuBunga, $nPembayaranBunga)
    {
        $vaMusiman = self::GetMusiman($cRekening);
        $dTgl = Func::Date2String($dTgl);
        $dTglRealisasi = Func::String2Date($dTglRealisasi);
        $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTgl);
        $va = [
            "T.Pokok" => 0,
            "T.Bunga" => 0,
            "FR" => 0,
            "Kol" => 1,
            "Denda" => 0,
            "NilaiJaminan" => 0,
            "NilaiYangDiPerhitungkan" => 0,
            "JenisJaminan" => "",
            "PPAP" => 0,
            "FRPokok" => 0,
            "FRBunga" => 0,
            "BakiDebet" => $nBakiDebet,
            "NilaiJaminanNJOP" => 0,
            "PPAPNJOP" => 0,
            "JenisPengikatan" => "",
            "HariTerlambat" => 0,
            "HariTerlambatPokok" => 0,
            "HariTerlambatBunga" => 0,
            "NilaiJaminanAwal" => 0
        ];

        // Ambil Nilai Jaminan
        $vaJaminan = self::getJaminanKlasifikasi($cRekening);
        $va = array_merge($va, $vaJaminan);
        $nTotJaminan = $va['NilaiJaminanKlasifikasi'];
        $va['NilaiJaminanNJOP'] = $nTotJaminan;

        if ($nBakiDebet <= 0) {
            return $va;
        }

        $nTgl = Func::Tgl2Time($dTgl);
        $nTglRealisasi = Func::Tgl2Time($dTglRealisasi);
        $nTotalBunga = round($nPlafond * $nSukuBunga / 100 / 12 * $nLama, 0); // Bunga Untuk Kredit Flat

        // WriteOFF
        $rw = DB::table('debitur')->select('TglWriteOff')->where('rekening', $cRekening)->first();
        $dTglWriteOff = $rw ? $rw->TglWriteOff : null;

        // Di Cek Ulang Untuk Menghitung Accual
        $nKe = self::GetKe($dTglRealisasi, $dTgl, $nLama);
        $nKewajibanPokok = 0;
        $nKewajibanBunga = 0;
        for ($n = 1; $n <= $nKe; $n++) {
            $nAngsuranPokok = self::GetAngsuranPokok($cRekening, $n);
            $nAngsuranBunga = self::GetAngsuranBunga($cRekening, $n);
            $nKewajibanPokok += $nAngsuranPokok;
            $dTglAgs = date("d-m-Y", FuncDate::NextMonth($nTglRealisasi, $n));
            $nBakiDebet = self::getBakiDebet($cRekening, $dTglAgs);
            $nPokok = $nAngsuranPokok;
            $nBunga = $nAngsuranBunga;
            if ($cCaraPerhitungan !== 6) {
                $nPokok = self::GetAngsuranPokok($cRekening, $n);
                $nBunga = self::GetAngsuranBunga($cRekening, $n, $nBakiDebet);
            }
            if ($cCaraPerhitungan == "1" || $cCaraPerhitungan == '2' || $cCaraPerhitungan == '6' || $cCaraPerhitungan == '7') {
                // Flat
                if ($n >= $nLama) {
                    $nKewajibanBunga += $nAngsuranBunga;
                } else {
                    $nKewajibanBunga += $nAngsuranBunga;
                }
            } else {
                $dJTHTMPAgs = date("d-m-Y", FuncDate::NextMonth($nTglRealisasi, $n));
                $nKewajibanBunga = self::getKewajibanBunga($cRekening, $dJTHTMPAgs, true);
            }
            if ($nKewajibanPokok > $nPembayaranPokok + 1000 || $nKewajibanBunga > $nPembayaranBunga + 1000) {
                $va['FR']++;
            }

            if ($nKewajibanPokok > $nPembayaranPokok + 1000) {
                if ($nPokok == 0) {
                    $va['FRPokok'] = 0;
                }
                $va['FRPokok']++;
            }
            if ($nKewajibanBunga > $nPembayaranBunga + 1000) {
                if ($nBunga == 0) {
                    $va['FRBunga'] = 0;
                }
                $va['FRBunga']++;
            }
        }

        // Jika Jatuh Tempo
        $nJTHTMP = FuncDate::NextMonth($nTglRealisasi, $nLama);
        $nFrekuensiJTHTMP = 0;
        if ($nTgl >= $nJTHTMP) {
            while ($nJTHTMP <= $nTgl) {
                $nFrekuensiJTHTMP++;
                $nJTHTMP = FuncDate::NextMonth($nTglRealisasi, $nLama + $nFrekuensiJTHTMP);
            }
            $nKewajibanPokok = $nPlafond;
        }

        if ($cRekening == '10230002877') {
            //echo('alert("'.$nKewajibanBunga . "}" .$nPembayaranBunga .'");');
        }
        //$cError .= $nKewajibanPokok . "|" . $nPembayaranPokok ;
        $va['T.Pokok'] = max($nKewajibanPokok - $nPembayaranPokok, 0);
        $va['T.Bunga'] = max($nKewajibanBunga - $nPembayaranBunga, 0);

        $va['FR'] = max($va['FRPokok'], $va['FRBunga']);
        if ($va['T.Pokok'] == 0 && $va['T.Bunga'] == 0) {
            $va['FR'] = 0;
            $nFrekuensiJTHTMP = 0;
        }
        $nPembulatanFrekuensi = config("msPembulatanFrekuensi", 2);
        $va['FR'] = round($va['FR'], $nPembulatanFrekuensi);
        $nBakiDebet = self::getBakiDebet($cRekening, $dTgl);

        //Untuk Koreksi Kol Dari Pemeriksaan
        $nKolCIF = '';
        $lKolAsli = 1;
        /*
            if($lKolAsli == "1"){
            $cTgl = Date2String($dTgl) ;
            $dbData = DB::table('debitur_kol')
                ->where('Rekening', $cRekening)
                ->where('tgl', '<=', $cTgl)
                ->where('tglakhir', '>=', $cTgl)
                ->orderBy('tgl', 'desc')
                ->limit(1)
                ->first();

            if($dbData){
                $nKolCIF = $dbData->Kol;
                $dTglKolCIF = $dbData->Tgl;
            }
            }
            */

        $va['FR Kol'] = $va['FR'] + 0;
        if ($dTgl <= '2015-08-31') {
            $va['FR Kol']++;
        }
        $va['NilaiJaminanNJOP'] = $nTotJaminan;
        $va['FR'] += $nFrekuensiJTHTMP;
        $nPembulatanFrekuensi = config("msPembulatanFrekuensi", 2);
        $va['FR'] = round($va['FR'], $nPembulatanFrekuensi);
        $nBakiDebet = self::getBakiDebet($cRekening, $dTgl);
        $va['FrekuensiJTHTMP'] = $nFrekuensiJTHTMP;

        $va['FR'] = ceil($va['FR']);
        $va['FRPokok'] = ceil(round($va['FRPokok'], 2));
        $va['FRBunga'] = ceil(round($va['FRBunga'], 2));
        //HITUNG KOL OJK
        $nTunggakan = $va['T.Pokok'] + $va['T.Bunga'];
        $nHariTunggakan = 0;
        $nHariTunggakanPokok = 0;
        $nHariTunggakanBunga = 0;
        if ($nTunggakan == 0) {
            $va['Kol OJK'] = 1;
        } else {
            $nKe = self::GetKe($dTglRealisasi, $dTgl, $nLama);
            $nKeLastAngsuran = $nKe - $va['FR'];
            if ($va['FR'] > 0) $nKeLastAngsuran += 1;

            $nKePokok = $nKe;
            $nMusiman = $vaMusiman['Musiman'];
            if ($vaMusiman['Musiman'] > 1) {
                $nKePokok = floor($nKe / $vaMusiman['Musiman']);
            }

            $nKeLastAngsuranPokok = $nKePokok - round($va['FRPokok']);
            if ($va['FRPokok'] > 0) $nKeLastAngsuranPokok += 1;
            $nKeLastAngsuranPokok = $nKeLastAngsuranPokok * $nMusiman;

            $nKeLastAngsuranBunga = $nKe - round($va['FRBunga']);
            if ($va['FRBunga'] > 0) $nKeLastAngsuranBunga += 1;

            $dTglLastJadwal = date('Y-m-d', FuncDate::NextMonth(func::Tgl2Time($dTglRealisasi), $nKeLastAngsuran));
            $dTglLastJadwalPokok = date('Y-m-d', FuncDate::NextMonth(func::Tgl2Time($dTglRealisasi), $nKeLastAngsuranPokok));
            $dTglLastJadwalBunga = date('Y-m-d', FuncDate::NextMonth(func::Tgl2Time($dTglRealisasi), $nKeLastAngsuranBunga));
            $dTglLastJadwal = min($dTglLastJadwalPokok, $dTglLastJadwalBunga);

            $nHariTunggakanPokok = 0;
            $nHariTunggakanBunga = 0;

            if ($va['T.Pokok'] > 0) $nHariTunggakanPokok = self::GetJumlahHariTunggakan($dTglLastJadwalPokok, $dTgl);
            if ($va['T.Bunga'] > 0) $nHariTunggakanBunga = self::GetJumlahHariTunggakan($dTglLastJadwalBunga, $dTgl);
            $nHariTunggakan = max($nHariTunggakanPokok, $nHariTunggakanBunga);

            $dJTHTMP = date('Y-m-d', FuncDate::NextMonth(func::Tgl2Time($dTglRealisasi), $nLama));
            $nHariJTHTMP = 0;
            if ($dJTHTMP <= $dTgl) {
                $nHariJTHTMP = self::GetJumlahHariTunggakan($dJTHTMP, $dTgl);
            }
            $va['Tgl Macet'] = '';
            if ($nHariJTHTMP > 60 or $nHariTunggakan > 360 || ($nKolCIF == 5 and !empty($nKolCIF))) {
                $va['Kol OJK'] = 5;
                if ($nHariJTHTMP > 60) {
                    // $dTglMacetJTHTMP = date('Y-m-d', NextDay(Tgl2Time($dJTHTMP), 61));
                    $dTglMacetJTHTMP = Carbon::createFromTimestamp(func::Tgl2Time($dJTHTMP))->addDays(61)->toDateString();
                    $dTglMacet = $dTglMacetJTHTMP;
                }

                if ($nHariTunggakan > 360) {
                    // $dTglMacetTunggakan = date('Y-m-d', NextDay(Tgl2Time($dTglLastJadwal), 361));
                    $dTglMacetTunggakan = Carbon::createFromTimestamp(func::Tgl2Time($dTglLastJadwal))->addDays(361)->toDateString();
                    if ($dTglMacetTunggakan < func::Date2String($dTglRealisasi)) {
                        $dTglMacetTunggakan = $dTglMacetJTHTMP;
                    }
                    $dTglMacet = $dTglMacetTunggakan;
                }
                $va['Tgl Macet'] = $dTglMacet;
                if ($nKolCIF == 5 and !empty($nKolCIF)) {
                    // $va['Tgl Macet'] = $dTglKolCIF;
                }
            } else if ($nHariJTHTMP > 30 or $nHariTunggakan > 180 || ($nKolCIF == 4 and !empty($nKolCIF))) {
                $va['Kol OJK'] = 4;
            } else if ($nHariJTHTMP > 15 or $nHariTunggakan > 90 || ($nKolCIF == 3 and !empty($nKolCIF))) {
                $va['Kol OJK'] = 3;
            } else if ($nHariJTHTMP > 0 or $nHariTunggakan > 30 || ($nKolCIF == 2 and !empty($nKolCIF))) {
                $va['Kol OJK'] = 2;
            } else {
                $va['Kol OJK'] = 1;
            }
        }
        //HITUNG PPAP
        $va['HariTerlambat'] = $nHariTunggakan;
        $va['HariTerlambatPokok'] = $nHariTunggakanPokok;
        $va['HariTerlambatBunga'] = $nHariTunggakanBunga;

        $va['HariTunggakan'] = $nHariTunggakan;
        $va['HariTunggakanPokok'] = $nHariTunggakanPokok;
        $va['HariTunggakanBunga'] = $nHariTunggakanBunga;

        $cJenisJaminan = $va['JenisJaminan'];

        $va['Kol'] = $va['Kol OJK'];
        if ($va['Kol'] == 0 or $va['Kol'] == 1) {
            $va['PPAP'] = max($nBakiDebet, 0) * 0.5 / 100;
            $va['PPAPNJOP'] = max($nBakiDebet, 0) * 0.5 / 100;
        }
        if ($va['Kol'] == 2) {
            if ($dTgl >= "2019-12-01" && $dTgl <= "2020-11-30") {
                $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0) * 0.5 / 100;
                $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0) * 0.5 / 100;
            } else if ($dTgl >= "2020-11-30" && $dTgl <= "2021-11-30") {
                $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0) * 1 / 100;
                $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0) * 1 / 100;
            } else {
                $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0) * 3 / 100;
                $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0) * 3 / 100;
            }
        }
        if ($va['Kol'] == 3) {
            $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0) * 10 / 100;
            $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0) * 10 / 100;
        }
        if ($va['Kol'] == 4) {
            $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0) * 50 / 100;
            $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0) * 50 / 100;
        }
        if ($va['Kol'] == 5) {
            $va['PPAP'] = max($nBakiDebet - $nTotJaminan, 0);
            $va['PPAPNJOP'] = max($nBakiDebet - $nTotJaminan, 0);
            $va['NilaiJaminanNJOP'] = $nTotJaminan;
            $nLamaMacet = self::GetJumlahHariTunggakan($va['Tgl Macet'], $dTgl);

            if ($cJenisJaminan == "6") {
                if (($va['FR'] > 36 && $va['FR'] <= 49) || ($nFrekuensiJTHTMP > 26 && $nFrekuensiJTHTMP <= 39)) {

                    $va['PPAP'] = max($nBakiDebet - ($nTotJaminan * 0.5), 0);
                    $va['PPAPNJOP'] = max($nBakiDebet - ($nTotJaminan * 0.5), 0);
                    $va['NilaiJaminanNJOP'] = $nTotJaminan * 0.5;
                }
                if ($va['FR'] > 61 || $nFrekuensiJTHTMP > 51) {

                    $va['PPAP'] = max($nBakiDebet, 0);
                    $va['PPAPNJOP'] = max($nBakiDebet, 0);
                    $va['NilaiJaminanNJOP'] = 0;
                }
            } else {
                //if($va['FR'] > 24 && $va['FR'] <= 36 || $nFrekuensiJTHTMP > 14 && $nFrekuensiJTHTMP <= 27){
                // if($nLamaMacet > 365 and $nLamaMacet <= 730){
                if ($nLamaMacet > 730 and $nLamaMacet <= 1095) {
                    $va['PPAP'] = max($nBakiDebet - ($nTotJaminan * 0.5), 0);
                    $va['PPAPNJOP'] = max($nBakiDebet - ($nTotJaminan * 0.5), 0);
                    $va['NilaiJaminanNJOP'] = $nTotJaminan * 0.5;
                }

                //if($va['FR'] > 36 || $nFrekuensiJTHTMP > 27){
                // if($nLamaMacet > 730){
                if ($nLamaMacet > 1095) {
                    $va['PPAP'] = max($nBakiDebet, 0);
                    $va['PPAPNJOP'] = max($nBakiDebet, 0);
                    $va['NilaiJaminanNJOP'] = 0;
                }
            }
        }

        if (!empty($cError)) {
            echo 'alert("' . $cError . '");';
        }
        $vaKeteranganKol = self::GetKeteranganKol($dTgl);
        $va['Keterangan Kol'] = $vaKeteranganKol[$va['Kol']];

        return $va;
    }

    public static function getPerhitunganBunga()
    {
        $va = [
            '1' => 'Flat',
            '3' => 'Menurun / Reguler',
            '5' => 'Sliding'
        ];
        return $va;
    }

    public static function getSaldoCekList($tgl, $rekening, $cCabang, $cJenisGabungan)
    {
        $saldo = 0;
        $data = DB::table('bukubesar as b')
            ->leftJoin('cabang as c', 'c.Kode', '=', 'b.Cabang')
            ->where('tgl', '<=', $tgl)
            ->where('b.Rekening', '=', $rekening)
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->select(DB::raw('IFNULL(SUM(b.debet - b.kredit), 0) AS Saldo'))
            ->first();
        if ($data) {
            $saldo = abs($data->Saldo);
        }
        return $saldo;
    }

    public static function getGolonganDepositoPeriode($rekening, $tgl)
    {
        $retval = '';
        $exists = DB::table('deposito_perubahangolongan')
            ->where('Rekening', '=', $rekening)
            ->where('Tgl', '<=', $tgl)
            ->exists();
        if ($exists) {
            $data = DB::table('deposito_perubahangolongan')
                ->where('Rekening', '=', $rekening)
                ->where('Tgl', '<=', $tgl)
                ->select('GolonganDeposito')
                ->first();
            if ($data) {
                $retval = $data->GolonganDeposito;
            }
        } else {
            $data = Deposito::where('Rekening', $rekening)->first();
            if ($data) {
                $retval = $data->GolonganDeposito;
            }
        }
        return $retval;
    }

    public static function getAngsuranKe($rekening, $tgl, $select)
    {
        $data = DB::table('angsuran')
            ->where('Rekening', '=', $rekening)
            ->where('Tgl', '<=', $tgl)
            ->where('Faktur', 'LIKE', 'AG%')
            // ->whereRaw("$select > 0")
            ->count();
        return $data;
    }

    public static function getKewajibanPokok($cRekening, $dTgl, $lTanpaAngsuran = false)
    {
        $instance = new self();
        // $nTgl = Func::Tgl2Time($dTgl);
        $cTgl = Func::Date2String($dTgl);
        $nRetval = 0;

        $debitur = DB::table('debitur AS d')
            ->leftJoin('angsuran AS a', function ($join) use ($cTgl) {
                $join->on('a.Rekening', '=', 'd.Rekening')
                    ->where('a.Tgl', '<', $cTgl);
            })
            ->select(
                'd.Lama',
                'd.Tgl',
                'd.Plafond',
                'd.CaraPerhitungan',
                DB::raw('IFNULL(SUM(a.KPokok),0) AS PembayaranPokok')
            )
            ->where('d.Rekening', '=', $cRekening)
            ->groupBy('d.Rekening')
            ->first();
        if ($debitur) {
            $nKe = $instance->GetKe(Func::String2Date($debitur->Tgl), $dTgl, $debitur->Lama); // Asumsikan GetKe dan String2Date adalah fungsi yang ada di Laravel
            for ($n = 1; $n <= $nKe; $n++) {
                $nRetval += $instance->getAngsuranPokok($cRekening, $n); // Asumsikan GetAngsuranPokok adalah fungsi yang ada di Laravel
            }

            if (!$lTanpaAngsuran) {
                $nRetval -= $debitur->PembayaranPokok;
            }
        }

        return max($nRetval, 0);
    }

    public static function getRekeningKasTeller($UserName, $tgl)
    {
        $data = DB::table('username')
            ->select(
                'UserName',
                'FullName',
                'KasTeller'
            )
            ->where('UserName', '=', $UserName)
            ->first();
        if ($data) {
            $kasTeller = $data->KasTeller;
            $data2 = DB::table('username_kantorkas')
                ->select(
                    'Tgl',
                    'KasTeller'
                )
                ->where('UserName', '=', $UserName)
                ->where('Tgl', '<=', $tgl)
                ->orderByDesc('Tgl')
                ->limit(1)
                ->first();
            if ($data2) {
                $kasTeller = $data2->KasTeller;
            }
        }
        return $kasTeller;
    }

    public static function getCabangInduk($cCabang)
    {
        // $unit = self::getKantorUnit($cUsername);
        $vaData = DB::table('cabang')
            ->select('KodeInduk')
            ->where('Kode', '=', $cCabang)
            ->first();
        if ($vaData) {
            $cCabang = $vaData->KodeInduk;
        }
        return $cCabang;
    }

    public static function getKantorUnit($username)
    {
        $data = DB::table('username')
            ->select('Unit')
            ->where('UserName', '=', $username)
            ->first();
        if ($data) {
            $unit = $data->Unit;
        }
        return $unit;
    }

    public static function GetJumlahHariTunggakan($dTglAwal, $dTglAkhir)
    {
        $nDay = 0;
        $dTglAwal = Carbon::parse($dTglAwal);
        $dTglAkhir = Carbon::parse($dTglAkhir);

        if ($dTglAkhir >= $dTglAwal) {
            $cServer = 'Windows';
            if ($cServer == 'LINUX') {
                $diff = $dTglAwal->diff($dTglAkhir);
                $nDay = $diff->days;
                $nDay += 1;
            } else {
                $nDay = 0;
                for ($dTgl = $dTglAwal; $dTgl <= $dTglAkhir; $dTgl = $dTgl->addDay()) {
                    $nDay++;
                }
            }

            // Perbaikan: tambahkan 1 jika nDay masih 0
            if ($nDay == 0) $nDay += 1;
        }

        return $nDay;
    }

    public static function getRRA($rekening, $tgl)
    {
        $nRRA = 0;

        $vaTgl = explode("-", $tgl);
        $dBlnHariIni = $vaTgl[2] . "-" . $vaTgl[1];

        $carbonTgl = Carbon::parse($tgl);
        $dAwal = $carbonTgl->startOfMonth();
        $nTglAwal = $dAwal->timestamp;
        $dBulanLalu = $dAwal->subDay()->toDateString();

        $debiturData = DB::table('debitur')
            ->select('Plafond', 'SukuBunga', 'Lama', 'CaraPerhitungan', 'Tgl')
            ->where('Rekening', $rekening)
            ->first();

        if ($debiturData) {
            $vaTotal = self::getTotalPembayaranKredit($rekening, Func::Date2String($dBulanLalu));
            $nPembayaranPokok = $vaTotal['PembayaranPokok'];
            $nPembayaranBunga = $vaTotal['PembayaranBunga'];

            $vaTglRealisasi = explode("-", $debiturData->Tgl);
            $dBlnRealisasi = $vaTglRealisasi[0] . "-" . $vaTglRealisasi[1];

            $vaT = self::getTunggakan(
                $rekening,
                $dBulanLalu,
                Func::String2Date($debiturData->Tgl),
                $debiturData->CaraPerhitungan,
                $debiturData->Lama,
                $debiturData->Plafond,
                $nPembayaranPokok,
                $debiturData->SukuBunga,
                $nPembayaranBunga
            );

            // dd($vaT);

            $nSelisihHari = $carbonTgl->day - Carbon::createFromFormat('Y-m-d', $debiturData->Tgl)->day + 1;
            if ($nSelisihHari < 0) {
                $nSelisihHari = 0;
            }

            $nSaldoPokok = self::getBakiDebet($rekening, $dBulanLalu);
            $nBagiHari = $carbonTgl->day;

            if ($dBlnHariIni !== $dBlnRealisasi) {
                if ($vaT['Kol_Akhir'] <= '2') {
                    if ($debiturData->CaraPerhitungan == "1") {
                        $nRRA = $vaT['T_Bunga_Akhir'] + ($debiturData->Plafond * $debiturData->SukuBunga * $nSelisihHari) / (12 * $nBagiHari * 100);
                    } else {
                        //$nRRA  = $vaT['T_Bunga_Akhir'] + ($nSaldoPokok * $debiturData->SukuBunga * $nSelisihHari) / (12 * $nBagiHari * 100);
                        $nRRA = $vaT['T_Bunga_Akhir'] + self::getRRAHarian($rekening, $dBulanLalu);
                    }
                }
            }
        }

        return $nRRA;
    }

    public static function getRRAHarian($cRekening, $dTgl)
    {
        $dTgl = Func::Date2String($dTgl);
        $nBakiDebet = self::getBakiDebet($cRekening, $dTgl);

        $carbonTgl = Carbon::parse($dTgl);
        $dBulanLalu = $carbonTgl->subMonth()->endOfMonth();
        $nJumlahHariAkhirBulan = $carbonTgl->daysInMonth;
        $nJumlahHariAkhirBulanLalu = $dBulanLalu->daysInMonth;
        $nHari = $carbonTgl->day;

        if ($dTgl === $carbonTgl->toDateString()) {
            $nHari = 31;
        }

        $nAccrual = 0;
        $cError = '';

        $debiturData = DB::table('debitur as d')
            ->select('d.rekening', 'd.plafond', 'd.sukubunga', 'd.lama', 'd.caraperhitungan', 'd.tgl as tglrealisasi', DB::raw('DAY(d.Tgl) as hari'), DB::raw('SUM(a.dpokok - a.kpokok) as saldopokok'), 'd.golongankredit', 'd.cabangentry')
            ->leftJoin('angsuran as a', function ($join) use ($carbonTgl) {
                $join->on('a.rekening', '=', 'd.rekening')
                    ->where('a.tgl', '<=', $carbonTgl);
            })
            ->where('d.tgl', '<=', $carbonTgl->toDateString())
            ->where('d.rekening', $cRekening)
            ->groupBy('d.rekening')
            ->having('saldopokok', '>', 0)
            ->orderBy('d.rekening')
            ->first();

        if ($debiturData) {
            $cRekening = $debiturData->rekening;
            $dTglRealisasi = $debiturData->tglrealisasi;
            $cCaraPerhitungan = $debiturData->caraperhitungan;
            $nSukuBunga = $debiturData->sukubunga;
            $nLama = $debiturData->lama;
            $nPlafond = $debiturData->plafond;
            $cGolonganKredit = $debiturData->golongankredit;
            $cCabangEntry = $debiturData->cabangentry;

            $nKe = self::getKe($dTglRealisasi, $carbonTgl->toDateString(), $nLama);
            $dTglJadwalBulanDepan = $carbonTgl->copy()->addMonths($nKe + 1)->toDateString();
            $nAccrual = 0;
            $nKeAngsuran = $nKe + 1;
            if ($nKeAngsuran > $nLama) {
                $nKeAngsuran = $nLama;
            }
            $nPlus = 0;
            if ($carbonTgl->toDateString() <= '2016-08-31') $nPlus = 1;
            if ($nKe + $nPlus < $nLama) {
                $nAngsuranBungaBulanDepan = self::getAngsuranBunga($cRekening, $nKeAngsuran, $nBakiDebet);
                $nJumlahHari = $nJumlahHariAkhirBulan;
                if ($nHari < $debiturData->hari) {
                    $nJumlahHari = $nJumlahHariAkhirBulanLalu;
                }
                $carbonTglJadwal = $carbonTgl->copy()->addMonths($nKe);
                $nSelisihHari = $carbonTgl->diffInDays($carbonTglJadwal);
                $nAngsuranBungaBulanDepanHarian = ($nAngsuranBungaBulanDepan / $nJumlahHari);
                $nAccrualHarian = round($nAngsuranBungaBulanDepanHarian * $nSelisihHari, 2);

                $nAccrual = round($nAccrualHarian);
            } else {
                $nAccrual = 0;
            }
        }

        return $nAccrual;
    }

    public static function
    getAmortisasiDebitur($cRekening, $cJenis = '', $dTgl = '')
    {
        $dTgl = Carbon::parse($dTgl);

        $dBulan = $dTgl->format('m');
        $dTahun = $dTgl->format('Y');

        $dAwal = Carbon::createFromDate($dTahun, $dBulan, 1);
        $dAkhir = $dAwal->copy()->endOfMonth();
        $dBulanLalu = $dAwal->copy()->subDay();

        $cWhere5Juta = '';

        if ($dAkhir >= '2014-02-28') {
            // $cWhere5Juta = "and d.plafond > 5000000";
        }

        $vaArray = [
            "Nominal" => 0,
            "Lama" => 0,
            "Ke" => 0,
            "Sisa Awal" => 0,
            "Awal" => 0,
            "Amortisasi" => 0,
            "Akhir" => 0,
            "Sisa" => 0,
        ];

        $query = DB::table('debitur as d')
            ->select(DB::raw("'R' as Type"))
            ->select(DB::raw("RIGHT(d.RekeningLama, 5) as RekLama"))
            ->select('d.Plafond')
            ->select('d.RekeningLama as Rek')
            ->select('d.ID')
            ->select(DB::raw("REPLACE(CONCAT(d.Rekening, d.Tgl, 'A'), '-', '') as Status"))
            ->select('d.Rekening')
            ->select('d.Tgl')
            ->select('r.Nama')
            ->select(DB::raw("d.{$cJenis} as JenisAmortisasi"))
            ->select('d.Lama')
            ->select(DB::raw('IFNULL(SUM(dpokok - kpokok), 0) as BakiDebetAwal'))
            ->select('d.GolonganKredit')
            ->leftJoin('angsuran as a', function ($join) use ($dBulanLalu) {
                $join->on('a.rekening', '=', 'd.rekening')
                    ->where('a.TGL', '<=', $dBulanLalu);
            })
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('cabang as c', function ($join) use ($dAkhir) {
                $join->on('c.Kode', '=', DB::raw('IFNULL((SELECT CabangEntry FROM debitur_cabang WHERE Rekening = d.Rekening AND tgl <= ? ORDER BY tgl DESC LIMIT 1), d.CabangEntry)'))
                    ->addBinding($dAkhir->toDateString(), 'select');
            })
            ->where('d.Rekening', $cRekening)
            ->where('d.statuspencairan', '1')
            ->where('d.Tgl', '<=', $dAkhir)
            ->where('d.tglwriteoff', '>', $dAkhir)
            ->when($cWhere5Juta, function ($query, $cWhere5Juta) {
                return $query->whereRaw($cWhere5Juta);
            })
            ->groupBy('d.rekening')
            ->having('JenisAmortisasi', '<>', 0)
            ->having(function ($query) use ($dBulanLalu, $dAkhir) {
                $query->having('BakiDebetAwal', '>', 0)
                    ->orWhere(function ($query) use ($dBulanLalu, $dAkhir) {
                        $query->where('Tgl', '>', $dBulanLalu)
                            ->where('Tgl', '<=', $dAkhir);
                    });
            })
            ->orderBy('GolonganKredit')
            ->orderBy('Rekening')
            ->orderBy('Lama')
            ->orderBy('RekLama')
            ->get();


        $nRow = 0;
        $nMaxRow = 0;
        $cError = '';

        foreach ($query as $dbRow) {
            $nMaxRow++;
            $nRow++;

            $nTglRealisasi = Carbon::parse($dbRow->Tgl);

            if (($dbRow->JenisAmortisasi > 0 && $dbRow->BakiDebetAwal > 0) || ($dbRow->Tgl >= $dAwal && $dbRow->Tgl <= $dAkhir)) {
                $nBakiDebet = self::getBakiDebet($dbRow->Rekening, $dAkhir);
                $va = self::getAmortisasiHarianEtap($dbRow->Rekening, $dAkhir, $dbRow->Tgl, $dbRow->Lama, $dbRow->BakiDebetAwal, $nBakiDebet, $cJenis, $dbRow->JenisAmortisasi);

                $dJTHTMP = Carbon::parse($nTglRealisasi)->addMonths($dbRow->Lama)->format('d-m-Y');

                if ($va['$cJenis Awal'] + $va['Realisasi'] + $va['Amortisasi'] == 0) {
                    $va['$cJenis Akhir'] = $dbRow->JenisAmortisasi;

                    if (Carbon::parse($dJTHTMP) < $dAwal) {
                        $va['$cJenis Akhir'] = 0;
                    }
                }

                if ($va['$cJenis Awal'] == $va['Sisa $cJenis'] && $va['Baki Debet'] == 0) {
                    $va['$cJenis Awal'] = 0;
                    $va['Sisa $cJenis'] = 0;
                }

                if ($va['$cJenis Akhir'] == $va['Amortisasi'] && $va['Ke'] == $dbRow->Lama) {
                    $va['$cJenis Awal'] = 0;
                    $va['Sisa $cJenis'] = 0;
                }

                if ($va['Realisasi'] + $va['Amortisasi'] + $va['$cJenis Akhir'] > 0) {
                    $nTgl = Carbon::parse($dbRow->Tgl);

                    $cKey = $dbRow->GolonganKredit . "-" . $dbRow->Status;

                    $nSisaAwal = $dbRow->JenisAmortisasi - $va['$cJenis Awal'];

                    if ($dbRow->Tgl > $dBulanLalu) {
                        $nSisaAwal = 0;
                    }

                    $vaArray = [
                        "Nominal" => $dbRow->JenisAmortisasi,
                        "Lama" => $dbRow->Lama,
                        "Ke" => $va['Ke'],
                        "Sisa Awal" => $nSisaAwal,
                        "Awal" => $va['$cJenis Awal'],
                        "Amortisasi" => $va['Amortisasi'],
                        "Akhir" => $va['$cJenis Akhir'],
                        "Sisa" => $va['Sisa $cJenis'],
                    ];
                }
            }
        }

        return $vaArray;
    }
    public static function getAmortisasiHarianEtap($cRekening, $dTgl, $dTglRealisasi, $nLama, $nBakiDebetBulanLalu, $nBakiDebetSekarang, $cJenis = '', $nJenis = 0)
    {
        $dTgl = Carbon::parse($dTgl);
        $va = [
            '$cJenis' => 0,
            'Ke Awal' => 0,
            'Ke' => 0,
            '$cJenis Awal' => 0,
            'Realisasi' => 0,
            'Amortisasi' => 0,
            '$cJenis Akhir' => 0,
            'Baki Debet' => 0,
            'Sisa $cJenis' => 0,
        ];
        $dAwal = $dTgl->startOfMonth();
        $nTglAwal = $dAwal->timestamp;
        $nTglAkhir = $dAwal->copy()->endOfMonth()->timestamp;
        $dBulanLalu = $dTgl->subDay()->toDateString();
        $nDayRealisasi = $dTglRealisasi->day;
        $nJumlahHariBulanRealisasi = $dTglRealisasi->daysInMonth;
        $nTglRealisasi = $dTglRealisasi->timestamp;
        $nAmortisasiBulanan = round($nJenis / $nLama);

        if ($nAmortisasiBulanan > 0) {
            $nPenambahan = 1;
            $nKe = $dTglRealisasi->diffInDays($dTgl) / 30 + $nPenambahan;
            $nKeAwal = $dTglRealisasi->diffInDays($dBulanLalu) / 30;
            $va['Ke Awal'] = $nKeAwal;
            $nBakiDebetAwal = $nBakiDebetBulanLalu;
            $nBakiDebet = $nBakiDebetSekarang;
            $va['Baki Debet'] = $nBakiDebet;
            $nAmortisasiHarian = round($nAmortisasiBulanan / $nJumlahHariBulanRealisasi);
            $nJumlahHarian = ($nJumlahHariBulanRealisasi - $nDayRealisasi) + 1;
            $nAmortisasiRealisasi = $nAmortisasiBulanan;

            if ($dAwal->timestamp <= $nTglRealisasi && $nTglAkhir >= $nTglRealisasi) {
                if ($nBakiDebet == 0) {
                    $nAmortisasiBulanan = $nJenis;
                }

                $va['$cJenis'] = $nJenis;
                $va['Ke'] = $nKe;
                $va['$cJenis Awal'] = 0;

                if ($nBakiDebet == 0 && $nKe == 0) {
                    $va['Amortisasi'] = $nJenis;
                    $va['$cJenis Akhir'] = $nJenis;
                    $va['Sisa $cJenis'] = 0;
                } else {
                    $va['Amortisasi'] = $nAmortisasiRealisasi;
                    $va['$cJenis Akhir'] = min($nAmortisasiBulanan, $nAmortisasiRealisasi);
                    $va['Sisa $cJenis'] = $nJenis - $va['$cJenis Akhir'];
                }
            } elseif ($nBakiDebetAwal > 0 && $nKeAwal < $nLama) {
                $va['$cJenis'] = $nJenis;
                $va['Ke'] = $nKe;
                $va['$cJenis Awal'] = max($nAmortisasiBulanan * ($nKeAwal), 0) + $nAmortisasiRealisasi;

                if ($nKe > $nLama) {
                    $va['$cJenis Awal'] = $nJenis;
                }

                if ($va['$cJenis Awal'] > $nJenis) {
                    $va['$cJenis Awal'] = $nJenis;
                }

                if ($nBakiDebet == 0 || $nKe >= $nLama) {
                    $nAmortisasiBulanan = $nJenis - $va['$cJenis Awal'];
                }

                $va['Amortisasi'] = $nAmortisasiBulanan;
                $va['$cJenis Akhir'] = min($nAmortisasiBulanan + $va['$cJenis Awal'], $nJenis);
                $va['Sisa $cJenis'] = $nJenis - $va['$cJenis Akhir'];
            }
        }

        return $va;
    }

    public static function checkLimitPlafond(
        $TGL,
        $MUTASI,
        $REKENING,
        $KETERANGAN,
        $USER,
        $FAKTUR,
        $LEVEL = 0,
        $USERNAMEACC = "",
        $JENISACC = '1',
        $ACC = "0"
    ) {
        $va['Fiat'] = true;
        if ($JENISACC < 5) {
            $data = Username::where('UserName', $USER)->first();
            if ($data) {
                if ($JENISACC == 5 || $JENISACC == 4) {
                    $data->Plafond = 0;
                }
                if ($MUTASI > $data->Plafond) {
                    $jenisReq = $JENISACC;
                    if ($jenisReq == 1) {
                        $whereJenisReq = 'Kas';
                    };
                    if ($jenisReq == 2) {
                        $whereJenisReq = 'Tabungan';
                    };
                    if ($jenisReq == 3) {
                        $whereJenisReq = 'Deposito';
                    };
                    if ($jenisReq == 4) {
                        $whereJenisReq = 'Kredit';
                    };
                    if ($jenisReq == 5) {
                        $whereJenisReq = 'Akutansi';
                    };
                    $limit = 1;
                    $USERNAMEACC = $data->UserNameAcc;
                    $cabang = $data->Cabang;
                    $cabangInduk = $data->CabangInduk;
                    $data2 = Username::where('UserName', $USERNAMEACC)
                        ->where('Plafond', '>=', $MUTASI)
                        ->where($whereJenisReq, $jenisReq)
                        ->first();
                    if ($data2) {
                        $USERNAMEACC = $data2->UserName;
                    } else {
                        $USERNAMEACC = '';
                        $data3 = Username::where('Plafond', '>=', $MUTASI)
                            ->where('Cabang', $cabang)
                            ->where('CabangInduk', $cabangInduk)
                            ->where($whereJenisReq, $jenisReq)
                            ->orderBy('plafond')
                            ->limit($limit)
                            ->first();
                        if ($data3) {
                            $USERNAMEACC = $data3->UserName;
                        } else {
                            $USERNAMEACC = '';
                            $data4 = Username::where('Plafond', '>=', $MUTASI)
                                ->where('CabangInduk', $cabangInduk)
                                ->where($whereJenisReq, $jenisReq)
                                ->orderBy('plafond')
                                ->limit($limit)
                                ->first();
                            if ($data4) {
                                $USERNAMEACC = $data4->UserName;
                            } else {
                                $USERNAMEACC = '';
                                $data5 = Username::where('Plafond', '>=', $MUTASI)
                                    ->where($whereJenisReq, $jenisReq)
                                    ->orderBy('plafond')
                                    ->limit($limit)
                                    ->first();
                                if ($data5) {
                                    $USERNAMEACC = $data5->UserName;
                                } else {
                                    $USERNAMEACC = '';
                                }
                            }
                        }
                    }
                    $array = [
                        'DateTime' => Carbon::now(),
                        'Transaksi' => $KETERANGAN,
                        'ACC' => $ACC,
                        'UserRequest' => $USER,
                        'Date' => $TGL,
                        'Nominal' => $MUTASI,
                        'Faktur' => $FAKTUR,
                        'Rekening' => $REKENING,
                        'UserACC' => '',
                        'Level' => $LEVEL,
                        'UserNameAcc' => $JENISACC == '4' ? "PENDI" : $USERNAMEACC,
                        'Jenis' => $JENISACC
                    ];
                    Request::where('Rekening', $REKENING)
                        ->where('UserRequest', $USER)
                        ->where('Date', $TGL)
                        ->update($array);
                    $va['Fiat'] = false;
                }
            }
            $data6 = Request::where('Acc', '0')
                ->where('Rekening', $REKENING)
                ->where('UserRequest', $USER)
                ->where('Date', $TGL)
                ->first();
            if ($data6) {
                $ID = $data6->id;
            }
        }
        return $va;
    }

    public static function getSaldoAwal1($dTgl, $cRekening, $cRekening2 = '', $lRekonsiliasi = false, $cCabang = '', $lLike = true, $lPenihilan = false, $cJenisGabungan = 'A')
    {
        $dTgl = Func::Date2String($dTgl);
        $nTahun = substr($dTgl, 0, 4);

        if (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3") {
            $cSum = DB::raw('(SUM(b.kredit) - SUM(b.debet)) as Saldo');
        } else {
            $cSum = DB::raw('(SUM(b.debet) - SUM(b.kredit)) as Saldo');
        }

        $like = $lLike ? 'like' : '=';
        $like2 = $lLike ? '%' : '';

        if ($cRekening2 !== '') {
            $cLike = "b.rekening >= '$cRekening' and b.rekening <= '$cRekening2'";
        } else {
            $cLike = "b.rekening $like '$cRekening$like2'";
        }

        $cWhere = $lPenihilan ? " and b.Faktur not like 'TH$nTahun%'" : '';

        if (empty($cCabang)) {
            $cCabang = GetterSetter::getDBConfig('msKodeCabang');
        }

        // $cWhereGabungan = getWhereJenisGabungan($cJenisGabungan, $cCabang);

        $cRekeningAKA = GetterSetter::getDBConfig('msRekeningAKA');
        $cRekeningAKP = GetterSetter::getDBConfig('msRekeningAKP');
        $cWhereRAK = '';
        if ($cJenisGabungan == 'C') {
            $cWhereRAK .= " b.rekening not like '$cRekeningAKA%'";
            $cWhereRAK .= " b.rekening not like '$cRekeningAKP%'";
        }

        $saldo = DB::table('bukubesar as b')
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
            ->where('b.tgl', '<=', $dTgl);

        if (!empty($cLike)) {
            $saldo->whereRaw($cLike);
        }

        if (!empty($cWhereGabungan)) {
            $saldo->whereRaw($cWhereGabungan);
        }

        if (!empty($cWhere)) {
            $saldo->whereRaw($cWhere);
        }

        if (!empty($cWhereRAK)) {
            $saldo->whereRaw($cWhereRAK);
        }

        $saldoResult = $saldo->select($cSum)->first();

        $nSaldo = $saldoResult ? $saldoResult->Saldo : 0;

        if ($lRekonsiliasi) {
            $jurnal = DB::table('jurnal as b')
                ->where('b.tgl', '>', $dTgl)
                ->where('b.rekonsiliasi', '=', 'Y')
                ->whereRaw($cLike)
                ->whereRaw("substring(b.faktur, 1, 2) like '$cCabang%'")
                ->whereRaw($cWhere);

            $rekonsiliasiSaldo = $jurnal->select($cSum)->first();

            $nSaldo += $rekonsiliasiSaldo ? $rekonsiliasiSaldo->Saldo : 0;
        }

        if (empty($cRekening)) {
            $nSaldo = 0;
        }

        return $nSaldo;
    }

    public static function getSaldoAwal($dTgl, $cRekening, $cRekening2 = '', $lRekonsiliasi = false, $cCabang = '', $lLike = true, $lPenihilan = false, $cJenisGabungan = 'A')
    {
        $dTgl = Func::Date2String($dTgl);
        $nTahun = substr($dTgl, 0, 4);

        $cSum = (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3")
            ? DB::raw('(SUM(b.kredit) - SUM(b.debet)) as Saldo')
            : DB::raw('(SUM(b.debet) - SUM(b.kredit)) as Saldo');

        $like = $lLike ? 'LIKE' : '=';
        $like2 = $lLike ? '%' : '';

        $cLike = $cRekening2 !== ''
            ? "b.Rekening BETWEEN '$cRekening' AND '$cRekening2'"
            : "b.Rekening $like '$cRekening$like2'";

        $cWhere = $lPenihilan ? " AND b.Faktur NOT LIKE 'TH$nTahun%'" : '';

        if (empty($cCabang)) {
            $cCabang = GetterSetter::getDBConfig('msKodeCabang');
        }

        $cRekeningAKA = GetterSetter::getDBConfig('msRekeningAKA');
        $cRekeningAKP = GetterSetter::getDBConfig('msRekeningAKP');
        $cWhereRAK = $cJenisGabungan == 'C'
            ? " b.rekening NOT LIKE '$cRekeningAKA%' AND b.rekening NOT LIKE '$cRekeningAKP%'"
            : '';

        $queryBukuBesar = DB::table('bukubesar as b')
            ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
            ->where('b.tgl', '<=', $dTgl)
            ->when(
                $cJenisGabungan != 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            );

        if (!empty($cLike)) {
            $queryBukuBesar->whereRaw($cLike);
        }

        if (!empty($cWhere)) {
            $queryBukuBesar->whereRaw($cWhere);
        }

        if (!empty($cWhereRAK)) {
            $queryBukuBesar->whereRaw($cWhereRAK);
        }

        $saldo = $queryBukuBesar->select($cSum)->first();

        $nSaldo = $saldo ? $saldo->Saldo : 0;

        if ($lRekonsiliasi) {
            $queryJurnal = DB::table('jurnal as b')
                ->where('b.tgl', '>', $dTgl)
                ->where('b.rekonsiliasi', '=', 'Y')
                ->whereRaw($cLike)
                ->whereRaw("SUBSTRING(b.faktur, 1, 2) LIKE '$cCabang%'")
                ->whereRaw($cWhere);

            $rekonsiliasiSaldo = $queryJurnal->select($cSum)->first();

            $nSaldo += $rekonsiliasiSaldo ? $rekonsiliasiSaldo->Saldo : 0;
        }

        if (empty($cRekening)) {
            $nSaldo = 0;
        }

        return $nSaldo;
    }

    public static function getWhereJenisGabungan($cJenisGabungan = 'A', $cCabang = '')
    {
        $cWhere = '';
        if (empty($cCabang)) {
            $cCabang = GetterSetter::getDBConfig('msKodeCabang');
        }
        if ($cJenisGabungan === 'A') {
            $cWhere = "c.Kode = '$cCabang'";
        }
        if ($cJenisGabungan == 'B') {
            $cCabangInduk = GetterSetter::getCabangInduk($cCabang);
            $cWhere = "c.KodeInduk = '$cCabangInduk'";
        }
        return $cWhere;
    }
}
