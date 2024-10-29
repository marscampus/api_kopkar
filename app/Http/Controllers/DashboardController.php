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
 * Created on Thu Dec 14 2023 - 13:46:20
 * Author : Salsabila Emma | salsabila17emma@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers;

use App\Helpers\GetterSetter;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\fun\Username;
use App\Models\teller\MutasiAnggota;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function insertUser(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cEmail = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        $vaData = DB::table('username')
            ->where('UserName', '=', $cEmail)
            ->exists();
        if (!$vaData) {
            $vaArray = [
                "UserName" => $cEmail,
                "Tgl" => GetterSetter::getTglTransaksi(),
                "Aktif" => 1,
                "FullName" => $cUser
            ];
            Username::create($vaArray);
        } else {
            $vaArray = [
                "UserName" => $cEmail,
                "Tgl" => GetterSetter::getTglTransaksi(),
                "Aktif" => 1,
                "FullName" => $cUser
            ];
            Username::where('UserName', '=', $cEmail)->update($vaArray);
        }
    }

    public function getJenisGabungan(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cEmail = $vaRequestData['auth']['email'];
        $cJenisGabungan = [];
        unset($vaRequestData['auth']);
        $vaData = DB::table('username')
            ->select("Gabungan")
            ->where('UserName', '=', $cEmail)
            ->first();
        if ($vaData) {
            $cGabungan = $vaData->Gabungan;
            switch ($cGabungan) {
                case 0:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor']
                    ];
                    break;
                case 1:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor'],
                        ['name' => 'B', 'label' => 'Cabang Induk']
                    ];
                    break;
                case 2:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor'],
                        ['name' => 'B', 'label' => 'Cabang Induk'],
                        ['name' => 'C', 'label' => 'Konsolidasi']
                    ];
                    break;
                default:
                    break;
            }
        }
        return response()->json(['fields' => $cJenisGabungan]);
    }

    public function countEntities($table)
    {
        try {
            $count = DB::table($table)->count();
            return response()->json($count);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function countAnggota()
    {
        return $this->countEntities('registernasabah');
    }

    public function countSimpanan()
    {
        // return $this->countEntities('tabungan');
        try {
            $vaData = DB::table('tabungan as t')
                ->leftJoin('mutasitabungan as m', 't.rekening', '=', 'm.rekening')
                ->whereRaw('(COALESCE(m.debet, 0) - COALESCE(m.kredit, 0)) > 0')
                ->distinct()
                ->count('t.rekening');

            return response()->json($vaData);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function countSimpananBerjangka()
    {
        return $this->countEntities('deposito');
        // try {
        //     $nSimpananBerjangka = DB::table('deposito as t')
        //     ->leftJoin('mutasideposito as m', 't.rekening', '=', 'm.rekening')
        //     ->whereRaw('(COALESCE(m.kredit, 0) - COALESCE(m.debet, 0)) > 0')
        //     ->distinct()
        //     ->count('t.rekening');

        //     return response()->json($nSimpananBerjangka);
        // } catch (\Throwable $th) {
        //     return response()->json(['status' => 'error']);
        // }
    }

    public function countPinjaman()
    {
        return $this->countEntities('debitur');
    }

    // -----------------------------------------------------------------------
    public function saldoKas()
    {
        //
    }

    public function aset()
    {
        $dTgl = Carbon::now();
        try {
            $nSaldoAset = DB::table('bukubesar as b')
            ->selectRaw('COALESCE(SUM(b.kredit - b.debet), 0) as Saldo')
                ->where('b.tgl', '<=', $dTgl)
                ->where('b.rekening', 'like', '1%')
                ->first();
            // dd($nSaldoAset);
            return response()->json($nSaldoAset->Saldo);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function pendapatan()
    {
        $dTgl = Carbon::now();
        try {
            $nSaldoPendapatan = DB::table('bukubesar as b')
                ->selectRaw('COALESCE(SUM(b.kredit - b.debet), 0) as Saldo')
                ->where('b.tgl', '<=', $dTgl)
                ->where('b.rekening', 'like', '4%')
                ->first();
            return response()->json($nSaldoPendapatan->Saldo);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function biaya()
    {
        $dTgl = Carbon::now();
        // dd($dTgl);
        try {
            $nSaldoBiaya = DB::table('bukubesar as b')
            ->selectRaw('COALESCE(SUM(b.kredit - b.debet), 0) as Saldo')
                ->where('b.tgl', '<=', $dTgl)
                ->where('b.rekening', 'like', '5%')
                ->first();
            // dd($nSaldoBiaya);
            return response()->json($nSaldoBiaya->Saldo);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    // -----------------------------------------------------------------------
    public function grafikNasabah()
    {
        try {
            $nJumlahNasabah = DB::table('registernasabah')
                ->selectRaw('YEAR(Tgl) as tahun, MONTH(Tgl) as bulan, COUNT(*) as jumlahNasabah')
                ->whereYear('Tgl', Carbon::now()->year)
                ->groupBy('tahun', 'bulan')
                ->get();
            // dd($nasabahPerBulan);
            return response()->json($nJumlahNasabah);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function grafikAsetAwal()
    {
        $endOfMonth = Carbon::now()->endOfMonth();
        try {
            $nSaldoAset = DB::table('bukubesar as b')
                ->selectRaw('SUM(b.debet - b.kredit) as Saldo')
                ->where('b.tgl', '<=', $endOfMonth)
                ->where('b.rekening', 'like', '1%')
                ->first();
            // dd($nSaldoAset);
            return response()->json($nSaldoAset->Saldo);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
    public function grafikAset()
    {
        try {
            $nSaldoAsetPerBulan = DB::table('bukubesar as b')
                ->selectRaw('YEAR(b.tgl) as tahun, MONTH(b.tgl) as bulan, SUM(b.debet - b.kredit) as Saldo')
                ->where('b.rekening', 'like', '1%')
                ->whereYear('b.tgl', Carbon::now()->year)
                ->groupBy('tahun', 'bulan')
                ->get();

            return response()->json($nSaldoAsetPerBulan);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
