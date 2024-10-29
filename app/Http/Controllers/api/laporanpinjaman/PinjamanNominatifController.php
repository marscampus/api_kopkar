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
 * Created on Thu Feb 29 2024 - 07:36:58
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganPinjaman;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Debitur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PinjamanNominatifController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $cRekening = $vaRequestData['Rekening'];
            $vaData = DB::table('debitur as d')
                ->select(
                    'd.Rekening',
                    'r.Nama'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->where('d.Rekening', '=', $cRekening)
                ->first();
            if ($vaData) {
                $vaResult = [
                    'Nama' => $vaData->Nama
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Pinjaman Nominatif', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pinjaman Nominatif', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function data(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaRequestData = json_decode(json_encode($request->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        $dTgl = Func::Date2String($vaRequestData['Tgl']);
        $cType = $vaRequestData['Tipe'];
        $cRekening = $vaRequestData['Rekening'];
        $cPeriode = date('Ym', strtotime($dTgl));
        $dAwal = Func::Date2String(Func::BOM($dTgl));
        $dAwal = date('Y-m-d', Date::nextDay(Func::Tgl2Time($dAwal), -1));
        $dAkhir = Func::Date2String(Func::EOM($dTgl));
        $cJenisGabungan = $vaRequestData['JenisGabungan'];
        $cCabang = null;
         if ($cJenisGabungan !== "C") {
            $cCabang = $vaRequestData['Cabang'];
        }
        $cStatusDebitur = $vaRequestData['StatusDebitur'];
        if ($cStatusDebitur == 'AKTIF') {
            $cHavingBakiDebet = "BakiDebet > 0";
        } elseif ($cStatusDebitur == 'LUNAS') {
            $cHavingBakiDebet = "BakiDebet = 0";
        } else {
            $cHavingBakiDebet = "BakiDebet >= 0";
        }
        $cHavingBakiDebet = "BakiDebet > 0 OR d.Tgl > '$dAwal'";
        // $cWhereRekening = '';
        // if (!empty($cRekening)) {
        //     $cWhereRekening = "d.Rekening = '$cRekening'";
        // }
        try {
            $vaData = DB::table('debitur as d')
                ->select(
                    'r.Kode as KodeCIF',
                    'r.KTP',
                    'r.Kelamin',
                    'r.Pekerjaan',
                    'r.PekerjaanInduk',
                    'r.TglLahir',
                    'r.KodyaKeterangan',
                    'r.KecamatanKeterangan',
                    'r.KelurahanKeterangan',
                    'd.Rekening',
                    'd.RekeningLama',
                    'd.PeriodePembayaran',
                    'd.RekeningTabungan',
                    'd.Tgl',
                    'r.Nama',
                    'r.Alamat',
                    'd.SifatKredit',
                    'r.RT',
                    'r.RW',
                    'd.GolonganPenjamin',
                    'd.BagianYangDiJamin',
                    'd.SektorEkonomi',
                    'd.GolonganDebitur',
                    'd.JenisPenggunaan',
                    'j.Keterangan as NamaJenisPenggunaan',
                    'd.TujuanPenggunaan',
                    'd.Lama',
                    'd.Wilayah',
                    'w.SandiBI',
                    'd.Keterkaitan',
                    'd.PeriodePembayaran',
                    'd.SumberDanaPelunasan',
                    'd.CaraPerhitungan',
                    'd.NoSPK',
                    'd.AO',
                    'd.AOTagih',
                    'd.WilayahAO',
                    'g.Kode as GolonganKredit',
                    'g.Keterangan as NamaGolongan',
                    'r.Telepon',
                    'i.Keterangan as NamaInstansi',
                    'd.NoPengajuan',
                    's.Keterangan as KetEkonomi',
                    'd.KodeAsuransi',
                    'asr.Keterangan as NamaAsuransi',
                    'd.GolonganDebitur',
                    'gd.Keterangan as NamaGolonganDebitur',
                    'd.SektorEkonomi',
                    'se.Keterangan as KeteranganSektorEkonomi',
                    'c.Kode as CabangEntry',
                    'd.Administrasi',
                    'd.Provisi',
                    'd.TglAmbilJaminan',
                    'd.Tgl as TglRealisasi',
                    'JenisPinjaman',
                    'SumberOrder',
                    'd.RekeningJaminan',
                    DB::raw("IFNULL((SELECT SUM(dpokok-kpokok) FROM angsuran ag WHERE ag.TGL <= '$dAwal' AND ag.rekening = d.rekening),0) AS BakiDebet"),
                    DB::raw("IFNULL((SELECT MAX(tgl) FROM angsuran ag WHERE ag.TGL <= '$dTgl' and ag.kpokok > 0 AND ag.rekening = d.rekening),'9999-99-99') AS TglLunas")
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('wilayah as w', 'w.Kode', '=', 'd.Wilayah')
                ->leftJoin('angsuran as a', 'a.Rekening', '=', 'd.Rekening')
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->leftJoin('asuransi as asr', 'asr.Kode', '=', 'd.KodeAsuransi')
                ->leftJoin('golongandebitur as gd', 'gd.Kode', '=', 'd.GolonganDebitur')
                ->leftJoin('golongankredit as g', function ($join) use ($dTgl) {
                    $join->on('g.Kode', '=', DB::raw("ifnull((select GolonganKredit_Baru from debitur_golongankredit where Rekening = d.Rekening and tgl <= '$dTgl' order by tgl desc limit 1), d.GolonganKredit)"));
                })
                ->leftJoin('pengajuankredit as p', 'p.Rekening', '=', 'd.NoPengajuan')
                ->leftJoin('instansi as i', 'i.Kode', '=', 'd.Instansi')
                ->leftJoin('cabang as c', function ($join) use ($dTgl) {
                    $join->on('c.Kode', '=', DB::raw("IFNULL((select CabangEntry from debitur_cabang where Rekening = d.Rekening and tgl <= '$dTgl' order by tgl desc limit 1), d.CabangEntry)"));
                })
                ->leftJoin('sektorekonomi as s', 's.Kode', '=', 'd.SektorEkonomi')
                ->leftJoin('slik_sektorekonomi as se', 'se.Kode', '=', 'd.SektorEkonomiOjk')
                ->leftJoin('jenispenggunaan as j', 'j.Kode', '=', 'd.JenisPenggunaan')
                ->where('d.StatusPencairan', '=', '1')
                ->where('d.Tgl', '<=', $dTgl)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->when(
                    !empty($cRekening), // Kondisi
                    function ($query) use ($cRekening) {
                        $query->where('d.Rekening', $cRekening);
                    }
                )
                // ->whereRaw($cWhereRekening)
                ->groupBy('d.Rekening')
                ->havingRaw($cHavingBakiDebet)
                ->orderBy('d.GolonganKredit')
                ->orderBy('d.Rekening')
                ->get();
            $nRow = 0;
            $nMaxRow = 0;
            $cStatusJaminan = '';
            $cDetailJaminan = '';
            $nKewajibanBunga = 0;
            $nKewajibanBungaSetelahLunas = 0;
            $nKewajibanDendaSetelahLunas = 0;
            $vaResult = [];
            $vaArray = [];
            foreach ($vaData as $d) {
                $nMaxRow++;
                $nRow++;
                $cRekening = $d->Rekening;
                $vaRealisasi = GetterSetter::getAdendum($cRekening, $dTgl);
                $dTglRealisasi = $vaRealisasi['Tgl'];
                $cCaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
                $nLama = $vaRealisasi['Lama'];
                $nSukuBunga = $vaRealisasi['SukuBunga'];
                $nPlafond = $vaRealisasi['Plafond'];
                $dJthTmp = date('Y-m-d', Date::nextMonth(Func::Tgl2Time($dTglRealisasi), $nLama));
                $vaPembayaran = GetterSetter::getTotalPembayaranKredit($cRekening, $dTgl);
                $nPembayaranPokok = $vaPembayaran['PembayaranPokok'];
                $nPembayaranBunga = $vaPembayaran['PembayaranBunga'];
                $vaT = GetterSetter::getTunggakan($cRekening, $dTgl);
                $nDenda = $vaT['Denda_Akhir'];
                $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTgl);
                $nSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($d->RekeningTabungan, $dTgl);
                $cRekeningTabungan = trim($d->RekeningTabungan);
                if (empty($cRekeningTabungan)) {
                    $nSaldoTabungan = 0;
                }
                $nKe = 1;
                $nPokok = GetterSetter::getAngsuranPokok($cRekening, $nKe);
                $nBunga = GetterSetter::getAngsuranBunga($cRekening, $nKe, $nBakiDebet);
                $nTotalAngsuran = $nPokok + $nBunga;

                $nPokokManual = GetterSetter::modAngsuran($nPlafond / $nLama);
                $nBungaManual = GetterSetter::modAngsuran($nPlafond * $nSukuBunga / 12 / 100);
                $nTotalAngsuranManual = $nPokokManual + $nBungaManual;

                $cStatusJaminan = 'MASIH DI KOPERASI';
                if ($d->TglAmbilJaminan != '9999-99-99') {
                    $cStatusJaminan = "SUDAH DIAMBIL PADA TANGGAL " . $d->TglAmbilJaminan;
                }
                $vaCaraPerhitungan = GetterSetter::getPerhitunganBunga();
                $cNamaCaraPerhitungan = $vaCaraPerhitungan[$cCaraPerhitungan];
                $cJaminan = '';
                $cRekJaminan = $d->RekeningJaminan ?? '';
                $dTglKeluarMasukJaminan = "";
                $vaData2 = DB::table('agunan as a')
                    ->select(
                        'Jaminan',
                        'Tgl'
                    )
                    ->where('Rekening', '=', $cRekJaminan)
                    ->orderByDesc('NilaiJaminan')
                    ->first();
                if ($vaData2) {
                    $cJaminan = $vaData2->Jaminan;
                    $dTglKeluarMasukJaminan = $vaData2->Tgl;
                }
                $cJaminan = GetterSetter::getKeterangan($cJaminan, 'Keterangan', 'jaminan');
                $nFR = max($vaT['FR_P_Awal'], $vaT['FR_B_Awal']);
                $nKe = GetterSetter::getKe($dTglRealisasi, $dTgl, $nLama);
                $nAngsuranKe = max(0, $nKe - $nFR);
                $nAngsPokok = GetterSetter::getAngsuranPokok($cRekening, $nKe);
                $nAngsBunga = GetterSetter::getAngsuranBunga($cRekening, $nKe, $nBakiDebet);
                if ($nAngsPokok > $nBakiDebet) {
                    $nAngsPokok = $nBakiDebet;
                }
                $nTotalAngsuran = $nAngsPokok + $nAngsBunga;

                $vaDetailJaminan = [];
                $cDetailKetJaminan = "";
                $vaData3 = DB::table('agunan as a')
                    ->select(
                        'a.Rekening',
                        'a.No',
                        'a.Jaminan',
                        'a.TglAmbil'
                    )
                    ->leftJoin('debitur as d', 'd.RekeningJaminan', '=', 'a.Rekening')
                    ->where('d.Rekening', '=', $cRekening)
                    ->orderBy('a.No')
                    ->get();
                foreach ($vaData3 as $d3) {
                    $vaDetail = GetterSetter::getDetailJaminan($d3->Rekening, $d3->No, $d3->Jaminan, $dTgl);
                    foreach ($vaDetail as $l => $va) {
                        foreach ($va as $key => $value) {
                            if (!empty($value)) {
                                $key = $key . '  : ' . $value;
                                $key = trim($key);
                                if (empty($key)) $key = $value;
                                $cDetailJaminan .= $key . ", ";
                            }
                        }
                    }
                    $cDetailJaminan = substr($cDetailJaminan, 0, -2) . ". ";
                    $cDetailKetJaminan .= $d3->No . '. ' . $cDetailJaminan;
                    $vaDetailJaminan[$d3->No] = [
                        'No' => $d3->No,
                        'Isi' => $cDetailJaminan
                    ];
                }
                $cKetJenisJaminan = '';
                $cKetJenisPengikatan = '';
                $vaTab = [];
                $vaTab = [
                    'jaminan' => $vaT['Jaminan'],
                    'jenispengikatanjaminan' => $vaT['JenisPengikatan']
                ];
                foreach ($vaTab as $keyTab => $valueTab) {
                    $vaData4 = DB::table($keyTab)
                        ->select('Keterangan')
                        ->where('Kode', '=', $valueTab)
                        ->first();
                    if ($vaData4) {
                        if ($keyTab === 'jaminan') {
                            $cKetJenisJaminan = $vaData4->Keterangan;
                        }
                        if ($keyTab === 'jenispengikatanjaminan') {
                            $cKetJenisJaminan = $vaData4->Keterangan;
                        }
                    }
                    $nNilaiAngunan = $vaT['NilaiYangDiPerhitungkan'];
                    $cStatusDebitur = '';
                    if ($nBakiDebet == 0) {
                        $nKewajibanBungaSetelahLunas = GetterSetter::getKewajibanBunga($cRekening, $dTgl);
                        $nKewajibanDendaSetelahLunas = $nDenda;
                        $cStatusDebitur = "LUNAS";
                        $dTglLunas = $d->TglLunas;
                        if ($dTglLunas >= $dJthTmp) {
                            $cStatusDebitur = "LUNAS JATUH TEMPO";
                        }
                        if ($dTglLunas < $dJthTmp) {
                            $cStatusDebitur = "LUNAS SEBELUM JATUH TEMPO";
                        }
                    }
                    if ($nBakiDebet > 0) {
                        $cStatusDebitur = "AKTIF";
                    }

                    $dTanggalBayar = '';
                    $nBayarPokok = 0;
                    $nBayarBunga = 0;
                    $nBayarDenda = 0;
                    $cFakturBayar = '';
                    $vaData5 = DB::table('angsuran')
                        ->select(
                            DB::raw('max(tgl) as TglBayar'),
                            DB::raw('sum(kpokok) as Pokok'),
                            DB::raw('sum(kbunga) as Bunga'),
                            DB::raw('sum(denda) as Denda'),
                            DB::raw('max(faktur) as FakturBayar')
                        )
                        ->where('Rekening', '=', $cRekening)
                        ->where('Tgl', '>', $dAwal)
                        ->where('Tgl', '<=', $dTgl)
                        ->first();
                    if ($vaData5) {
                        $dTanggalBayar = $vaData5->TglBayar;
                        $nBayarPokok = $vaData5->Pokok;
                        $nBayarBunga = $vaData5->Bunga;
                        $nBayarDenda = $vaData5->Denda;
                        $cFakturBayar = $vaData5->FakturBayar;
                    }
                    $cJenisNPL = '';
                    $nKolAwal = $vaT['Kol_Awal'];
                    if ($nKolAwal <= 2) {
                        $cJenisNPL = "NON NPL";
                    }
                    if ($nKolAwal > 2) {
                        $cJenisNPL = "NPL";
                    }
                    if ($nKolAwal == 2) {
                        $nSelisihAwal = $vaT['Hari_T_Awal'] - date('d', strtotime($dAkhir));
                        if ($nSelisihAwal <= 90) {
                            $cJenisNPL = "CALON NPL";
                        }
                    }
                    $cNamaAO = $d->AO;
                    $vaData6 = DB::table('debitur_ao as da')
                        ->select(DB::raw('IFNULL(o.Kode, \'\') as AOBaru'))
                        ->leftJoin('ao as o', 'o.Kode', '=', 'da.AO_Baru')
                        ->where('da.Rekening', '=', $cRekening)
                        ->orderBy('da.ID', 'desc')
                        ->first();
                    if ($vaData6) {
                        $cAOBaru = $vaData6->AOBaru;
                    }

                    if (empty($d->AOTagih)) {
                        $d->AOTagih = $cNamaAO;
                    }

                    $dTglBayarFix = '';
                    $vaData7 = DB::table('angsuran')
                        ->select(
                            DB::raw('max(tgl) as TglBayar'),
                            DB::raw('sum(kpokok) as Pokok'),
                            DB::raw('sum(kbunga) as Bunga'),
                            DB::raw('sum(denda) as Denda'),
                            DB::raw('max(faktur) as FakturBayar')
                        )
                        ->where('Rekening', '=', $cRekening)
                        ->where('Tgl', '<=', $dTgl)
                        ->where('Status', '=', '5')
                        ->first();
                    if ($vaData7) {
                        $dTglBayarFix = $vaData7->TglBayar;
                    }

                    // JATUH TEMPO ANGSURAN
                    $dTglJTAngs = "0000-00-00";
                    $nHariJtAngs = 0;
                    $nAngsTerakhirBayar = 0;
                    for ($i = 1; $i <= $nLama; $i++) {
                        $dTglJadwal = date('Y-m-d', Date::nextMonth(strtotime($dTglRealisasi), $i));
                        $nPokok = GetterSetter::getAngsuranPokok($cRekening, $i);
                        $nBunga = GetterSetter::getAngsuranBunga($cRekening, $i, $nBakiDebet);
                        if ($cCaraPerhitungan == '3' && $i >= $nLama) {
                            $nPokok = GetterSetter::getBakiDebet($cRekening, $dTglJadwal);
                        }
                        $dTglAwalJadwal = Func::Date2String(Func::BOM($dTglJadwal));
                        $dTglAkhirJadwal = Func::Date2String(Func::EOM($dTglJadwal));
                        $vaData8 = DB::table('angsuran')
                            ->select(
                                DB::raw('IFNULL(sum(KPokok), 0) as BayarPokok'),
                                DB::raw('IFNULL(sum(KBunga), 0) as BayarBunga'),
                                'Tgl'
                            )
                            ->where('Tgl', '>=', $dTglAwalJadwal)
                            ->where('Tgl', '<=', $dTglAkhirJadwal)
                            ->where('Rekening', '=', $cRekening)
                            ->where('Status', '=', '5')
                            ->groupBy('Tgl')
                            ->first();
                        if ($vaData8) {
                            if (($vaData8->BayarPokok + $vaData8->BayarBunga) >= ($nPokok + $nBunga)) {
                                $dTglJTAngs = date('Y-m-d', Date::nextMonth(strtotime($dTglJadwal), 1));
                                $nAngsTerakhirBayar++;
                            }
                        }
                        if ($dTgl >= $dTglJadwal) {
                            $nHariJtAngs = substr($dTglJadwal, -2);
                        }
                    }
                    $nBahanTagihPokok = 0;
                    $nBahanTagihBunga = 0;
                    $nAngsPokokBahanTagih = GetterSetter::getAngsuranPokok($cRekening, $nKe + 1);
                    $nAngsBungaBahanTagih = GetterSetter::getAngsuranBunga($cRekening, $nKe + 1, $nBakiDebet);
                    if ($nKe > 0) {
                        $nBahanTagihPokok = $nAngsPokokBahanTagih;
                        $nBahanTagihBunga = $nAngsBungaBahanTagih;
                    }
                    if ($vaT['T_Pokok_Akhir'] > 0) {
                        $nBahanTagihPokok = $vaT['T_Pokok_Akhir'];
                    }
                    if ($vaT['T_Bunga_Akhir'] > 0) {
                        $nBahanTagihBunga = $vaT['T_Bunga_Akhir'];
                    }
                    $nTotBahanTagih = $nBahanTagihPokok + $nBahanTagihBunga;
                }
                if ($cType === 'T') {
                    $vaArray[] = [
                        'No' => $nRow,
                        'RekLama' => $d->RekeningLama,
                        'Rekening' => $cRekening,
                        'Nama' => $d->Nama,
                        'JthTmp' => date('d-m-Y', strtotime($dJthTmp)),
                        'Plafond' => $nPlafond,
                        'Angsuran' => $nTotalAngsuran,
                        'RekTab' => $d->RekeningTabungan,
                        'SaldoTab' => $nSaldoTabungan,
                        'AO' => $d->AO,
                        'TPokok' => $vaT['T_Pokok_Awal'],
                        'TBunga' => $vaT['T_Bunga_Awal'],
                        'BakiDebet' => $vaT['Baki_Debet_Akhir']
                    ];
                } else if ($cType === 'P') {
                    $vaArray[] = [
                        'NO' => $nRow,
                        'CABANG' => $d->CabangEntry,
                        'NO. ANGGOTA' => $d->KodeCIF,
                        'REK LAMA' => "'" . $d->RekeningLama,
                        'REKENING' => $cRekening,
                        'PPU' => $d->NoSPK,
                        'KTP' => "'" . $d->KTP,
                        'NAMA' => $d->Nama,
                        'JENIS KELAMIN' => $d->Kelamin,
                        'ALAMAT' => $d->Alamat,
                        'RT' => $d->RT,
                        'RW' => $d->RW,
                        'KELURAHAN' => $d->KelurahanKeterangan,
                        'KECAMATAN' => $d->KecamatanKeterangan,
                        'KODYA' => $d->KodyaKeterangan,
                        'TELEPON' => $d->Telepon,
                        'TAHUN BULAN CAIR' => date('m-Y', strtotime($dTglRealisasi)),
                        'TGL CAIR' => date('d', strtotime($dTglRealisasi)),
                        'TGL REALISASI' => date('d-m-Y', strtotime($dTglRealisasi)),
                        'JTH TMP' => date('d-m-Y', strtotime($dJthTmp)),
                        'TGL JTH TMP ANGSURAN' => $dTglJTAngs,
                        'TGL FIX ANGSURAN' => $dTglBayarFix,
                        'CARA PERHITUNGAN' => $cNamaCaraPerhitungan,
                        'NAMA GOLONGAN' => $d->NamaGolongan,
                        'LAMA' => $nLama,
                        'SUKU BUNGA' => $nSukuBunga,
                        'ADMINISTRASI' => round($d->Administrasi),
                        'PROVISI' => round($d->Provisi),
                        'PLAFOND' => round($nPlafond),
                        'ANGSURAN' => round($nTotalAngsuran),
                        'ANGSURAN POKOK' => round($nAngsPokok),
                        'ANGSURAN BUNGA' => round($nAngsBunga),
                        'BAHAN TAGIH POKOK' => round($nBahanTagihPokok),
                        'BAHAN TAGIH BUNGA' => round($nBahanTagihBunga),
                        'TOTAL BAHAN TAGIH' => round($nTotBahanTagih),
                        'KOL BAHAN' => round($vaT['Kol_Awal']),
                        'BAKI DEBET AWAL' => round($vaT['Baki_Debet_Awal']),
                        'T POKOK AWAL' => round($vaT['T_Pokok_Awal']),
                        'T BUNGA AWAL' => round($vaT['T_Bunga_Awal']),
                        'TUNGGAKAN AWAL' => round($vaT['Tunggakan_Awal']),
                        'DENDA AWAL' => round($vaT['Denda_Awal']),
                        'FR P AWAL' => round($vaT['FR_P_Awal']),
                        'FR B AWAL' => round($vaT['FR_B_Awal']),
                        'FR AWAL' => round($vaT['FR_Awal']),
                        'HARI T AWAL' => round($vaT['Hari_T_Awal']),
                        'HARI P AWAL' => round($vaT['Hari_P_Awal']),
                        'HARI B AWAL' => round($vaT['Hari_B_Awal']),
                        'KOL' => round($vaT['Kol_Akhir']),
                        'BAKI DEBET BERJALAN' => round($nBakiDebet),
                        'BAKI DEBET AKHIR' => round($vaT['Baki_Debet_Akhir']),
                        'T POKOK AKHIR' => round($vaT['T_Pokok_Akhir']),
                        'T BUNGA AKHIR' => round($vaT['T_Bunga_Akhir']),
                        'TUNGGAKAN AKHIR' => round($vaT['Tunggakan_Akhir']),
                        'DENDA AKHIR' => round($vaT['Denda_Akhir']),
                        'FR P AKHIR' => round($vaT['FR_P_Akhir']),
                        'HARI T AKHIR' => round($vaT['Hari_T_Akhir']),
                        'HARI P AKHIR' => round($vaT['Hari_P_Akhir']),
                        'HARI B AKHIR' => round($vaT['Hari_B_Akhir']),
                        'KOL AKHIR' => round($vaT['Kol_Akhir']),
                        'REKENING SIMPANAN' => $d->RekeningTabungan,
                        'SALDO SIMPANAN' => round($nSaldoTabungan),
                        'AO' => $cNamaAO,
                        'GOLONGAN PINJAMAN' => $d->GolonganKredit,
                        'JAMINAN' => $cJaminan,
                        'ANGSURAN SEHARUSNYA BULAN INI' => round($nKe),
                        'ANGSURAN TERAKHIR BAYAR' => $nAngsTerakhirBayar,
                        'JMLH ANGSURAN TERTUNGGAK' => round($nKe) - round($nAngsuranKe),
                        'OVER DUE HARI INI' => date('d') - $nHariJtAngs,
                        'NILAI JAMINAN' => round($nNilaiAngunan),
                        'JENIS JAMINAN' => $vaT['JenisPengikatan'],
                        'KETERANGAN JENIS JAMINAN' => $cKetJenisJaminan,
                        'KETERANGAN JAMINAN' => $cDetailJaminan,
                        'NAMA ASURANSI' => $d->KodeAsuransi . ' - ' . $d->NamaAsuransi,
                        'PEKERJAAN' => $d->PekerjaanInduk,
                        'SUB PEKERJAAN' => $d->Pekerjaan,
                        'JENIS PENGGUNAAN' => $d->NamaJenisPenggunaan,
                        'TUJUAN PENGGUNAAN' => $d->TujuanPenggunaan,
                        'TANGGAL BAYAR' => $dTanggalBayar,
                        'BUKTI FAKTUR BAYAR' => $cFakturBayar,
                        'BAYAR POKOK' => round($nBayarPokok),
                        'BAYAR BUNGA' => round($nBayarBunga),
                        'BAYAR DENDA' => round($nBayarDenda),
                        'STATUS DEBITUR' => $cStatusDebitur,
                        'STATUS JAMINAN' => $cStatusJaminan,
                        'TGL KELUAR MASUK JAMINAN' => $dTglKeluarMasukJaminan,
                        'KEWAJIBAN BUNGA SETELAH LUNAS' => round($nKewajibanBungaSetelahLunas),
                        'KEWAJIBAN DENDA SETELAH LUNAS' => round($nKewajibanDendaSetelahLunas),
                        'T POKOK' => round($vaT['T_Pokok_Akhir']),
                        'T BUNGA' => round($vaT['T_Bunga_Akhir']),
                        'JENIS ORDER' => $d->JenisPinjaman,
                        'SUMBER ORDER' => $d->SumberOrder,
                        'TANGGAL LAHIR' => $d->TglLahir,
                        'TAHUN BULAN LAHIR' => date('d-m-Y', strtotime($d->TglLahir)),
                        'TGL LAHIR' => date('d', strtotime($d->TglLahir)),
                        'COLLECTION' => $d->AOTagih
                    ];
                }
            }
            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaArray)
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

            Func::writeLog('Pinjaman Nominatif', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
