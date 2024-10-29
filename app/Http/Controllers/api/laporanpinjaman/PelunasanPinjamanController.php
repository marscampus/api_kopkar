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
 * Created on Wed Feb 28 2024 - 09:37:33
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelunasanPinjamanController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cAoAwal = $vaRequestData['AOAwal'];
            $cAoAkhir = $vaRequestData['AOAkhir'];
            $cBerdasarkan = $vaRequestData['Berdasarkan'];
            $vaResult = [];
            $nRow = 0;
            $nTotalPlafond = 0;
            $nTotalSisaProvisi = 0;
            $nTotalSisaAdministrasi = 0;
            $nTotalPokok = 0;
            $nTotalBunga = 0;
            $nTotalPelunasan = 0;
            $cSyarat = "";

            // Hitung Saldo Kemarin
            $vaData = DB::table('debitur as d')
                ->select(
                    'd.Rekening',
                    'd.RekeningLama',
                    'd.Tgl',
                    'r.Nama',
                    'r.Alamat',
                    DB::raw('SUM(a.dpokok - a.kpokok) AS BakiDebet'),
                    'd.Plafond',
                    'Lama',
                    DB::raw('(select MAX(tgl) from angsuran where Rekening = d.rekening and kpokok > 0) as TglLunas'),
                    'd.AO',
                    'o.Nama as NamaAO',
                    'd.GolonganKredit'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                ->leftJoin('angsuran as a', function ($join) use ($dTglAkhir) {
                    $join->on('a.rekening', '=', 'd.rekening')
                        ->where('a.tgl', '<=', $dTglAkhir);
                })
                ->where('d.Tgl', '<=', $dTglAkhir)
                ->whereBetween('d.AO', [$cAoAwal, $cAoAkhir])
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy('d.Rekening')
                ->havingRaw('BakiDebet = 0')
                ->havingRaw("TglLunas >= '$dTglAwal'")
                ->havingRaw("TglLunas <= '$dTglAkhir'")
                ->orderBy('TglLunas')
                ->get();

            $vaArray = []; // Deklarasi $vaArray diluar loop
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $dTglRealisasi = $d->Tgl;
                $dTglLunas = $d->TglLunas;
                $dJthTmp = date('Y-m-d', Date::nextMonth(Func::Tgl2Time($d->Tgl), $d->Lama, 0));
                $nPelunasan = 0;
                $nBunga = 0;
                $bShow = false;
                if ($cBerdasarkan == 'A' && $dJthTmp > $dTglLunas) {
                    $bShow = true;
                    $cKeterangan = 'Belum Jatuh Tempo';
                }
                if ($cBerdasarkan == 'B' && $dJthTmp <= $dTglLunas) {
                    $bShow = true;
                    $cKeterangan = 'Jatuh Tempo';
                }
                if ($cBerdasarkan == 'C') {
                    $bShow = true;
                    $cKeterangan = '';
                }
                if ($bShow) {
                    $nPokok = 0;
                    $nBunga = 0;
                    $vaData2 = DB::table('angsuran')
                        ->selectRaw('SUM(kpokok) as Pokok, SUM(kbunga) as Bunga')
                        ->where('rekening', $cRekening)
                        ->where('tgl', $dTglLunas)
                        ->first();
                    if ($vaData2) {
                        $nPokok = $vaData2->Pokok;
                        $nBunga = $vaData2->Bunga;
                    }
                    $dBOM = Func::BOM($d->TglLunas);
                    $dEOMBulanLalu = date('Y-m-d', Date::nextDay(Func::Tgl2Time($dBOM), -1, 0));
                    $nSisaProvisi = 0;
                    $nSisaAdministrasi = 0;
                    $vaArray[] = [
                        'No' => ++$nRow,
                        'Rekening' => $cRekening,
                        'RekeningLama' => $d->RekeningLama,
                        'Nama' => $d->Nama,
                        'GolPinjaman' => $d->GolonganKredit,
                        'TglLunas' => $dTglLunas,
                        'TglCair' => $dTglRealisasi,
                        'JthTmp' => $dJthTmp,
                        'Plafond' => $d->Plafond,
                        'SisaProvisi' => $nSisaProvisi,
                        'SisaAdministrasi' => $nSisaAdministrasi,
                        'Pelunasan' => $nPokok,
                        'Bunga' => $nBunga,
                        'AO' => $d->AO
                    ];
                    $nTotalPlafond += $d->Plafond;
                    $nTotalSisaProvisi += $nSisaProvisi;
                    $nTotalSisaAdministrasi += $nSisaAdministrasi;
                    $nTotalPokok += $nPokok;
                    $nTotalBunga += $nBunga;
                }
            }
            $vaTotal = [
                'TotalPlafond' => $nTotalPlafond,
                'TotalSisaProvisi' => $nTotalSisaProvisi,
                'TotalSisaAdministrasi' => $nTotalSisaAdministrasi,
                'TotalPokok' => $nTotalPokok,
                'TotalBunga' => $nTotalBunga
            ];
            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'totals' => $vaTotal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Pelunasan Pinjaman', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pelunasan Pinjaman', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
        }
    }


    public function data1(Request $request)
    {
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $row = 0;
        $totalPlafond = 0;
        $totalSisaProvisi = 0;
        $totalSisaAdministrasi = 0;
        $totalPokok = 0;
        $totalBunga = 0;
        $totalPelunasan = 0;
        // Hitung saldo kemarin
        $data = DB::table('debitur as d')
            ->select('d.Rekening', 'd.RekeningLama', 'd.Tgl', 'r.Nama', 'r.Alamat', DB::raw('SUM(a.dpokok - a.kpokok) as BakiDebet'), 'd.Plafond', 'Lama', 'd.AO', 'o.Nama as NamaAO', 'd.GolonganKredit')
            ->selectSub(function ($query) {
                $query->select(DB::raw('MAX(tgl)'))
                    ->from('angsuran')
                    ->whereRaw('Rekening = d.rekening and kpokok > 0');
            }, 'TglLunas')
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.kode')
            ->leftJoin('ao as o', 'o.kode', '=', 'd.ao')
            ->leftJoin('cabang as c', 'c.kode', '=', 'd.cabangentry')
            ->leftJoin('angsuran as a', function ($join) use ($tglAkhir) {
                $join->on('a.rekening', '=', 'd.rekening')
                    ->where('a.tgl', '<=', $tglAkhir);
            })
            ->where('d.tgl', '<=', $tglAkhir)
            ->groupBy('d.Rekening', 'd.RekeningLama', 'd.Tgl', 'r.Nama', 'r.Alamat', 'd.Plafond', 'Lama', 'd.AO', 'o.Nama', 'd.GolonganKredit') // Tambahkan kolom yang diperlukan ke dalam GROUP BY
            ->havingRaw('BakiDebet = 0')
            ->havingRaw('TglLunas >= ?', [$tglAwal])
            ->havingRaw('TglLunas <= ?', [$tglAkhir])
            ->orderBy('TglLunas')
            ->paginate(10);
        $array = [];
        foreach ($data as $d) {
            $rekening = $d->Rekening;
            $tglRealisasi = $d->Tgl;
            $tglLunas = $d->TglLunas;
            $jthtmp = Carbon::parse($tglRealisasi)->addMonths($d->Lama)->format('Y-m-d');
            $pelunasan = 0;
            $bunga = 0;
            $dBOM = Carbon::parse($tglLunas)->startOfMonth();
            $dEOMBulanLalu = $dBOM->subMonth(1)->toDateString();
            $sisaProvisi = 0;
            $sisaAdministrasi = 0;
            $pokok = 0;
            $bunga = 0;
            $golongan = GetterSetter::getGolongan($rekening);
            $array[] = [
                'No' => ++$row,
                'Rekening' => $rekening,
                'RekeningLama' => $d->RekeningLama,
                'Nama' => $d->Nama,
                'GolPinjaman' => $d->GolonganKredit,
                'TglLunas' => $tglLunas,
                'TglCair' => $tglRealisasi,
                'JthTmp' => $jthtmp,
                'Plafond' => $d->Plafond,
                'SisaProvisi' => $sisaProvisi,
                'SisaAdministrasi' => $sisaAdministrasi,
                'Pelunasan' => $pokok,
                'Bunga' => $bunga,
                'AO' => $d->AO
            ];
            $totalPlafond += $d->Plafond;
            $totalSisaProvisi += $sisaProvisi;
            $totalSisaAdministrasi += $sisaAdministrasi;
            $totalPokok += $pokok;
            $totalBunga += $bunga;
        }
        $total[] = [
            'TotalPlafond' => $totalPlafond,
            'TotalSisaProvisi' => $totalSisaProvisi,
            'TotalSisaAdministrasi' => $totalSisaAdministrasi,
            'TotalPelunasan' => $totalPokok,
            'TotalBunga' => $totalBunga
        ];
        $result = [
            'data' => $array,
            'total' => $total
        ];
        return response()->json($result);
    }
}
