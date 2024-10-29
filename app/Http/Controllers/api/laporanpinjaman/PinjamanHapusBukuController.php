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
 * Created on Thu Feb 29 2024 - 07:37:08
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PinjamanHapusBukuController extends Controller
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
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $nRow = 0;
            $vaData = DB::table('debitur as d')
                ->select(
                    'd.FakturWriteOff',
                    'd.Rekening',
                    'd.RekeningLama',
                    'd.GolonganKredit',
                    'r.Nama',
                    'r.Alamat',
                    'd.TglWriteOff',
                    'g.Keterangan as NamaGolongan',
                    'c.Keterangan as NamaCabang',
                    'd.AO',
                    'o.Nama as NamaAO'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('golongankredit as g', 'g.Kode', '=', 'd.GolonganKredit')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->where('d.TglWriteOff', '<=', $dTglAkhir)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->orderBy('d.TglWriteOff')
                ->orderBy('d.FakturWriteOff')
                ->orderBy('d.Rekening')
                ->get();
            $nTotalBakiDebet = 0;
            $nTotalBakiDebetWO = 0;
            $vaArray = [];
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $dTglWO = $d->TglWriteOff;
                $nBakiDebetWO = GetterSetter::getBakiDebet($cRekening, $dTglWO);
                $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTglAkhir);
                $cFaktur = $d->FakturWriteOff;
                $cNama = $d->Nama;
                $cAlamat = $d->Alamat;
                $cAO = $d->AO;
                $cGolongan = $d->GolonganKredit;
                $cKetGolongan = $d->NamaGolongan;
                if ($nBakiDebet > 0) {
                    $vaArray[] = [
                        'No' => ++$nRow,
                        'Rekening' => $cRekening,
                        'GolPinjaman' => $cGolongan . ' - ' . $cKetGolongan,
                        'Nama' => $cNama,
                        'Alamat' => $cAlamat,
                        'TglWo' => $dTglWO,
                        'Faktur' => $cFaktur,
                        'BakiDebetWO' => $nBakiDebet,
                        'BakiDebet' => $nBakiDebet,
                        'AO' => $cAO
                    ];
                }

                $nTotalBakiDebetWO += $nBakiDebetWO;
                $nTotalBakiDebet += $nBakiDebet;

                $vaTotal = [
                    'TotalBakiDebetWO' => $nTotalBakiDebetWO,
                    'TotalBakiDebet' => $nTotalBakiDebet,
                ];
            }
            $vaResults = [
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'totals' => $vaTotal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Mutasi Simpanan Harian', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function data1(Request $request)
    {
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $data =
            DB::table('angsuran as a')
            ->select(
                'a.Faktur',
                'a.TGL',
                'd.Rekening',
                'd.RekeningLama',
                'd.GolonganKredit',
                'a.KPokok',
                'a.KBunga',
                'a.Denda',
                'r.Nama',
                DB::raw('g.Keterangan as NamaGolongan'),
                'a.CabangEntry',
                DB::raw('c.Keterangan as NamaCabang'),
                'd.AO',
                DB::raw('o.Nama as NamaAo'),
                'a.UserName'
            )
            ->leftJoin('debitur as d', 'd.Rekening', '=', 'a.Rekening')
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongankredit as g', 'g.Kode', '=', 'd.GolonganKredit')
            ->leftJoin('cabang as c', 'c.Kode', '=', 'a.CabangEntry')
            ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
            ->where('a.Faktur', 'NOT LIKE', 'R%')
            ->whereBetween('a.TGL', [$tglAwal, $tglAkhir])
            ->whereColumn('a.TGL', '>', 'd.TglWriteOff')
            // ->where('d.TglWriteOff', '<=', $tglAkhir)
            // ->orderBy('a.TGL')
            // ->orderBy('a.Faktur')
            ->orderBy('d.GolonganKredit')
            ->paginate(10);

        $row = 0;
        $previousGolongan = null;
        $totalPokok = 0;
        $totalBakiDebet = 0;
        $totalBunga = 0;
        $totalDenda = 0;
        $totalAngsuran = 0;
        $vaArray = [];
        foreach ($data as $d) {
            $rekening = $d->Rekening;
            $rekeningLama = $d->RekeningLama;
            $pokok = $d->KPokok;
            $bunga = $d->KBunga;
            $denda = $d->Denda;
            $angsuran = $pokok + $bunga + $denda;
            $tgl = $d->TGL;
            $bakiDebet = GetterSetter::getBakiDebet($rekening, $tgl);
            $faktur = $d->Faktur;
            $nama = $d->Nama;
            $AO = $d->AO;
            $golKredit = $d->GolonganKredit;
            $ketGolonganKredit = $d->NamaGolongan;
            // Periksa apakah golongan berbeda dengan golongan sebelumnya
            if ($golKredit !== $previousGolongan) {
                $previousGolongan = $golKredit;
                $row = 1; // Jika berbeda, atur 'No' kembali ke 1
            } else {
                $row++; // Jika sama, tambahkan 'No' sebelumnya
            }
            if (!isset($vaArray[$golKredit])) {
                $vaArray[$golKredit] = [
                    'GolKredit' => $golKredit,
                    'KetKredit' => $ketGolonganKredit,
                    'Data' => [],
                ];
            }
            $vaArray[$golKredit]['Data'][] = [
                'No' => $row,
                'Rekening' => $rekening,
                'UserName' => $d->UserName,
                'Nama' => $nama,
                'Tgl' => $tgl,
                'Faktur' => $faktur,
                'Pokok' => $pokok,
                'Bunga' => $bunga,
                'Denda' => $denda,
                'Jumlah' => $angsuran,
                'BakiDebet' => $bakiDebet,
                'AO' => $AO
            ];

            $totalPokok += $pokok;
            $totalBunga += $bunga;
            $totalDenda += $denda;
            $totalAngsuran += $angsuran;
            $totalBakiDebet += $bakiDebet;
        }
        $newVaArray = [];
        foreach ($vaArray as $key => $value) {
            $newRow = [
                'No' =>  '',
                'Rekening' => '',
                'UserName' => '',
                'Nama' => '',
                'Tgl' => '',
                'Faktur' => "{$key} - {$value['KetKredit']}",
                'Pokok' => '',
                'Bunga' => '',
                'Denda' => '',
                'Jumlah' => '',
                'BakiDebet' => '',
                'AO' => ''
            ];
            $newVaArray[] = $newRow;

            foreach ($value['Data'] as $dataRow) {
                $newVaArray[] = [
                    'No' =>  $dataRow['No'],
                    'Rekening' => $dataRow['Rekening'],
                    'UserName' => $dataRow['UserName'],
                    'Nama' => $dataRow['Nama'],
                    'Tgl' => $dataRow['Tgl'],
                    'Faktur' => $dataRow['Faktur'],
                    'Pokok' => $dataRow['Pokok'],
                    'Bunga' => $dataRow['Bunga'],
                    'Denda' => $dataRow['Denda'],
                    'Jumlah' => $dataRow['Jumlah'],
                    'BakiDebet' => $dataRow['BakiDebet'],
                    'AO' => $dataRow['AO']
                ];
            }
            $totalArray = [
                'No' => 'Total',
                'Rekening' => '',
                'UserName' => '',
                'Nama' => '',
                'Tgl' => '',
                'Faktur' => '',
                'Pokok' => $totalPokok,
                'Bunga' => $totalBunga,
                'Denda' => $totalDenda,
                'Jumlah' => $totalAngsuran,
                'BakiDebet' => $totalBakiDebet,
                'AO' => ''
            ];
            $newVaArray[] = $totalArray;

            return response()->json($newVaArray);
        }
    }
}
