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
 * Created on Thu Dec 28 2023 - 13:36:25
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NominatifSimpananController extends Controller
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
            $dTgl = $vaRequestData['Tgl'];

            $vaData = DB::table('tabungan as t')
                ->select(
                    'r.Kode',
                    't.Rekening',
                    't.RekeningLama',
                    't.GolonganTabungan',
                    'g.Keterangan as NamaGolonganTabungan',
                    't.GolonganNasabah',
                    't.Tgl',
                    'r.Kelamin',
                    'r.Alamat',
                    't.AO',
                    'o.Nama as NamaAO',
                    'r.Kodya',
                    't.KelompokTabungan',
                    DB::raw('GROUP_CONCAT(r.Nama) as Nama'),
                    DB::raw('SUM(m.kredit - m.debet) as SaldoAkhir'),
                    't.Keterkaitan',
                    'g.Keterangan as NamaGolongan',
                    'o.Nama as NamaAO2'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('ao as o', 'o.Kode', '=', 't.ao')
                ->leftJoin('mutasitabungan as m', function ($join) use ($dTgl) {
                    $join->on('m.rekening', '=', 't.Rekening')
                        ->where('m.Tgl', '<=', $dTgl);
                })
                ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
                ->where('t.Keterkaitan', '>=', '')
                ->where('t.Keterkaitan', '<=', '2')
                ->where('t.GolonganTabungan', '>=', $cGolAwal)
                ->where('t.GolonganTabungan', '<=', $cGolAkhir)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy(
                    't.Rekening'
                )
                ->orderBy('t.GolonganTabungan')
                ->having('SaldoAkhir', '<>', 0)
                ->having('SaldoAkhir', '>=', 0)
                ->having('SaldoAkhir', '<=', 9999999999)
                ->get();

            $nRow = 0;
            $nTotalSaldoAkhir = 0;
            $cNama = '';
            $vaResults = [];
            foreach ($vaData as $d) {
                $nRow++;
                $cRekening = $d->Rekening;
                $nSaldo = PerhitunganTabungan::getSaldoTabungan($cRekening, $dTgl);
                $cGolTabungan = $d->GolonganTabungan;
                $cKetGolTabungan = $d->NamaGolonganTabungan;
                $vaData2 = DB::table('tabungan as t')
                    ->select('r.Nama')
                    ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                    ->where('Rekening', '=', $cRekening)
                    ->first();
                if ($vaData2) {
                    $cNama = $vaData2->Nama;
                }
                $vaResult[] = [
                    'No' => $nRow,
                    'Tgl' => $d->Tgl,
                    'NoCIF' => $d->Kode,
                    'GolTabungan' => $cGolTabungan . ' - ' . $cKetGolTabungan,
                    'RekeningLama' => $d->RekeningLama,
                    'Rekening' => $cRekening,
                    'Nama' => $cNama,
                    'Alamat' => $d->Alamat,
                    'WilayahAO' => $d->KelompokTabungan,
                    'AO' => $d->AO,
                    'Keterkaitan' => $d->Keterkaitan,
                    'SaldoAkhir' => $nSaldo,
                ];
                $nTotalSaldoAkhir += $nSaldo;
            }
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'totals' => $nTotalSaldoAkhir
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Nominatif Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Nominatif Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }
}
