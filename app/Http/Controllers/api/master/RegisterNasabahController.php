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
 * Created on Sat Dec 23 2023 - 09:02:24
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\api\master\PictureController;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\RegisterNasabah;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RegisterNasabahController extends Controller
{

    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            $vaArray = [];
            if ($nReqCount < 1) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Nasabah', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('registernasabah as r')
                ->select(
                    'r.Kode',
                    'r.Nama',
                    'r.Anggota',
                    'r.Alamat',
                    'r.Tgl',
                    'r.RT',
                    'r.RW',
                    'r.KodePos',
                    'r.Pekerjaan',
                    'r.Agama',
                    'r.NoBerkas',
                    'r.KodyaKeterangan',
                    'r.KecamatanKeterangan',
                    'r.KelurahanKeterangan',
                    'r.Kelamin',
                    'r.GolonganDarah',
                    'r.TglLahir',
                    'r.TempatLahir',
                    'r.Telepon',
                    'r.Fax',
                    'r.StatusPerkawinan',
                    'r.KTP',
                    'r.NamaPasangan',
                    'r.TempatLahirPasangan',
                    'r.TglLahirPasangan',
                    'r.KTPPasangan',
                    'r.KodyaPasangan',
                    'r.KecamatanPasangan',
                    'r.KelurahanPasangan',
                    'r.RTRWPasangan',
                    'r.AlamatPasangan',
                    'r.KodePosPasangan',
                    'r.NamaKantor',
                    'r.AlamatKantor',
                    'r.TeleponKantor',
                    'r.KodePosKantor',
                    'r.FaxKantor',
                    'r.Bagian',
                    'r.AlamatTinggal',
                    'r.KodePosTinggal',
                    'r.TeleponTinggal',
                    'r.FaxTinggal',
                    'r.KodyaTinggal',
                    'r.KecamatanTinggal',
                    'r.KelurahanTinggal',
                    'r.RTRWTinggal',
                    'a.Keterangan as KetAgama',
                    'p.Keterangan as KetPekerjaan'
                )
                ->leftJoin('agama as a', 'a.Kode', '=', 'r.Agama')
                ->leftJoin('pekerjaan as p', 'p.Kode', '=', 'r.Pekerjaan');
            $vaData->orderByDesc('Kode');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, 'LIKE', '%' . $filterValue . '%');
                }
            }
            if ($vaRequestData['page'] == null) {
                $vaData = $vaData->get();
            } else {
                $vaData = $vaData->paginate($nLimit);
            }
            // JIKA REQUEST SUKSES
            foreach ($vaData as $d) {
                // return $vaData;
                $vaArray[] = [
                    'Kode' => strval($d->Kode),
                    'Nama' => strval($d->Nama),
                    'Anggota' => $d->Anggota == 1 ? 'Anggota' : 'Calon Anggota',
                    'Alamat' => strval($d->Alamat),
                    'Tgl' => date('d-m-Y', strtotime($d->Tgl)),
                    'RT' => "'" . $d->RT,
                    'RW' => "'" . $d->RW,
                    'KodePos' => "'" . $d->KodePos,
                    'KodePekerjaan' => strval($d->Pekerjaan),
                    'Pekerjaan' => strval($d->KetPekerjaan),
                    'KodeAgama' => strval($d->Agama),
                    'Agama' => strval($d->KetAgama),
                    'NoBerkas' => strval($d->NoBerkas),
                    'KodyaKeterangan' => strval($d->KodyaKeterangan),
                    'KecamatanKeterangan' => strval($d->KecamatanKeterangan),
                    'KelurahanKeterangan' => strval($d->KelurahanKeterangan),
                    'Kelamin' => strval($d->Kelamin),
                    'GolonganDarah' => strval($d->GolonganDarah),
                    'TglLahir' => strval($d->TglLahir),
                    'TempatLahir' => strval($d->TempatLahir),
                    'Telepon' => "'" . $d->Telepon,
                    'Fax' => "'" . $d->Fax,
                    'StatusPerkawinan' => $d->StatusPerkawinan == 'B' ? 'Belum Kawin' : 'Kawin',
                    'KTP' => "'" . $d->KTP,
                    'NamaPasangan' => strval($d->NamaPasangan),
                    'TempatLahirPasangan' => strval($d->TempatLahirPasangan),
                    'TglLahirPasangan' => date('d-m-Y', strtotime($d->TglLahirPasangan)),
                    'KTPPasangan' => "'" . $d->KTPPasangan,
                    'KodyaPasangan' => strval($d->KodyaPasangan),
                    'KecamatanPasangan' => strval($d->KecamatanPasangan),
                    'KelurahanPasangan' => strval($d->KelurahanPasangan),
                    'RTRWPasangan' => "'" . $d->RTRWPasangan,
                    'AlamatPasangan' => strval($d->AlamatPasangan),
                    'KodePosPasangan' => "'" . $d->KodePosPasangan,
                    'NamaKantor' => strval($d->NamaKantor),
                    'AlamatKantor' => strval($d->AlamatKantor),
                    'TeleponKantor' => "'" . $d->TeleponKantor,
                    'KodePosKantor' => "'" . $d->KodePosKantor,
                    'FaxKantor' => "'" . $d->FaxKantor,
                    'Bagian' => strval($d->Bagian),
                    'AlamatTinggal' => strval($d->AlamatTinggal),
                    'KodePosTinggal' => "'" . $d->KodePosTinggal,
                    'TeleponTinggal' => "'" . $d->TeleponTinggal,
                    'FaxTinggal' => "'" . $d->FaxTinggal,
                    'KodyaTinggal' => strval($d->KodyaTinggal),
                    'KecamatanTinggal' => strval($d->KecamatanTinggal),
                    'KelurahanTinggal' => strval($d->KelurahanTinggal),
                    'RTRWTinggal' => "'" . $d->RTRWTinggal
                ];
            }
            $vaRetVal = [
                "status" => "00",
                "message" => $vaArray
            ];
            Func::writeLog('Register Nasabah', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaArray);
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
            Func::writeLog('Register Nasabah', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataEdit(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Kode'] == null || empty($vaRequestData['Kode'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Nasabah', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cKode = $vaRequestData['Kode'];
            $vaData = DB::table('registernasabah as r')
                ->select(
                    'r.Kode',
                    'r.Nama',
                    'r.Anggota',
                    'r.Alamat',
                    'r.Tgl',
                    'r.RT',
                    'r.RW',
                    'r.KodePos',
                    'r.Pekerjaan',
                    'r.Agama',
                    'r.NoBerkas',
                    'r.KodyaKeterangan',
                    'r.KecamatanKeterangan',
                    'r.KelurahanKeterangan',
                    'r.Kelamin',
                    'r.GolonganDarah',
                    'r.TglLahir',
                    'r.TempatLahir',
                    'r.Telepon',
                    'r.Fax',
                    'r.StatusPerkawinan',
                    'r.KTP',
                    'r.NamaPasangan',
                    'r.TempatLahirPasangan',
                    'r.TglLahirPasangan',
                    'r.KTPPasangan',
                    'r.KodyaPasangan',
                    'r.KecamatanPasangan',
                    'r.KelurahanPasangan',
                    'r.RTRWPasangan',
                    'r.AlamatPasangan',
                    'r.KodePosPasangan',
                    'r.NamaKantor',
                    'r.AlamatKantor',
                    'r.TeleponKantor',
                    'r.KodePosKantor',
                    'r.FaxKantor',
                    'r.Bagian',
                    'r.AlamatTinggal',
                    'r.KodePosTinggal',
                    'r.TeleponTinggal',
                    'r.FaxTinggal',
                    'r.KodyaTinggal',
                    'r.KecamatanTinggal',
                    'r.KelurahanTinggal',
                    'r.RTRWTinggal',
                    'a.Keterangan as KetAgama',
                    'p.Keterangan as KetPekerjaan'
                )
                ->leftJoin('agama as a', 'a.Kode', '=', 'r.Agama')
                ->leftJoin('pekerjaan as p', 'p.Kode', '=', 'r.Pekerjaan')
                ->where('r.Kode', '=', $cKode)
                ->first();
            if ($vaData) {
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Register Nasabah', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Register Nasabah', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
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
            Func::writeLog('Register Nasabah', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 4) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make(
                $request->all(),
                [
                    'Kode' => 'required|max:20',
                    'Nama' => 'required|max:100',
                    'Anggota' => 'max:1',
                    'Pekerjaan' => 'max:4',
                    'Alamat' => 'required|max:255',
                    'NoBerkas' => 'max:20',
                    'Tgl' => 'date',
                    'RT' => 'max:10',
                    'RW' => 'max:10',
                    'KodePos' => 'max:10',
                    'Agama' => 'max:4',
                    'KodyaKeterangan' => 'max:50',
                    'KecamatanKeterangan' => 'max:50',
                    'KelurahanKeterangan' => 'max:50',
                    'Kelamin' => 'max:1',
                    'GolonganDarah' => 'max:2',
                    'TglLahir' => 'date',
                    'TempatLahir' => 'max:255',
                    'Telepon' => 'max:30',
                    'Fax' => 'max:255',
                    'StatusPerkawinan' => 'max:1',
                    'KTP' => 'max:30',
                    'NamaPasangan' => 'max:40',
                    'TempatLahirPasangan' => 'max:255',
                    'TglLahirPasangan' => 'date',
                    'KTPPasangan' => 'max:30',
                    'KodyaKeteranganPasangan' => 'max:255',
                    'KecamatanPasangan' => 'max:255',
                    'KelurahanPasangan' => 'max:255',
                    'RTRWPasangan' => 'max:7',
                    'AlamatPasangan' => 'max:50',
                    'NamaKantor' => 'max:50',
                    'AlamatKantor' => 'max:50',
                    'TeleponKantor' => 'max:30',
                    'FaxKantor' => 'max:30',
                    'AlamatTinggal' => 'max:50',
                    'KodePosTinggal' => 'max:10',
                    'TeleponTinggal' => 'max:30',
                    'FaxTinggal' => 'max:30',
                    'KodyaTinggal' => 'max:255',
                    'KecamatanTinggal' => 'max:255',
                    'KelurahanTinggal' => 'max:255',
                    'RTRWTinggal' => 'max:255',
                ],
                [
                    'required' => 'Kolom :attribute harus diisi.',
                    'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
                ]
            );
            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }
            $vaArray = [
                'Kode' => GetterSetter::getRekening(0, 7),
                'Nama' => $vaRequestData['Nama'],
                'Anggota' => $vaRequestData['Anggota'],
                'Pekerjaan' => $vaRequestData['Pekerjaan'] ?? null,
                'Alamat' => $vaRequestData['Alamat'],
                'NoBerkas' => $vaRequestData['NoBerkas'] ?? null,
                'Tgl' => $vaRequestData['Tgl'],
                'RT' => $vaRequestData['RT'] ?? null,
                'RW' => $vaRequestData['RW'] ?? null,
                'KodePos' => $vaRequestData['KodePos'] ?? null,
                'Agama' => $vaRequestData['Agama'] ?? null,
                'KodyaKeterangan' => $vaRequestData['KodyaKeterangan'] ?? null,
                'KecamatanKeterangan' => $vaRequestData['KecamatanKeterangan'] ?? null,
                'KelurahanKeterangan' => $vaRequestData['KelurahanKeterangan'] ?? null,
                'Kelamin' => $vaRequestData['Kelamin'] ?? null,
                'GolonganDarah' => $vaRequestData['GolonganDarah'] ?? null,
                'TglLahir' => $vaRequestData['TglLahir'] ?? null,
                'TempatLahir' => $vaRequestData['TempatLahir'] ?? null,
                'Telepon' => $vaRequestData['Telepon'] ?? null,
                'Fax' => $vaRequestData['Fax'] ?? null,
                'StatusPerkawinan' => $vaRequestData['StatusPerkawinan'] ?? null,
                'KTP' => $vaRequestData['KTP'] ?? null,
                'NamaPasangan' => $vaRequestData['NamaPasangan'] ?? null,
                'TempatLahirPasangan' => $vaRequestData['TempatLahirPasangan'] ?? null,
                'TglLahirPasangan' => $vaRequestData['TglLahirPasangan'] ?? null,
                'KTPPasangan' => $vaRequestData['KTPPasangan'] ?? null,
                'KodyaPasangan' => $vaRequestData['KodyaPasangan'] ?? null,
                'KecamatanPasangan' => $vaRequestData['KecamatanPasangan'] ?? null,
                'KelurahanPasangan' => $vaRequestData['KelurahanPasangan'] ?? null,
                'KodyaPasangan' => $vaRequestData['KodyaPasangan'] ?? null,
                'RTRWPasangan' => $vaRequestData['RTRWPasangan'] ?? null,
                'AlamatPasangan' => $vaRequestData['AlamatPasangan'] ?? null,
                'KodePosPasangan' => $vaRequestData['KodePosPasangan'] ?? null,
                'NamaKantor' => $vaRequestData['NamaKantor'] ?? null,
                'AlamatKantor' => $vaRequestData['AlamatKantor'] ?? null,
                'KodePosKantor' => $vaRequestData['KodePosKantor'] ?? null,
                'TeleponKantor' => $vaRequestData['TeleponKantor'] ?? null,
                'FaxKantor' => $vaRequestData['FaxKantor'] ?? null,
                'Bagian' => $vaRequestData['Jabatan'] ?? null,
                'AlamatTinggal' => $vaRequestData['AlamatTinggal'] ?? $vaRequestData['Alamat'],
                'KodePosTinggal' => $vaRequestData['KodePosTinggal'] ?? $vaRequestData['KodePos'] ?? null,
                'TeleponTinggal' => $vaRequestData['TeleponTinggal'] ?? $vaRequestData['Telepon'] ?? null,
                'FaxTinggal' => $vaRequestData['FaxTinggal'] ?? $vaRequestData['Fax'] ?? null,
                'KodyaTinggal' => $vaRequestData['KodyaTinggal'] ?? $vaRequestData['KodyaKeterangan'] ?? null,
                'KecamatanTinggal' => $vaRequestData['KecamatanTinggal'] ?? $vaRequestData['KecamatanKeterangan'] ?? null,
                'KelurahanTinggal' => $vaRequestData['KelurahanTinggal'] ?? $vaRequestData['KelurahanKeterangan'] ?? null, 'RTRWTinggal' => isset($vaRequestData['RTRWTinggal']) ? $vaRequestData['RTRWTinggal'] : (
                    isset($vaRequestData['RT']) && isset($vaRequestData['RW']) ? $vaRequestData['RT'] . '/' . $vaRequestData['RW'] : null
                ),
            ];
            RegisterNasabah::create($vaArray);
            GetterSetter::setRekening('0');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
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
            Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function update(Request $request, $Kode)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 4) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make(
                $request->all(),
                [
                    'Kode' => 'required|max:20',
                    'Nama' => 'required|max:100',
                    'Anggota' => 'max:1',
                    'Pekerjaan' => 'max:4',
                    'Alamat' => 'required|max:255',
                    'NoBerkas' => 'max:20',
                    'Tgl' => 'date',
                    'RT' => 'max:10',
                    'RW' => 'max:10',
                    'KodePos' => 'max:10',
                    'Agama' => 'max:4',
                    'KodyaKeterangan' => 'max:50',
                    'KecamatanKeterangan' => 'max:50',
                    'KelurahanKeterangan' => 'max:50',
                    'Kelamin' => 'max:1',
                    'GolonganDarah' => 'max:2',
                    'TglLahir' => 'date',
                    'TempatLahir' => 'max:255',
                    'Telepon' => 'max:30',
                    'Fax' => 'max:255',
                    'StatusPerkawinan' => 'max:1',
                    'KTP' => 'max:30',
                    'NamaPasangan' => 'max:40',
                    'TempatLahirPasangan' => 'max:255',
                    'TglLahirPasangan' => 'date',
                    'KTPPasangan' => 'max:30',
                    'KodyaKeteranganPasangan' => 'max:255',
                    'KecamatanPasangan' => 'max:255',
                    'KelurahanPasangan' => 'max:255',
                    'RTRWPasangan' => 'max:7',
                    'AlamatPasangan' => 'max:50',
                    'NamaKantor' => 'max:50',
                    'AlamatKantor' => 'max:50',
                    'TeleponKantor' => 'max:30',
                    'FaxKantor' => 'max:30',
                    'AlamatTinggal' => 'max:50',
                    'KodePosTinggal' => 'max:10',
                    'TeleponTinggal' => 'max:30',
                    'FaxTinggal' => 'max:30',
                    'KodyaTinggal' => 'max:255',
                    'KecamatanTinggal' => 'max:255',
                    'KelurahanTinggal' => 'max:255',
                    'RTRWTinggal' => 'max:255',
                ],
                [
                    'required' => 'Kolom :attribute harus diisi.',
                    'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
                ]
            );
            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Register Nasabah', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }
            $cKode = $vaRequestData['Kode'];
            $vaData = DB::table('registernasabah')
                ->where('Kode', '=', $cKode)
                ->exists();
            if ($vaData) {
                $vaArray = [
                    'Kode' => $vaRequestData['Kode'],
                    'Nama' => $vaRequestData['Nama'],
                    'Anggota' => $vaRequestData['Anggota'],
                    'Pekerjaan' => $vaRequestData['Pekerjaan'] ?? null,
                    'Alamat' => $vaRequestData['Alamat'],
                    'NoBerkas' => $vaRequestData['NoBerkas'] ?? null,
                    'Tgl' => $vaRequestData['Tgl'],
                    'RT' => $vaRequestData['RT'] ?? null,
                    'RW' => $vaRequestData['RW'] ?? null,
                    'KodePos' => $vaRequestData['KodePos'] ?? null,
                    'Agama' => $vaRequestData['Agama'] ?? null,
                    'KodyaKeterangan' => $vaRequestData['KodyaKeterangan'] ?? null,
                    'KecamatanKeterangan' => $vaRequestData['KecamatanKeterangan'] ?? null,
                    'KelurahanKeterangan' => $vaRequestData['KelurahanKeterangan'] ?? null,
                    'Kelamin' => $vaRequestData['Kelamin'] ?? null,
                    'GolonganDarah' => $vaRequestData['GolonganDarah'] ?? null,
                    'TglLahir' => $vaRequestData['TglLahir'] ?? null,
                    'TempatLahir' => $vaRequestData['TempatLahir'] ?? null,
                    'Telepon' => $vaRequestData['Telepon'] ?? null,
                    'Fax' => $vaRequestData['Fax'] ?? null,
                    'StatusPerkawinan' => $vaRequestData['StatusPerkawinan'] ?? null,
                    'KTP' => $vaRequestData['KTP'] ?? null,
                    'NamaPasangan' => $vaRequestData['NamaPasangan'] ?? null,
                    'TempatLahirPasangan' => $vaRequestData['TempatLahirPasangan'] ?? null,
                    'TglLahirPasangan' => $vaRequestData['TglLahirPasangan'] ?? null,
                    'KTPPasangan' => $vaRequestData['KTPPasangan'] ?? null,
                    'KodyaPasangan' => $vaRequestData['KodyaPasangan'] ?? null,
                    'KecamatanPasangan' => $vaRequestData['KecamatanPasangan'] ?? null,
                    'KelurahanPasangan' => $vaRequestData['KelurahanPasangan'] ?? null,
                    'KodyaPasangan' => $vaRequestData['KodyaPasangan'] ?? null,
                    'RTRWPasangan' => $vaRequestData['RTRWPasangan'] ?? null,
                    'AlamatPasangan' => $vaRequestData['AlamatPasangan'] ?? null,
                    'KodePosPasangan' => $vaRequestData['KodePosPasangan'] ?? null,
                    'NamaKantor' => $vaRequestData['NamaKantor'] ?? null,
                    'AlamatKantor' => $vaRequestData['AlamatKantor'] ?? null,
                    'KodePosKantor' => $vaRequestData['KodePosKantor'] ?? null,
                    'TeleponKantor' => $vaRequestData['TeleponKantor'] ?? null,
                    'FaxKantor' => $vaRequestData['FaxKantor'] ?? null,
                    'Bagian' => $vaRequestData['Jabatan'] ?? null,
                    'AlamatTinggal' => $vaRequestData['AlamatTinggal'] ?? $vaRequestData['Alamat'] ?? null,
                    'KodePosTinggal' => $vaRequestData['KodePosTinggal'] ?? $vaRequestData['KodePos'] ?? null,
                    'TeleponTinggal' => $vaRequestData['TeleponTinggal'] ?? $vaRequestData['Telepon'] ?? null,
                    'FaxTinggal' => $vaRequestData['FaxTinggal'] ?? $vaRequestData['Fax'] ?? null,
                    'KodyaTinggal' => $vaRequestData['KodyaTinggal'] ?? $vaRequestData['KodyaKeterangan'] ?? null,
                    'KecamatanTinggal' => $vaRequestData['KecamatanTinggal'] ?? $vaRequestData['KecamatanKeterangan'] ?? null,
                    'KelurahanTinggal' => $vaRequestData['KelurahanTinggal'] ?? $vaRequestData['KelurahanKeterangan'] ?? null,
                    'RTRWTinggal' => $vaRequestData['RTRWTinggal'] ?? ($vaRequestData['RT'] && $vaRequestData['RW'] ? $vaRequestData['RT'] . '/' . $vaRequestData['RW'] : null),
                ];
                RegisterNasabah::where('Kode', '=', $cKode)->update($vaArray);
                $vaRetVal = [
                    "status" => "00",
                    "message" => "SUKSES"
                ];
                Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'success']);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'error']);
            }
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

            Func::writeLog('Register Nasabah', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function delete(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Kode'] == null || empty($vaRequestData['Kode'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Register Nasabah', 'delete', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = RegisterNasabah::findOrFail($vaRequestData['Kode']);
            $vaData->delete();
            DB::table('picture')->where('Kode', $request->Kode)->delete();
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Register Nasabah', 'delete', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
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
            Func::writeLog('Register Nasabah', 'delete', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
