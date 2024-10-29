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
 * Created on Mon Feb 05 2024 - 04:35:00
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\Func\Date as FuncDate;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Func\Date;

class JadwalAngsuranController extends Controller
{
    public function getRekening(Request $request)
    {
        $rekening = $request->Rekening;
        $data = DB::table('debitur as d')
            ->select('r.Nama', 'r.Alamat', 'r.RTRW', 'r.Kodya', 'r.Kecamatan', 'r.Kelurahan', 'd.Tgl')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->where('d.rekening', '=', $rekening)
            ->first();
        $array = [];
        if ($data) {
            $vaJaminan = GetterSetter::GetJaminanNasabah($rekening);
            $nama = $data->Nama;
            $ketJaminan = $vaJaminan['NamaJaminan'];
            $kodya = Func::SeekDaerah($data->Kodya);
            $kecamatan = Func::SeekDaerah($data->Kodya . $data->Kecamatan);
            $kelurahan = Func::SeekDaerah($data->Kodya . $data->Kecamatan . $data->Kelurahan);
            // $alamat = $data->Alamat . ' RT/RW ' . $data->RTRW . ' ' . $kelurahan . ' ' . $kecamatan . $kelurahan . ' ' . $kodya;
            $array = [
                'Nama' => $nama,
                'Alamat' => $data->Alamat,
                'TglPK' => $data->Tgl,
                'KeteranganJaminan' => $ketJaminan
            ];
        }
        return response()->json($array);
    }

    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $cRekening = $vaRequestData['Rekening'];
            $cDetail = $vaRequestData['Detail'];
            $cTypeAngsuran = $vaRequestData['TipeAngsuran'];
            $cType = $vaRequestData['Tipe'];
            $dTgl = $vaRequestData['Tgl'];
            $vaKolek = ['Lancar', 'DPK', 'Kurang Lancar', 'Diragukan', 'Macet'];
            $vaResult = [];
            if ($cTypeAngsuran === 'J') {
                $vaData = DB::table('debitur as d')
                    ->select(
                        'd.GolonganKredit',
                        'd.GracePeriod',
                        'd.CaraPerhitungan',
                        's.Keterangan as KeteranganSE',
                        'o.Nama as NamaAO',
                        'r.NamaPasangan',
                        'r.NoBerkas',
                        'i.Keterangan as KeteranganInstansi',
                        'r.Nama',
                        'r.Alamat',
                        'd.SukuBunga',
                        'd.Plafond',
                        'd.Lama',
                        'd.NoSPK'
                    )
                    ->leftJoin('sektorekonomi as s', 's.Kode', '=', 'd.SektorEkonomi')
                    ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                    ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                    ->leftJoin('instansi as i', 'i.Kode', '=', 'd.Instansi')
                    ->where('Rekening', '=', $cRekening)
                    ->first();
                if ($vaData) {
                    $dTgl = Func::Date2String(GetterSetter::getTglTransaksi());
                    $vaRealisasi = GetterSetter::getAdendum($cRekening, $dTgl);
                    $vaData->Tgl = $vaRealisasi['Tgl'];
                    $dTglAdendum = $vaRealisasi['TglAdendum'];
                    $vaData->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
                    $vaData->Lama = $vaRealisasi['Lama'];
                    $vaData->SukuBunga = $vaRealisasi['SukuBunga'];
                    $vaData->Plafond = $vaRealisasi['Plafond'];
                    if ($vaData->CaraPerhitungan == "1") {
                        $cCaraPerhitungan = "Flat";
                    } else if ($vaData->CaraPerhitungan == "3") {
                        $cCaraPerhitungan = "Reguler";
                    } else if ($vaData->CaraPerhitungan == "5") {
                        $cCaraPerhitungan = "Sliding";
                    } else if ($vaData->CaraPerhitungan == "6") {
                        $cCaraPerhitungan = "Anuitas (Flat)";
                    } else if ($vaData->CaraPerhitungan == "7") {
                        $cCaraPerhitungan = "Faktor";
                    } else if ($vaData->CaraPerhitungan == "10") {
                        $cCaraPerhitungan = "Anuitas (Efektif)";
                    } else if ($vaData->CaraPerhitungan == "11") {
                        $cCaraPerhitungan = "Rekening Koran";
                    }
                    $vaJaminan = GetterSetter::getJaminanNasabah($cRekening);
                    $dTglAwal = date('Y-m-d', Date::nextMonth(strtotime($vaData->Tgl), 1));
                    $vaTglJadwal = explode('-', $dTglAwal);
                    $dHariJadwal = $vaTglJadwal[2];
                    $dBulanJadwal = $vaTglJadwal[1];
                    $dTahunJadwal = $vaTglJadwal[0];

                    $dTglSelesai = date('Y-m-d', Date::nextMonth(strtotime($vaData->Tgl), $vaData->Lama));
                    $vaTglSelesai = explode('-', $dTglSelesai);
                    $dBulanSelesai = $vaTglSelesai[1];
                    $dTahunSelesai = $vaTglSelesai[0];
                    $dTglSelesai = date('d-m-Y', mktime(0, 0, 0, $dBulanSelesai, $dHariJadwal, $dTahunSelesai));
                    $vaResult['data'] = [
                        'Rekening' => $cRekening,
                        'Nama' => $vaData->Nama,
                        'Alamat' => $vaData->Alamat,
                        'NoSPK' => $vaData->NoSPK,
                        'TglRealisasi' => $vaData->Tgl,
                        'Sektor' => GetterSetter::getKeterangan($vaData->GolonganKredit, 'Keterangan', 'golongankredit'),
                        'AO' => $vaData->NamaAO,
                        'Plafond' => $vaData->Plafond,
                        'Bunga' => $vaData->SukuBunga . ' %/Thn',
                        'JangkaWaktu' => $vaData->Lama . ' Bulan',
                        'TglMulai' => $dTglAwal,
                        'TglSelesai' => $dTglSelesai,
                        'CaraPerhitungan' => $cCaraPerhitungan
                    ];

                    $vaData2 = DB::table('debitur as d')
                        ->select(
                            'd.CaraPerhitungan',
                            'd.Rekening',
                            'r.Nama',
                            'r.Alamat',
                            'd.Tgl as TglRealisasi',
                            'd.Plafond',
                            'd.Lama',
                            'd.SukuBunga',
                            'd.GracePeriod',
                            'r.NamaPasangan',
                            'd.StatusPencairan',
                            'd.NoSPK'
                        )
                        ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                        ->where('d.Rekening', '=', $cRekening)
                        ->first();
                    if ($vaData2) {
                        $vaRealisasi = GetterSetter::getAdendum($cRekening, $dTgl);
                        $vaData2->TglRealisasi = $vaRealisasi['Tgl'];
                        $dTglAdendum = $vaRealisasi['TglAdendum'];
                        $vaData2->CaraPerhitungan = $vaRealisasi['CaraPerhitungan'];
                        $vaData2->Lama = $vaRealisasi['Lama'];
                        $vaData2->SukuBunga = $vaRealisasi['SukuBunga'];
                        $vaData2->Plafond = $vaRealisasi['Plafond'];
                        $vaSukuBunga = GetterSetter::getDebiturSukuBunga($cRekening, $dTgl);
                        $dTglBungaDebitur = $vaSukuBunga['TglBungaDebitur'];
                        $cStatusPencairan = $vaData2->StatusPencairan;
                        $nLama = $vaData2->Lama;
                        $nPlafond = $vaData2->Plafond;
                        $dTglAwal = $vaData2->TglRealisasi;
                        $nRow = 0;
                        $nBakiDebet = $nPlafond;
                        $nTotalJadwalBunga = $nPlafond * $vaData2->SukuBunga / 100 / 12 * $nLama;
                        $nBakiBunga = $nTotalJadwalBunga;
                        $nTotalPokok = 0;
                        $nTotalBunga = 0;
                        $nTotalJumlah = 0;
                        $nTotalBakiDebet = 0;
                        $nTotalBakiBunga = 0;
                        $nKe = 0;
                        for ($n = 1; $n <= $nLama; $n++) {
                            $nKe = $n;
                            $dTgl = date('Y-m-d', Date::nextMonth(strtotime($dTglAwal), $n, 0));
                            $nPokok = GetterSetter::getAngsuranPokok($cRekening, $nKe);
                            $nBunga = GetterSetter::getAngsuranBunga($cRekening, $nKe, $nBakiDebet);
                            $nBakiDebet -= $nPokok;
                            $nBakiBunga -= $nBunga;
                            $nTotalBakiDebet += $nBakiDebet;
                            $nTotalBakiBunga += $nBakiBunga;
                            $nTotalPokok += $nPokok;
                            $nTotalBunga += $nBunga;
                            $nJumlah = $nBunga + $nPokok;
                            $nTotalJumlah += $nJumlah;
                            // Untuk Tabel
                            if ($cType == 'T') {
                                if ($cDetail == 'Y') {
                                    $vaResult['header'] = [
                                        "KE"  => '',
                                        "TGL ANGSURAN" => '',
                                        "JUMLAH"  => '',
                                        "POKOK" => '',
                                        "BUNGA" => '',
                                        "DENDA" => '',
                                        "SALDO" => $nPlafond == 0 ? '' : Func::getZFormat($nPlafond),
                                        "PARAF"  => ''
                                    ];

                                    $vaResult['detail'][] = [
                                        'KE' => ++$nRow,
                                        'TGL ANGSURAN' => date('d-m-Y', strtotime($dTgl)),
                                        'JUMLAH' => $nJumlah == 0 ? '' : Func::getZFormat($nJumlah),
                                        'POKOK' => $nPokok == 0 ? '' : Func::getZFormat($nPokok),
                                        'BUNGA' => $nBunga == 0 ? '' : Func::getZFormat($nBunga),
                                        'DENDA' => '',
                                        'SALDO' => $nBakiDebet == 0 ? '' : Func::getZFormat($nBakiDebet),
                                        'PARAF' => ''
                                    ];

                                    $vaResult['jumlah'] = [
                                        'KE' => '',
                                        'TGL ANGSURAN' => '',
                                        'JUMLAH' => $nTotalJumlah == 0 ? '' : Func::getZFormat($nTotalJumlah),
                                        'POKOK' => $nTotalPokok == 0 ? '' : Func::getZFormat($nTotalPokok),
                                        'BUNGA' => $nTotalBunga == 0 ? '' : Func::getZFormat($nTotalBunga),
                                        'DENDA' => '',
                                        'SALDO' => '',
                                        'PARAF'  => ''
                                    ];
                                } else {
                                    $vaResult['header'] = [
                                        "KE"  => '',
                                        "TGL ANGSURAN" => '',
                                        "JUMLAH" => '',
                                        "JANJI POKOK" => $nPlafond == 0 ? '' : Func::getZFormat($nPlafond),
                                        "JANJI JASA" => $nTotalJadwalBunga == 0 ? '' : Func::getZFormat($nTotalJadwalBunga)
                                    ];

                                    $vaResult['detail'][] = [
                                        "KE"  => ++$nRow,
                                        "TGL ANGSURAN" => date('d-m-Y', strtotime($dTgl)),
                                        "JUMLAH" => $nJumlah == 0 ? '' : Func::getZFormat($nJumlah),
                                        "JANJI POKOK" => $nBakiDebet == 0 ? '' : Func::getZFormat($nBakiDebet),
                                        "JANJI JASA" => $nBakiBunga == 0 ? '' : Func::getZFormat($nBakiBunga)
                                    ];

                                    $vaArray['jumlah'] = [
                                        'KE' => '',
                                        'TGL ANGSURAN' => '',
                                        'JUMLAH' => $nTotalJumlah == 0 ? '' : Func::getZFormat($nTotalJumlah),
                                        'JANJI POKOK' => $nTotalBakiDebet == 0 ? '' : Func::getZFormat($nTotalBakiDebet),
                                        'JANJI JASA' => $nTotalBakiBunga == 0 ? '' : Func::getZFormat($nTotalBakiBunga),
                                    ];
                                }
                                // Untuk PDF
                            } else if ($cType == 'P') {
                                if ($cDetail == 'Y') {
                                    $vaResult['header'] = [
                                        "Ke"  => '',
                                        "TglAngsuran" => '',
                                        "Jumlah" => '',
                                        "Pokok" => '',
                                        "Bunga" => '',
                                        "Denda" => '',
                                        "Saldo" => $nPlafond,
                                        "Paraf" => ''
                                    ];

                                    $vaResult['detail'][] = [
                                        'Ke' => ++$nRow,
                                        'TglAngsuran' => date('d-m-Y', strtotime($dTgl)),
                                        'Jumlah' => $nJumlah,
                                        'Pokok' => $nPokok,
                                        'Bunga' => $nBunga,
                                        'Denda' => 0,
                                        'Saldo' => $nBakiDebet,
                                        'Paraf' => ''
                                    ];

                                    $vaResult['jumlah'] = [
                                        'Ke' => '',
                                        'TglAngsuran' => '',
                                        'Jumlah' => $nTotalJumlah,
                                        'Pokok' => $nTotalPokok,
                                        'Bunga' => $nTotalBunga,
                                        'Denda' => '',
                                        'Saldo' => '',
                                        'Paraf'  => ''
                                    ];
                                } else {
                                    $vaResult['header'] = [
                                        "Ke"  => '',
                                        "TglAngsuran" => '',
                                        "Jumlah" => '',
                                        "JanjiPokok" => $nPlafond,
                                        "JanjiJasa" => $nTotalJadwalBunga
                                    ];

                                    $vaResult['detail'][] = [
                                        "Ke"  => ++$nRow,
                                        "TglAngsuran" => date('d-m-Y', strtotime($dTgl)),
                                        "Jumlah" => $nJumlah,
                                        "JanjiPokok" => $nBakiDebet,
                                        "JanjiJasa" => $nBakiBunga
                                    ];

                                    $vaResult['jumlah'] = [
                                        'Ke' => '',
                                        'TglAngsuran' => '',
                                        'Jumlah' => $nTotalJumlah,
                                        'JanjiPokok' => $nTotalBakiDebet,
                                        'JanjiJasa' => $nTotalBakiBunga,
                                    ];
                                }
                            }
                        }
                    }
                }
            } else if ($cTypeAngsuran === 'K') {
                $vaData  = DB::table('debitur as d')
                    ->leftJoin('angsuran as a', function ($join) use ($dTgl) {
                        $join->on('a.Rekening', '=', 'd.Rekening')
                            ->where('a.Tgl', '<=', $dTgl);
                    })
                    ->where('d.rekening', $cRekening)
                    ->select(
                        'd.Plafond',
                        'd.Kode',
                        'd.Lama',
                        'd.NoSpk',
                        'd.SukuBunga',
                        'd.Tgl',
                        'd.CaraPerhitungan',
                        DB::raw('IFNULL(SUM(a.kpokok), 0) as PembayaranPokok'),
                        DB::raw('IFNULL(SUM(a.kbunga), 0) as PembayaranBunga'),
                        DB::raw('IFNULL(SUM(a.krra), 0) as RRA')
                    )
                    ->groupBy(
                        'd.Rekening'
                    )
                    ->first();
                if ($vaData) {
                    $dTglRealisasi = $vaData->Tgl;
                    $vaPembayaranKredit = GetterSetter::getTotalPembayaranKredit($cRekening, $dTgl);
                    $nPembayaranPokok = $vaPembayaranKredit['PembayaranPokok'];
                    $nPembayaranBunga = $vaPembayaranKredit['PembayaranPokok'] + $vaData->RRA;
                    $vaKol = GetterSetter::getTunggakan($cRekening, $dTgl);
                    $dJthTmp = Date::nextMonth(strtotime(Func::Date2String($vaData->Tgl), $vaData->Lama), 0);
                    $vaData2
                        = DB::table('agunan AS a')
                        ->leftJoin('pengajuankredit AS r', 'r.Jaminan', '=', 'a.Rekening')
                        ->leftJoin('debitur AS d', 'd.NoPengajuan', '=', 'r.Rekening')
                        ->select('a.Rekening', 'a.No', 'a.Jaminan')
                        ->where('d.Rekening', '=', $cRekening)
                        ->get();
                    foreach ($vaData2 as $d2) {
                        $va = GetterSetter::getDetailJaminan($d2->Rekening, $d2->No, $d2->Jaminan, $dTgl);
                        $cDetailJaminan = "";
                        foreach ($va as $k => $v) {
                            foreach ($v as $key => $value) {
                                $cKeterangan = $key . " : " . $value . ", ";
                                if ($value == "") {
                                    $cKeterangan = "";
                                }
                                $cDetailJaminan .= $cKeterangan;
                            }
                        }
                        $cDetailJaminan = substr($cDetailJaminan, 8);
                        $vaDetail[$d2->No] = array("Judul" => $cDetailJaminan);
                    }
                    $nBunga = round($vaData->Plafond * $vaData->SukuBunga / 100 / 12 * $vaData->Lama, 0);

                    $vaResult['data'] = [
                        'NoRekening' => $cRekening,
                        'NamaDebitur' => GetterSetter::getKeterangan($vaData->Kode, 'Nama', 'registernasabah'),
                        'Alamat' => GetterSetter::getKeterangan($vaData->Kode, 'Alamat', 'registernasabah'),
                        'NoSPK' => $vaData->NoSpk,
                        'Plafond' => $vaData->Plafond,
                        'TglRealisasi' => $vaData->Tgl,
                        'LamaAngsuran' => $vaData->Lama,
                        'JatuhTempo' => date('d-m-Y', $dJthTmp),
                        'SukuBunga' => $vaData->SukuBunga . '% / Tahun',
                    ];
                }
                $nTBunga = "";
                $cFaktur = "";
                $vaData3
                    = DB::table('debitur AS d')
                    ->select('d.Rekening', 'd.Faktur', 'd.Lama')
                    ->where('Rekening', $cRekening)
                    ->first();
                if ($vaData3) {
                    $nTBunga = GetterSetter::getAngsuranBunga($vaData3->Rekening, 1) * $vaData->Lama;
                    $cFaktur = $vaData3->Faktur;
                }
                $vaData4 = DB::table('angsuran as a')
                    ->leftJoin('debitur as d', 'd.rekening', '=', 'a.rekening')
                    ->select(
                        'a.Rekening',
                        'a.Faktur',
                        'a.Tgl',
                        'a.DPokok',
                        'a.KPokok',
                        'a.DBunga',
                        DB::raw('(a.KBunga + a.KRRA + a.KBungaRK) as KBunga'),
                        'a.Denda',
                        'a.Keterangan',
                        'd.Lama'
                    )
                    ->where('a.rekening', '=', $cRekening)
                    ->orderBy('a.tgl')
                    ->orderBy('a.id')
                    ->get();
                $nRow = 0;
                $nTotalDPokok = 0;
                $nTotalKPokok = 0;
                $nBakiDebet = 0;
                $nTotalBunga = 0;
                $nTunggakanBunga = 0;
                $nTotalAngsuran = 0;
                $dTglAkhirAngsuran = Carbon::parse($dTgl);
                $nSisaJasa = 0;
                $nJasaAwal = 0;
                $nKe = 0;
                $nBakiJasa = 0;
                foreach ($vaData4 as $d4) {
                    $dTglAkhirAngsuran = $d4->Tgl;
                    $nTotalDPokok += $d4->DPokok;
                    $nTotalKPokok += $d4->KPokok;
                    $nPokokAwal = $nBakiDebet;
                    $nBakiDebet += $d4->DPokok - $d4->KPokok;
                    if ($d4->Faktur == $cFaktur) {
                        $nJasaAwal = $nTBunga;
                    } else {
                        $nJasaAwal = 0;
                    }
                    $nBakiJasa += $nJasaAwal - $d4->KBunga;
                    $nTotalBunga += $d4->KBunga;
                    $nTotalAngsuran = $d4->KPokok + $d4->KBunga + $d4->Denda;
                    // Untuk Tabel
                    if ($cType == "T") {
                        $vaResult['detail'][] = [
                            'NO' => ++$nRow,
                            'TGL' => date('d-m-Y', strtotime($d4->Tgl)),
                            'KETERANGAN' => $d4->Keterangan,
                            'POKOK' => $d4->KPokok == 0 ? '' : Func::getZFormat($d4->KPokok),
                            'BUNGA' => $d4->KBunga == 0 ? '' : Func::getZFormat($d4->KBunga),
                            'DENDA' => $d4->Denda == 0 ? '' : Func::getZFormat($d4->Denda),
                            'T ANGSURAN' => $nTotalAngsuran == 0 ? '' : Func::getZFormat($nTotalAngsuran),
                            'BAKI DEBET' => $nBakiDebet == 0 ? '' : Func::getZFormat($nBakiDebet)
                        ];
                    } else if ($cType == 'P') {
                        $vaResult['detail'][] = [
                            'No' => ++$nRow,
                            'Tgl' => date('d-m-Y', strtotime($d4->Tgl)),
                            'Keterangan' => $d4->Keterangan,
                            'Pokok' => $d4->KPokok,
                            'Bunga' => $d4->KBunga,
                            'Denda' => $d4->Denda,
                            'TAngsuran' => $nTotalAngsuran,
                            'BakiDebet' => $nBakiDebet
                        ];
                    }
                }
            }
            if (empty($vaResult['detail'])) {
                // Tambahkan entri default ke dalam $vaResult['detail']
                $vaResult['detail'][] = [
                    'NO' => '',
                    'TGL' => '',
                    'KETERANGAN' => '',
                    'POKOK' => '',
                    'BUNGA' => '',
                    'DENDA' => '',
                    'T ANGSURAN' => '',
                    'BAKI DEBET' => ''
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Jadwal Angsuran', 'getData', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Jadwal Angsuran', 'getData', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
