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
 * Created on Wed Mar 06 2024 - 02:58:56
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\checklistaccounting;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\GolonganSimpananBerjangka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckListAccountingController extends Controller
{
    public function data1(Request $request)
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
            $dTgl = $vaRequestData['Tgl'];
            $vaArray = [];
            $vaArray[1] = [
                'No' => '1',
                'Keterangan' => 'Simpanan',
                'SaldoNominatif' => 0,
                'SaldoNeraca' => 0,
                'Selisih' => 0
            ];
            $vaData = DB::table('tabungan as t')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('rekening as r', 'r.Kode', '=', 'g.Rekening')
                ->leftJoin('mutasitabungan as m', function ($join) use ($dTgl) {
                    $join->on('m.rekening', '=', 't.Rekening')
                        ->where('m.Tgl', '<=', $dTgl);
                })
                ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
                ->where('t.tgl', '<=', $dTgl)
                ->groupBy('g.kode')
                ->orderBy('g.kode')
                ->select('t.Rekening', 'g.Rekening as RekeningAkuntansi', 'g.Keterangan as NamaRekening', DB::raw('ifnull(sum(m.kredit-m.debet),0) as Saldo'))
                ->get();

            $nKreditTabungan = 0;
            $nDebetTabungan = 0;
            $cCOATabPokok = "";
            $cCOATabWajib = "";
            $vaData2 = DB::table('golongansimpanan')
                ->select('RekeningSimpanan')
                ->orderBy('Kode')
                ->get();
            foreach ($vaData2 as $d2) {
                if ($d2->Kode == '01') {
                    $cCOATabPokok = $d2->RekeningSimpanan;
                }
                if ($d2->Kode == '02') {
                    $cCOATabWajib = $d2->RekeningSimpanan;
                }
            }
            foreach ($vaData as $d) {
                if ($d->Saldo > 0) {
                    $cKey = "T" . $d->RekeningAkutansi;
                    if (!isset($vaArray[$cKey])) {
                        $nTabunganNeraca = GetterSetter::getSaldoCekList($dTgl, $d->RekeningAkutansi, $cCabang, $cJenisGabungan);
                        $vaArray[$cKey] = [
                            'No' => '',
                            'Keterangan' => $d->RekeningAkuntansi . ' - ' . $d->NamaRekening,
                            'SaldoNominatif' => 0,
                            'SaldoNeraca' => $nTabunganNeraca,
                            'Selisih' => 0
                        ];
                        $nKreditTabungan += $nTabunganNeraca;
                    }
                    $vaArray[$cKey]['SaldoNominatif'] += $d->Saldo;
                    $nDebetTabungan += $d->Saldo;
                }
            }

            $vaArray[11] = [
                'No' => '',
                'Keterangan' => "$cCOATabPokok - Simpanan Pokok",
                'SaldoNominatif' => GetterSetter::getSaldoAwal($dTgl, $cCOATabPokok),
                'SaldoNeraca' => GetterSetter::getSaldoCekList($dTgl, $cCOATabPokok, $cCabang, $cJenisGabungan),
                'Selisih' => 0
            ];

            $vaArray[12] = [
                'No' => '',
                'Keterangan' => "$cCOATabWajib - Simpanan Wajib",
                'SaldoNominatif' => GetterSetter::getSaldoAwal($dTgl, $cCOATabWajib),
                'SaldoNeraca' => GetterSetter::getSaldoCekList($dTgl, $cCOATabWajib, $cCabang, $cJenisGabungan),
                'Selisih' => 0
            ];

            $vaArray[29] = [
                'No' => '',
                'Keterangan' => 'TOTAL SIMPANAN',
                'SaldoNominatif' => $nDebetTabungan,
                'SaldoNeraca' => $nKreditTabungan,
                'Selisih' => $nDebetTabungan - $nKreditTabungan
            ];


            // Array Deposito
            $vaArray[2] = [
                'No' => '2',
                'Keterangan' => 'Simpanan Berjangka',
                'SaldoNominatif' => 0,
                'SaldoNeraca' => 0,
                'Selisih' => 0
            ];

            $vaData3 = DB::table('deposito as t')
                ->leftJoin('golongandeposito as g', 'g.Kode', '=', 't.GolonganDeposito')
                ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningAkuntansi')
                ->leftJoin('mutasideposito as m', function ($join) use ($dTgl) {
                    $join->on('m.rekening', '=', 't.Rekening')
                        ->where('m.Tgl', '<=', $dTgl);
                })
                ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
                ->where('t.tgl', '<=', $dTgl)
                ->groupBy('g.kode')
                ->orderBy('g.kode')
                ->select('t.Rekening', 'g.RekeningAkuntansi', DB::raw('IFNULL(SUM(m.SetoranPlafond - m.PencairanPlafond), 0) AS Saldo'), 'g.Keterangan as NamaRekening')
                ->get();
            $nKreditDeposito = 0;
            $nDebetDeposito = 0;
            foreach ($vaData3 as $d3) {
                if ($d3->Saldo > 0) {
                    $cRekening = $d3->Rekening;
                    $cGolongan = GetterSetter::getGolonganDepositoPeriode($cRekening, $dTgl);
                    $vaData4 = DB::table('golongandeposito')
                        ->select('RekeningAkuntansi')
                        ->where('Kode', '=', $cGolongan)
                        ->get();
                    if ($vaData4) {
                        $d3->RekeningAkutansi = $vaData4->RekeningAkutansi;
                    }
                    $cKey = 'D' . $d3->RekeningAkuntansi;

                    if (!isset($vaArray[$cKey])) {
                        $nDepositoNeraca = GetterSetter::getSaldoCekList($dTgl, $d3->RekeningAkutansi, $cCabang, $cJenisGabungan);
                        // $vaArray[$cKey]
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function data(Request $request)
    {
        $dTgl = $request->Tgl;
        $cJenisGabungan = $request->JenisGabungan;
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $request->Cabang;
        }
        $vaArray = [];
        // ARRAY SIMPANAN
        $vaArray[1] = [
            'No' => '1',
            'Keterangan' => 'Simpanan',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data = DB::table('tabungan as t')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('rekening as r', 'r.Kode', '=', 'g.Rekening')
            ->leftJoin('mutasitabungan as m', function ($join) use ($dTgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $dTgl);
            })
            ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
            ->where('t.tgl', '<=', $dTgl)
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.Rekening as RekeningAkuntansi', 'g.Keterangan as NamaRekening', DB::raw('ifnull(sum(m.kredit-m.debet),0) as Saldo'))
            ->get();
        $row = 0;

        // dd($data);

        // return 0;
        $kreditTabungan = 0;
        $debetTabungan = 0;

        $cCOATabPokok = ""; // Anda perlu mendapatkan ini dari logika yang sesuai
        $cCOATabWajib = ""; // Anda perlu mendapatkan ini dari logika yang sesuai
        $golonganSimpanan = DB::table('golongansimpanan')->orderBy('kode')->get();

        foreach ($golonganSimpanan as $golongan) {
            if ($golongan->Kode == '01') {
                $cCOATabPokok = $golongan->RekeningSimpanan;
            }
            if ($golongan->Kode == '02') {
                $cCOATabWajib = $golongan->RekeningSimpanan;
            }
        }

        foreach ($data as $d) {
            if ($d->Saldo > 0) {
                $key = "T" . $d->RekeningAkuntansi;
                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($dTgl, $d->RekeningAkuntansi, $cCabang, $cJenisGabungan);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d->RekeningAkuntansi . ' - ' . $d->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditTabungan += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d->Saldo;
                $debetTabungan += $d->Saldo;
            }
        }

        $vaArray[11] = [
            'No' => '',
            'Keterangan' => "$cCOATabPokok - Simpanan Pokok",
            'SaldoNominatif' => GetterSetter::getSaldoAwal($dTgl, $cCOATabPokok), //$dbR['spokok'],
            'SaldoNeraca' => GetterSetter::getSaldoCekList($dTgl, $cCOATabPokok, $cCabang, $cJenisGabungan), //$nSaldoNeracaSPokok,
            'Selisih' =>  0, //$dbR['spokok'] - $nSaldoNeracaSPokok
        ];

        $vaArray[12] = [
            'No' => '',
            'Keterangan' => "$cCOATabWajib - Simpanan Wajib",
            'SaldoNominatif' =>  GetterSetter::getSaldoAwal($dTgl, $cCOATabWajib), //$dbR['swajib'],
            'SaldoNeraca' => GetterSetter::getSaldoCekList($dTgl, $cCOATabWajib, $cCabang, $cJenisGabungan), //$nSaldoNeracaSWajib,
            'Selisih' =>  0, //$dbR['swajib'] - $nSaldoNeracaSWajib
        ];

        $vaArray[29] = [
            'No' => '',
            'Keterangan' => 'TOTAL SIMPANAN',
            'SaldoNominatif' => $debetTabungan,
            'SaldoNeraca' => $kreditTabungan,
            'Selisih' => $debetTabungan - $kreditTabungan
        ];

        // ARRAY DEPOSITO
        $vaArray[2] = [
            'No' => '2',
            'Keterangan' => 'Simpanan Berjangka',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data2 = DB::table('deposito as t')
            ->leftJoin('golongandeposito as g', 'g.Kode', '=', 't.GolonganDeposito')
            ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningAkuntansi')
            ->leftJoin('mutasideposito as m', function ($join) use ($dTgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $dTgl);
            })
            ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
            ->where('t.tgl', '<=', $dTgl)
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.RekeningAkuntansi', DB::raw('IFNULL(SUM(m.SetoranPlafond - m.PencairanPlafond), 0) AS Saldo'), 'g.Keterangan as NamaRekening')
            ->get();
        $row = 0;
        $kreditDeposito = 0;
        $debetDeposito = 0;
        foreach ($data2 as $d2) {
            if ($d2->Saldo > 0) {
                $rekening = $d2->Rekening;
                $golongan = GetterSetter::getGolonganDepositoPeriode($rekening, $dTgl);
                $data3 = GolonganSimpananBerjangka::where('Kode', $golongan)->first();
                if ($data3) {
                    $d2->RekeningAkuntansi = $data3->RekeningAkuntansi;
                }
                $key = 'D' . $d2->RekeningAkuntansi;

                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($dTgl, $d2->RekeningAkuntansi, $cCabang, $cJenisGabungan);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d2->RekeningAkuntansi . ' - ' . $d2->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditDeposito += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d2->Saldo;
                $debetDeposito += $d2->Saldo;
            }
        }
        $vaArray[30] = [
            'No' => '',
            'Keterangan' => 'TOTAL SIMPANAN BERJANGKA',
            'SaldoNominatif' => $debetDeposito,
            'SaldoNeraca' => $kreditDeposito,
            'Selisih' => $debetDeposito - $kreditDeposito
        ];

        // ARRAY KREDIT
        $debetKredit = 0;
        $kreditKredit = 0;
        $vaArray[3] = [
            'No' => '3',
            'Keterangan' => 'Pinjaman',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data4 = DB::table('debitur as t')
            ->leftJoin('rekening as r', 'r.Kode', '=', DB::raw('IFNULL((SELECT GolonganKredit_Baru FROM debitur_golongankredit WHERE Rekening = t.Rekening AND tgl <= "' . $dTgl . '" ORDER BY tgl DESC LIMIT 1), t.GolonganKredit)'))
            ->leftJoin('debitur_cabang as dc', 'dc.Rekening', '=', 't.Rekening')
            ->leftJoin('cabang as c', 'c.Kode', '=', DB::raw('IFNULL((SELECT CabangEntry FROM debitur_cabang WHERE Rekening = t.Rekening AND tgl <= "' . $dTgl . '" ORDER BY tgl DESC LIMIT 1), t.CabangEntry)'))
            ->leftJoin('angsuran as m', function ($join) use ($dTgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $dTgl)
                    ->where('t.tglwriteoff', '>', $dTgl);
            })
            ->leftJoin('golongankredit as g', 'g.Kode', '=', DB::raw('IFNULL((SELECT GolonganKredit_Baru FROM debitur_golongankredit WHERE Rekening = t.Rekening AND tgl <= "' . $dTgl . '" ORDER BY tgl DESC LIMIT 1), t.GolonganKredit)'))
            ->where('t.tgl', '<=', $dTgl)
            ->where('t.offbalance', '=', 0)
            ->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            )
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.Rekening as RekeningAkuntansi', 'g.Keterangan as NamaRekening', DB::raw('IFNULL(SUM(m.DPokok - m.KPokok), 0) AS Saldo'))
            ->get();
        $row = 0;
        foreach ($data4 as $d4) {
            if ($d4->Saldo > 0) {
                $key = 'K' . $d4->RekeningAkuntansi;
                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($dTgl, $d4->RekeningAkuntansi, $cCabang, $cJenisGabungan);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d4->RekeningAkuntansi . ' - ' . $d4->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditKredit += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d4->Saldo;
                $debetKredit += $d4->Saldo;
            }
        }
        $vaArray[31] = [
            'No' => '',
            'Keterangan' => 'TOTAL KREDIT',
            'SaldoNominatif' => $debetKredit,
            'SaldoNeraca' => $kreditKredit,
            'Selisih' => $debetKredit - $kreditKredit
        ];

        // ARRAY LAIN LAIN
        $vaArray[4] = [
            'No' => '4',
            'Keterangan' => 'Lain-Lain',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $PBLeadger = 0;
        $PBNeraca = GetterSetter::getSaldoCekList($dTgl, GetterSetter::getDBConfig('msRekeningPB'), $cCabang, $cJenisGabungan);
        $vaArray['PB'] = [
            'No' => '',
            'Keterangan' => '4.1 Pemindah Bukuan',
            'SaldoNominatif' => $PBLeadger,
            'SaldoNeraca' => $PBNeraca,
            'Selisih' => $PBLeadger - $PBNeraca
        ];
        foreach ($vaArray as $key => $value) {
            $vaArray[$key]['Selisih'] = $value['SaldoNominatif'] - $value['SaldoNeraca'];
            $vaArray[$key]['SaldoNominatif'] = $vaArray[$key]['SaldoNominatif'];
            $vaArray[$key]['SaldoNeraca'] = $vaArray[$key]['SaldoNeraca'];
            $vaArray[$key]['Selisih'] = $vaArray[$key]['Selisih'];
        }

        $responseArray = [
            'data' => $vaArray
        ];

        // return $responseArray;


        $transformedData = [];
        foreach ($vaArray as $key => $value) {
            $transformedData[] = [
                'No' => $value['No'],
                'Keterangan' => $value['Keterangan'],
                'SaldoNominatif' => $value['SaldoNominatif'],
                'SaldoNeraca' => $value['SaldoNeraca'],
                'Selisih' => $value['Selisih'],
            ];
        }

        $responseArray = [
            'data' => $transformedData
        ];

        return $responseArray;
    }
}
