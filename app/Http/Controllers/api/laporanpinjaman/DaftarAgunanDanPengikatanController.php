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
 * Created on Tue Feb 20 2024 - 03:24:16
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Agunan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarAgunanDanPengikatanController extends Controller
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
            $cJaminan = $vaRequestData['Jaminan'];
            $cStatusDebitur = $vaRequestData['StatusDebitur'];
            if ($cStatusDebitur == 'AKTIF') {
                $cHavingBakiDebet = "BakiDebet > 0";
            } elseif ($cStatusDebitur == 'LUNAS') {
                $cHavingBakiDebet = "BakiDebet <= 0";
            } else {
                $cHavingBakiDebet = "BakiDebet >= 0";
            }
            $vaResult = [];
            $nRow = 0;
            $nTotalJaminan = 0;
            $vaData = DB::table('debitur as d')
                ->select(
                    'r.Nama',
                    'd.Plafond',
                    'd.Rekening as RekeningKredit',
                    'd.AO',
                    'd.RekeningLama',
                    'd.Tgl as TglRealisasi',
                    'a.ID',
                    'a.Kode',
                    'a.No',
                    'a.Tgl',
                    'a.Rekening',
                    'a.S_JenisPengikatan',
                    'a.M_Warna',
                    'a.NilaiJaminan',
                    DB::raw('sum(ag.dpokok - ag.kpokok) as BakiDebet'),
                    'd.TglAmbilJaminan'
                )
                ->leftJoin('angsuran as ag', function ($join) use ($dTglAkhir) {
                    $join->on('ag.rekening', '=', 'd.rekening')
                        ->where('ag.tgl', '<=', $dTglAkhir);
                })
                ->leftJoin('agunan as a', 'a.rekening', '=', 'd.rekeningjaminan')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                // ->leftJoin('cabang as c', function ($join) use ($dTglAkhir) {
                //     $join->on('c.Kode', '=', DB::raw("(select CabangEntry from debitur_cabang where Rekening = d.Rekening and tgl <= '$dTglAkhir' order by tgl desc limit 1 )"))
                //     ->orWhere('c.Kode', '=', 'd.CabangEntry');
                // })
                ->whereBetween('d.Tgl', [$dTglAwal, $dTglAkhir])
                ->where('a.Jaminan', '=', $cJaminan)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy('d.Rekening')
                ->havingRaw($cHavingBakiDebet)
                ->get();
            foreach ($vaData as $d) {
                $cDetailKeteranganJaminan = "";
                $cStatusJaminan = "MASIH DI KOPERASI";
                $cRekening = $d->RekeningKredit;
                $vaData2 = DB::table('agunan as a')
                    ->select(
                        'a.Rekening as Rekening',
                        'a.No',
                        'a.Jaminan',
                        'a.TglAmbil',
                        'd.TglAmbilJaminan'
                    )
                    ->leftJoin('debitur as d', 'd.RekeningJaminan', '=', 'a.Rekening')
                    ->where('d.Rekening', '=', $cRekening)
                    ->orderBy('a.No')
                    ->get();
                foreach ($vaData2 as $d2) {
                    if ($d->TglAmbilJaminan <> '9999-99-99') {
                        $cStatusJaminan = "SUDAH DIAMBIL PADA TANGGAL " . date('d-m-Y', strtotime($d2->TglAmbilJaminan));
                    }
                    $vaDetail = GetterSetter::getDetailJaminan($d2->Rekening, $d2->No, $d2->Jaminan);
                    $cDetailJaminan = "";
                    foreach ($vaDetail as $k => $va) {
                        foreach ($va as $key => $value) {
                            if (!empty($value)) {
                                $cKey = $key . " : " . $value;
                                $key = trim($key);
                                if (empty($key)) {
                                    $cKey = $value;
                                }
                                $cDetailJaminan .= $cKey . ", ";
                            }
                        }
                    }
                    $cDetailJaminan = substr($cDetailJaminan, 0, -2) . ". ";
                    $cDetailKeteranganJaminan .= $d2->No . ". " . $cDetailJaminan;
                    $vaDetailJaminan[$d2->No] = [
                        'No' => $d2->No,
                        'Isi' => $cDetailJaminan
                    ];
                }
                $vaArray['data'][] = [
                    'No' => ++$nRow,
                    'Tgl' => date('d-m-Y', strtotime($d->TglRealisasi)),
                    'RekKredit' => $d->RekeningKredit,
                    'NamaNasabah' => $d->Nama,
                    'Plafond' => $d->Plafond,
                    'BakiDebet' => $d->BakiDebet,
                    'NoJaminan' => "'" . $d->Rekening,
                    'Nilai' => $d->NilaiJaminan,
                    'AO' => $d->AO,
                    'StatusJaminan' => $cStatusJaminan,
                    'KeteranganJaminan' => $cDetailJaminan
                ];
                $nTotalJaminan += $d->NilaiJaminan;
            }
            $vaResult = [
                'data' => $vaArray['data'],
                'total_data' => count($vaArray['data']),
                'total' => $nTotalJaminan
            ];
            return response($vaResult);
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

            Func::writeLog('Daftar Agunan dan Pengikatan', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
