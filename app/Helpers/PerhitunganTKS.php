<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerhitunganTKS
{
    public static function getNilaiMasukRekening($cSandiI, $cSandiD, $dTahun)
    {
        $valueReturn = 0.00;
        $sandiD = trim($cSandiD);

        $methods = [
            'E1' => 'getE1',
            'E2' => 'getE2',
            'E3' => 'getE3',
            'E4' => 'getE4',
            'E5' => 'getE5',
            'E6' => 'getE6',
            'E7' => 'getE7',
            'E8' => 'getE8',
            'A2' => 'getA2',
            'R9' => 'getR9',
            'R10' => 'getR10',
            'R11' => 'getR11',
            'R12' => 'getR12',
            'L2' => 'getL2',
            'S5' => 'getS5',
            'S7' => 'getS7',
            'S8' => 'getS8',
            'S9' => 'getS9',
            'S10' => 'getS10',
            'S11' => 'getS11'
        ];

        if (array_key_exists($cSandiI, $methods)) {
            $vaSandi = self::{$methods[$cSandiI]}($dTahun);

            if (isset($vaSandi[$sandiD])) {
                $valueReturn = $vaSandi[$sandiD];
            }
        }

        return $valueReturn;
    }

    public static function getE1($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00
        ];

        $cRekeningA = explode(";", GetterSetter::getDBConfig('msE1ATotalPinjamanBeredar'));
        $cRekeningB = explode(";", GetterSetter::getDBConfig('msE1BDanaCadanganResiko'));

        $valueReturn['A'] = self::calculateTotal($cRekeningA, $dTahun);
        $valueReturn['B'] = self::calculateTotal($cRekeningB, $dTahun);
        $valueReturn['C'] = self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE2($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00
        ];

        $cRekeningA = explode(";", GetterSetter::getDBConfig('msE2ATotalInvestasiLikuid'));

        $valueReturn['A'] = self::calculateTotal($cRekeningA, $dTahun);
        $valueReturn['B'] = self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE3($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00
        ];

        $cRekeningA = explode(";", GetterSetter::getDBConfig('msE3ATotalInvestasiKeuangan'));

        $valueReturn['A'] = self::calculateTotal($cRekeningA, $dTahun);
        $valueReturn['B'] = self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE4($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msE4ATotalInvestasiNonKeuangan');
        $arrayRekeningA = array_filter(explode(";", $cRekeningA));

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningBuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningBuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE5($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msE5ATotalSimpananNonSaham');
        $arrayRekeningA = array_filter(explode(";", $cRekeningA));

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE6($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msE6ATotalKPJangkaPendek');
        $cRekeningB = GetterSetter::getDBConfig('msE6BTotalKPJangkaPanjang');

        $arrayRekeningA = array_filter(explode(";", $cRekeningA));
        $arrayRekeningB = array_filter(explode(";", $cRekeningB));

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;

        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $totalB += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['B'] = $totalB;
        $valueReturn['C'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE7($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msE7ATotalSimpananSahamAnggota');
        $arrayRekeningA = array_filter(explode(";", $cRekeningA)); // Menggunakan array_filter untuk mengabaikan nilai kosong

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getE8($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msE8ATotalModalLembaga');
        $arrayRekeningA = array_filter(explode(";", $cRekeningA));

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getA2($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $rekeningA = GetterSetter::getDBConfig('msA2ATotalAsetYangTidakMenghasilkan');
        $arrayRekeningA = array_filter(explode(";", $rekeningA));

        $nTotalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $cRekeningbuilder = $nilai;
                $nTotalA += (float) self::getSaldoAwal($cRekeningbuilder, '', $dTahun, false);
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float) self::getSaldoAwal('1', '', $dTahun, true);

        return $valueReturn;
    }

    public static function getR9($dTahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        $cRekeningA = GetterSetter::getDBConfig('msR9ATotalBiayaOperasional');
        $arrayRekeningA = explode(';', trim($cRekeningA));

        $nTotalA = 0.00;

        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $nSaldoAwal = self::getSaldoAwal($nilai, '', $dTahun, false);
                $nTotalA += (float)$nSaldoAwal;
            }
        }

        $valueReturn['A'] = $nTotalA;
        $valueReturn['B'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $dTahun, true);
        $valueReturn['C'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $dTahun - 1, true);

        return $valueReturn;
    }

    public static function getR10($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        $rekeningA = GetterSetter::getDBConfig('msR10ATotalBiayaProvisi');
        $arrayRekeningA = explode(';', trim($rekeningA));

        $totalA = 0.00;

        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $saldoAwal = self::getSaldoAwal($nilai, '', $tahun, false);
                $totalA += (float)$saldoAwal;
            }
        }

        $valueReturn['A'] = $totalA;
        $valueReturn['B'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $tahun, true);
        $valueReturn['C'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $tahun - 1, true);

        return $valueReturn;
    }

    public static function getR11($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        // Mengambil nilai rekening A
        $rekeningA = GetterSetter::getDBConfig('msR11ATotalPendapatanAtauBiayaLainTahunBerjalan');
        $arrayRekeningA = explode(';', trim($rekeningA));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwal($nilai, '', $tahun, false);
            }
        }

        // Assign nilai ke dalam array
        $valueReturn['A'] = $totalA;
        $valueReturn['B'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $tahun, true);
        $valueReturn['C'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', (string)((int)$tahun - 1), true);

        return $valueReturn;
    }

    public static function getR12($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        // Mengambil nilai rekening A
        $rekeningA = GetterSetter::getDBConfig('msR12ALabaBersih');
        $arrayRekeningA = explode(';', trim($rekeningA));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwal($nilai, '', $tahun, false);
            }
        }

        // Assign nilai ke dalam array
        $valueReturn['A'] = $totalA;
        $valueReturn['B'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', $tahun, true);
        $valueReturn['C'] = (float)self::getSaldoAwalSampaiAkhirTahun('1', '', (string)((int)$tahun - 1), true);

        return $valueReturn;
    }

    public static function getL2($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
            'C' => 0.00,
        ];

        // Mengambil nilai rekening A, B, dan C
        $rekeningA = GetterSetter::getDBConfig('msL2ATotalCadanganLikuiditasAsetYangMenghasilkan');
        $rekeningB = GetterSetter::getDBConfig('msL2BTotalCadanganLikuiditasAsetYangTidakMenghasilkan');
        $rekeningC = GetterSetter::getDBConfig('msL2CTotalSimpananNonSaham');

        $arrayRekeningA = explode(';', trim($rekeningA));
        $arrayRekeningB = explode(';', trim($rekeningB));
        $arrayRekeningC = explode(';', trim($rekeningC));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwal($nilai, '', $tahun, false);
            }
        }
        $valueReturn['A'] = $totalA;

        // Menghitung total B
        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $totalB += (float)self::getSaldoAwal($nilai, '', $tahun, false);
            }
        }
        $valueReturn['B'] = $totalB;

        // Menghitung total C
        $totalC = 0.00;
        foreach ($arrayRekeningC as $nilai) {
            if (!empty($nilai)) {
                $totalC += (float)self::getSaldoAwal($nilai, '', $tahun, false);
            }
        }
        $valueReturn['C'] = $totalC;

        return $valueReturn;
    }

    public static function getS5($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        // Mengambil nilai rekening A dan B dari konfigurasi
        $rekeningA = GetterSetter::getDBConfig('msS5ATotalSimpananNonSahamTahunBerjalan');
        $rekeningB = GetterSetter::getDBConfig('msS5BTotalSimpananNonSahamSampaiTahunLalu');

        $arrayRekeningA = explode(';', trim($rekeningA));
        $arrayRekeningB = explode(';', trim($rekeningB));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', $tahun, false);
            }
        }
        $valueReturn['A'] = $totalA;

        // Menghitung total B
        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $totalB += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', (string)((int)$tahun - 1), false);
            }
        }
        $valueReturn['B'] = $totalB;

        return $valueReturn;
    }

    public static function getS7($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        // Mengambil nilai rekening A dan B dari konfigurasi
        $rekeningA = GetterSetter::getDBConfig('msS7ATotalSimpananSahamAnggotaTahunBerjalan');
        $rekeningB = GetterSetter::getDBConfig('msS7BTotalSimpananSahamAnggotaSampaiAkhirTahunLalu');

        $arrayRekeningA = explode(';', trim($rekeningA));
        $arrayRekeningB = explode(';', trim($rekeningB));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', $tahun, false);
            }
        }
        $valueReturn['A'] = $totalA;

        // Menghitung total B
        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $totalB += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', (string)((int)$tahun - 1), false);
            }
        }
        $valueReturn['B'] = $totalB;

        return $valueReturn;
    }

    public static function getS8($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        // Mengambil nilai rekening A dan B dari konfigurasi
        $rekeningA = GetterSetter::getDBConfig('msS8AModalLembagaTahunBerjalan');
        $rekeningB = GetterSetter::getDBConfig('msS8BModalLembagaSampaiAkhirTahunLalu');

        $arrayRekeningA = explode(';', trim($rekeningA));
        $arrayRekeningB = explode(';', trim($rekeningB));

        // Menghitung total A
        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', $tahun, false);
            }
        }
        $valueReturn['A'] = $totalA;

        // Menghitung total B
        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $totalB += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', (string)((int)$tahun - 1), false);
            }
        }
        $valueReturn['B'] = $totalB;

        return $valueReturn;
    }

    public static function getS9($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $rekeningA = GetterSetter::getDBConfig('msS9AModalLembagaBersihTahunBerjalan');
        $rekeningB = GetterSetter::getDBConfig('msS9BModalLembagaBersihSampaiAkhirTahunLalu');

        $arrayRekeningA = explode(';', trim($rekeningA));
        $arrayRekeningB = explode(';', trim($rekeningB));

        $totalA = 0.00;
        foreach ($arrayRekeningA as $nilai) {
            if (!empty($nilai)) {
                $totalA += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', $tahun, false);
            }
        }
        $valueReturn['A'] = $totalA;

        $totalB = 0.00;
        foreach ($arrayRekeningB as $nilai) {
            if (!empty($nilai)) {
                $totalB += (float)self::getSaldoAwalSampaiAkhirTahun($nilai, '', (string)((int)$tahun - 1), false);
            }
        }
        $valueReturn['B'] = $totalB;

        return $valueReturn;
    }

    public static function getS10($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        try {
            // Tanggal akhir tahun berjalan
            $tglAkhir = "$tahun-12-31";

            // Hitung jumlah anggota sampai akhir tahun berjalan
            $jumlahAnggota = DB::table('registernasabah')
                ->where('Tgl', '<=', $tglAkhir)
                ->count();

            // Set nilai A
            $valueReturn['A'] = $jumlahAnggota;

            // Tanggal akhir tahun sebelumnya
            $tahunLalu = $tahun - 1;
            $tglAkhirTahunLalu = "$tahunLalu-12-31";

            // Hitung jumlah anggota sampai akhir tahun sebelumnya
            $jumlahAnggotaTahunLalu = DB::table('registernasabah')
                ->where('Tgl', '<=', $tglAkhirTahunLalu)
                ->count();

            // Set nilai B
            $valueReturn['B'] = $jumlahAnggotaTahunLalu;
        } catch (\Exception $e) {
            throw $e;
        }

        return $valueReturn;
    }

    public static function getS11($tahun)
    {
        $valueReturn = [
            'A' => 0.00,
            'B' => 0.00,
        ];

        $valueReturn['A'] = (float) self::getSaldoAwalSampaiAkhirTahun('1', '', $tahun, true);
        $valueReturn['B'] = (float) self::getSaldoAwalSampaiAkhirTahun('1', '', (string)((int)$tahun - 1), true);

        return $valueReturn;
    }

    private static function calculateTotal(array $rekeningArray, $tahun)
    {
        return collect($rekeningArray)->filter()->reduce(function ($total, $rekening) use ($tahun) {
            return $total + (float) self::getSaldoAwal($rekening, '', $tahun, false);
        }, 0.00);
    }

    public static function getSaldoAwal($rekening, $rekening2 = '', $tahun, $likeQuery = false)
    {
        // Cache result for 5 minutes (300 seconds)
        return Cache::remember("saldo_awal_{$rekening}_{$rekening2}_{$tahun}", 300, function () use ($rekening, $rekening2, $tahun, $likeQuery) {

            $saldo = 0.00;
            $tglAwal = "{$tahun}-01-01";
            $tglAkhir = "{$tahun}-12-31";

            $sum = DB::raw("IF(SUBSTRING(b.rekening, 1, 1) IN ('2', '3', '4'), SUM(b.kredit - b.debet), SUM(b.debet - b.kredit)) as Saldo");

            // Dynamic condition building
            $condition = function ($query) use ($rekening, $rekening2, $likeQuery) {
                if (!empty($rekening2)) {
                    $query->whereBetween('b.rekening', [$rekening, $rekening2]);
                } else {
                    $query->where('b.rekening', $likeQuery ? 'LIKE' : '=', $rekening . ($likeQuery ? '%' : ''));
                }
            };

            // Perform the optimized query
            $result = DB::table('bukubesar as b')
                ->whereBetween('b.tgl', [$tglAwal, $tglAkhir])
                ->where($condition)
                ->select($sum)
                ->first();

            return $result ? (string) $result->Saldo : '0.00';
        });
    }

    public static function getSaldoAwalSampaiAkhirTahun($rekening, $rekening2 = '', $tahun, $likeQuery = false)
    {
        // Create a unique cache key based on the function parameters
        $cacheKey = "saldo_awal_sampai_akhir_tahun_{$rekening}_{$rekening2}_{$tahun}_{$likeQuery}";

        // Cache result for 5 minutes (300 seconds)
        return Cache::remember($cacheKey, 300, function () use ($rekening, $rekening2, $tahun, $likeQuery) {
            $valueReturn = 0.00;

            // Tanggal akhir Desember
            $tglAkhir = \Carbon\Carbon::create($tahun, 12, 31)->format('Y-m-d');

            $splitRek = explode('.', $rekening);

            // Tentukan kondisi sum berdasarkan rekening
            $sum = in_array($splitRek[0], ['2', '3', '4']) ? 'b.kredit - b.debet' : 'b.debet - b.kredit';

            // Tentukan kondisi LIKE atau "="
            $like = $likeQuery ? 'LIKE' : '=';
            $like2 = $likeQuery ? '%' : '';

            // Atur kondisi rekening
            if (!empty($rekening2)) {
                $rekeningCondition = [['b.rekening', '>=', $rekening], ['b.rekening', '<=', $rekening2]];
            } else {
                $rekeningCondition = [['b.rekening', $like, $rekening . $like2]];
            }

            // Try to fetch the saldo from the database
            try {
                $saldo = DB::table('bukubesar as b')
                    ->selectRaw('SUM(' . $sum . ') as Saldo')
                    ->where('b.tgl', '<=', $tglAkhir)
                    ->where($rekeningCondition)
                    ->value('Saldo');

                $valueReturn += $saldo ?? 0.00;
            } catch (\Exception $e) {
                throw $e;
            }

            return number_format($valueReturn, 2, '.', '');
        });
    }

    public static function getRatioE1($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;

        // Cek jika saldo_c bernilai 0 untuk menghindari DivisionByZeroError
        if ($saldo_c == 0) {
            return $valueReturn; // Mengembalikan 0 jika saldo_c adalah 0
        }

        // Perhitungan rasio
        $ratio = (($saldo_a - $saldo_b) / $saldo_c) * 100;

        // Cek apakah hasilnya infinity
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Hanya kembalikan rasio jika lebih besar dari 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE2($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;
        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }

        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE3($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;
        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE4($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;
        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }

        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE5($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;
        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE6($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;
        if ($saldo_c != 0) {
            $ratio = (($saldo_a + $saldo_b) / $saldo_c) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE7($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioE8($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioA2($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        if ($saldo_b != 0) {
            $ratio = ($saldo_a / $saldo_b) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioR9($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;

        $denominator = ($saldo_b + $saldo_c) / 2;

        if ($denominator != 0) {
            $ratio = ($saldo_a / $denominator) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioR10($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;
        $denominator = ($saldo_b + $saldo_c) / 2;

        if ($denominator != 0) {
            $ratio = ($saldo_a / $denominator) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioR11($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;

        $denominator = ($saldo_b + $saldo_c) / 2;

        if ($denominator != 0) {
            $ratio = ($saldo_a / $denominator) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioR12($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;

        $denominator = ($saldo_b + $saldo_c) / 2;

        if ($denominator != 0) {
            $ratio = ($saldo_a / $denominator) * 100;
        } else {
            $ratio = 0.00;
        }

        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioL2($saldo_a, $saldo_b, $saldo_c)
    {
        $valueReturn = 0.00;

        if ($saldo_c != 0) {
            $ratio = (($saldo_a + $saldo_b) / $saldo_c) * 100;
        } else {
            $ratio = 0.00;
        }
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS5($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;
        if ($saldo_b != 0) {
            $ratio = (($saldo_a / $saldo_b) - 1) * 100;
        } else {
            $ratio = 0.00;
        }
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS7($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        // Prevent division by zero
        if ($saldo_b == 0) {
            return $valueReturn; // or return a specific value like null or a custom message
        }

        $ratio = (($saldo_a / $saldo_b) - 1) * 100;

        // Check if ratio is infinite
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Return ratio if greater than 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS8($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        // Prevent division by zero
        if ($saldo_b == 0) {
            return $valueReturn; // or return a specific value like null or a custom message
        }

        $ratio = (($saldo_a / $saldo_b) - 1) * 100;

        // Check if ratio is infinite
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Return ratio if greater than 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS9($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        // Prevent division by zero
        if ($saldo_b == 0) {
            return $valueReturn; // or return a custom value like null if preferred
        }

        // Calculate the ratio
        $ratio = (($saldo_a / $saldo_b) - 1) * 100;

        // Check if ratio is infinite
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Return ratio if greater than 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS10($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        // Prevent division by zero
        if ($saldo_b == 0) {
            return $valueReturn; // or return a custom value like null if preferred
        }

        // Calculate the ratio
        $ratio = (($saldo_a / $saldo_b) - 1) * 100;

        // Check if ratio is infinite
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Return ratio if greater than 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatioS11($saldo_a, $saldo_b)
    {
        $valueReturn = 0.00;

        // Cek jika saldo_b bernilai 0 untuk menghindari DivisionByZeroError
        if ($saldo_b == 0) {
            return $valueReturn; // Mengembalikan 0 jika saldo_b adalah 0
        }

        // Perhitungan rasio
        $ratio = (($saldo_a / $saldo_b) - 1) * 100;

        // Cek apakah hasilnya infinity
        if (is_infinite($ratio)) {
            $ratio = 0.00;
        }

        // Hanya kembalikan rasio jika lebih besar dari 0
        if ($ratio > 0) {
            $valueReturn = $ratio;
        }

        return $valueReturn;
    }

    public static function getRatio($sandiRekening, $saldo_a, $saldo_b, $saldo_c, $saldo_d, $saldo_e, $saldo_f, $saldo_g, $saldo_h, $saldo_i, $saldo_j)
    {
        $valueReturn = 0.00;

        // Debugging output
        error_log("sandi rekening " . $sandiRekening);

        // Cek nilai sandiRekening dan panggil fungsi yang sesuai
        if ($sandiRekening == "E1") {
            return $valueReturn = self::getRatioE1($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "E2") {
            return $valueReturn = self::getRatioE2($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "E3") {
            return $valueReturn = self::getRatioE3($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "E4") {
            return $valueReturn = self::getRatioE4($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "E5") {
            return $valueReturn = self::getRatioE5($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "E6") {
            return $valueReturn = self::getRatioE6($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "E7") {
            return $valueReturn = self::getRatioE7($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "E8") {
            return $valueReturn = self::getRatioE8($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "A2") {
            return $valueReturn = self::getRatioA2($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "R9") {
            return $valueReturn = self::getRatioR9($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "R10") {
            return $valueReturn = self::getRatioR10($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "R11") {
            return $valueReturn = self::getRatioR11($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "R12") {
            return $valueReturn = self::getRatioR12($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "L2") {
            return $valueReturn = self::getRatioL2($saldo_a, $saldo_b, $saldo_c);
        } elseif ($sandiRekening == "S5") {
            return $valueReturn = self::getRatioS5($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "S7") {
            return $valueReturn = self::getRatioS7($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "S8") {
            return $valueReturn = self::getRatioS8($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "S9") {
            return $valueReturn = self::getRatioS9($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "S10") {
            return $valueReturn = self::getRatioS10($saldo_a, $saldo_b);
        } elseif ($sandiRekening == "S11") {
            return $valueReturn = self::getRatioS11($saldo_a, $saldo_b);
        }

        return $valueReturn;
    }
}
