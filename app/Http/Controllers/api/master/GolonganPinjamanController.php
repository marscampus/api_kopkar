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
 *************************************
 *Pasal 72 ayat 3 UU Hak Cipta berbunyi,
 *' Barangsiapa dengan sengaja dan tanpa hak memperbanyak penggunaan untuk kepentingan komersial '
 *' suatu program komputer dipidana dengan pidana penjara paling lama 5 (lima) tahun dan/atau '
 *' denda paling banyak Rp. 500.000.000,00 (lima ratus juta rupiah) '
 *************************************
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
 * Created on Fri Dec 08 2023 - 15:37:58
 * Author : Taufiq | ovie.reog@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\GolonganPinjaman;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class GolonganPinjamanController extends Controller
{
    //
    function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $GolonganPinjaman = GolonganPinjaman::select(
                    'Kode',
                    'Keterangan',
                    'Rekening',
                    'RekeningBunga',
                    'RekeningBungaPinalty',
                    'RekeningDenda',
                    'RekeningProvisi',
                    'RekeningAdministrasi',
                    'RekeningMaterai',
                    'RekeningNotaris',
                    'RekeningTitipan',
                    'RekeningAsuransi',
                    'RekeningCadanganBunga',
                    'RekeningPendapatanProvisi',
                    'RekeningBiayaTaksasi',
                    'RekeningBiayaRC',
                    'RekeningPajakBiayaRC',
                    'RekeningAdministratif',
                    'KodeLama',
                    'RekeningMarketing',
                    'RekeningBPKB',
                    'RekeningBiayaLainnya',
                    'RekeningLainnya',
                    'RekeningBiayaTransaksi',
                    'RekeningTitipanBiayaTransaksi',
                    'RekeningPajakBiayaTransaksi',
                    'RekeningPendapatanAdministrasi',
                    'RekeningPendapatanNotaris',
                    'RekeningPendapatanBiayaTransaksi',
                    'RekeningIPTW',
                    'Administrasi',
                    'TglUpdate',
                    'Pengakuan',
                    'TglPengakuan',
                    'RekeningPendapatanLainnya',
                    'RekeningHapusBuku',
                    'RekeningBungaHapusBuku',
                    'PersenIPTW',
                    'RekeningPPAP',
                    'RekeningPPAPNPL',
                    'RekeningPendapatanPPAP',
                    'RekeningBiayaPPAP',
                    'RekeningAdministrasiHapusBuku',
                    'OffBalance'
                )
                    ->where($k, "LIKE", '%' . $v . '%')
                    ->orderBy('Kode')
                    ->paginate(10);
                return response()->json($GolonganPinjaman);
            }
        }
        $GolonganPinjaman = GolonganPinjaman::select(
            'Kode',
            'Keterangan',
            'Rekening',
            'RekeningBunga',
            'RekeningBungaPinalty',
            'RekeningDenda',
            'RekeningProvisi',
            'RekeningAdministrasi',
            'RekeningMaterai',
            'RekeningNotaris',
            'RekeningTitipan',
            'RekeningAsuransi',
            'RekeningCadanganBunga',
            'RekeningPendapatanProvisi',
            'RekeningBiayaTaksasi',
            'RekeningBiayaRC',
            'RekeningPajakBiayaRC',
            'RekeningAdministratif',
            'KodeLama',
            'RekeningMarketing',
            'RekeningBPKB',
            'RekeningBiayaLainnya',
            'RekeningLainnya',
            'RekeningBiayaTransaksi',
            'RekeningTitipanBiayaTransaksi',
            'RekeningPajakBiayaTransaksi',
            'RekeningPendapatanAdministrasi',
            'RekeningPendapatanNotaris',
            'RekeningPendapatanBiayaTransaksi',
            'RekeningIPTW',
            'Administrasi',
            'TglUpdate',
            'Pengakuan',
            'TglPengakuan',
            'RekeningPendapatanLainnya',
            'RekeningHapusBuku',
            'RekeningBungaHapusBuku',
            'PersenIPTW',
            'RekeningPPAP',
            'RekeningPPAPNPL',
            'RekeningPendapatanPPAP',
            'RekeningBiayaPPAP',
            'RekeningAdministrasiHapusBuku',
            'OffBalance'
        )

            ->orderBy('Kode')
            ->paginate(10);
        return response()->json($GolonganPinjaman);
    }

    function dataGolongan(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $GolonganPinjaman = GolonganPinjaman::select(
                    'Kode',
                    'Keterangan',
                    'Rekening',
                    'RekeningBunga',
                    'RekeningBungaPinalty',
                    'RekeningDenda',
                    'RekeningProvisi',
                    'RekeningAdministrasi',
                    'RekeningMaterai',
                    'RekeningNotaris',
                    'RekeningTitipan',
                    'RekeningAsuransi',
                    'RekeningCadanganBunga',
                    'RekeningPendapatanProvisi',
                    'RekeningBiayaTaksasi',
                    'RekeningBiayaRC',
                    'RekeningPajakBiayaRC',
                    'RekeningAdministratif',
                    'KodeLama',
                    'RekeningMarketing',
                    'RekeningBPKB',
                    'RekeningBiayaLainnya',
                    'RekeningLainnya',
                    'RekeningBiayaTransaksi',
                    'RekeningTitipanBiayaTransaksi',
                    'RekeningPajakBiayaTransaksi',
                    'RekeningPendapatanAdministrasi',
                    'RekeningPendapatanNotaris',
                    'RekeningPendapatanBiayaTransaksi',
                    'RekeningIPTW',
                    'Administrasi',
                    'TglUpdate',
                    'Pengakuan',
                    'TglPengakuan',
                    'RekeningPendapatanLainnya',
                    'RekeningHapusBuku',
                    'RekeningBungaHapusBuku',
                    'PersenIPTW',
                    'RekeningPPAP',
                    'RekeningPPAPNPL',
                    'RekeningPendapatanPPAP',
                    'RekeningBiayaPPAP',
                    'RekeningAdministrasiHapusBuku',
                    'OffBalance'
                )
                    ->where($k, "LIKE", '%' . $v . '%')->orderBy('Kode')->get();
                return response()->json($GolonganPinjaman);
            }
        }
        $GolonganPinjaman = GolonganPinjaman::select(
            'Kode',
            'Keterangan',
            'Rekening',
            'RekeningBunga',
            'RekeningBungaPinalty',
            'RekeningDenda',
            'RekeningProvisi',
            'RekeningAdministrasi',
            'RekeningMaterai',
            'RekeningNotaris',
            'RekeningTitipan',
            'RekeningAsuransi',
            'RekeningCadanganBunga',
            'RekeningPendapatanProvisi',
            'RekeningBiayaTaksasi',
            'RekeningBiayaRC',
            'RekeningPajakBiayaRC',
            'RekeningAdministratif',
            'KodeLama',
            'RekeningMarketing',
            'RekeningBPKB',
            'RekeningBiayaLainnya',
            'RekeningLainnya',
            'RekeningBiayaTransaksi',
            'RekeningTitipanBiayaTransaksi',
            'RekeningPajakBiayaTransaksi',
            'RekeningPendapatanAdministrasi',
            'RekeningPendapatanNotaris',
            'RekeningPendapatanBiayaTransaksi',
            'RekeningIPTW',
            'Administrasi',
            'TglUpdate',
            'Pengakuan',
            'TglPengakuan',
            'RekeningPendapatanLainnya',
            'RekeningHapusBuku',
            'RekeningBungaHapusBuku',
            'PersenIPTW',
            'RekeningPPAP',
            'RekeningPPAPNPL',
            'RekeningPendapatanPPAP',
            'RekeningBiayaPPAP',
            'RekeningAdministrasiHapusBuku',
            'OffBalance'
        )->orderBy('Kode')->get();
        return response()->json($GolonganPinjaman);
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        $rekening = $request->Rekening;
        $rekeningbunga = $request->RekeningBunga;
        $rekeningdenda = $request->RekeningDenda;
        try {
            $GolonganPinjaman = GolonganPinjaman::create([
                'Kode' => $Kode,
                'Keterangan' => $keterangan,
                'Rekening'  => $rekening,
                'RekeningBunga'  => $rekeningbunga,
                'RekeningDenda'  => $rekeningdenda
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $GolonganPinjaman = GolonganPinjaman::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan,
            'Rekening'  => $request->Rekening,
            'RekeningBunga'  => $request->RekeningBunga,
            'RekeningDenda'  => $request->RekeningDenda
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        // return response()->json([$request]);
        try {
            $GolonganPinjaman = GolonganPinjaman::findOrFail($request->Kode);
            $GolonganPinjaman->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' =>  $th]);
        }
    }
}
