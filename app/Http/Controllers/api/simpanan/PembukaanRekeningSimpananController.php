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
 * Created on Thu Dec 07 2023 - 09:15:20
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PembukaanRekeningSimpananController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            if ($nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('tabungan as t')
                ->select(
                    't.Rekening',
                    't.Kode',
                    't.Tgl',
                    'r.Nama',
                    'r.Alamat'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry');
            // JIKA REQ KOSONG ATAU NULL
            if ($vaRequestData['TglAwal'] == null || $vaRequestData['TglAkhir'] == null || empty($vaRequestData['TglAwal']) || empty($vaRequestData['TglAkhir'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            $vaData->whereBetween('t.Tgl', [$vaRequestData['TglAwal'], $vaRequestData['TglAkhir']]);
            $vaData->whereBetween('t.AO', [$vaRequestData['AOAwal'], $vaRequestData['AOAkhir']]);
            $vaData->whereBetween('t.GolonganTabungan', [$vaRequestData['GolSimpananAwal'], $vaRequestData['GolSimpananAkhir']]);
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData->when(
                $cJenisGabungan !== 'C',
                function ($query) use ($cJenisGabungan, $cCabang) {
                    $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                }
            );
            $vaData->orderByDesc('t.tgl');
            $vaData = $vaData->get();
            $vaResult = [
                'data' => $vaData,
                'total_data' => count($vaData)
            ];
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
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

            Func::writeLog('Pembukaan Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function getDataCIF(Request $request)
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
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataCIF', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('registernasabah as r')
                ->select(
                    'r.Nama',
                    'r.Alamat',
                    'r.Telepon',
                    'r.Pekerjaan',
                    'p.Keterangan as NamaPekerjaan',
                    'r.Keterkaitan',
                    'k.Keterangan as NamaKeterkaitan',
                    'r.KTP',
                    'r.TempatLahir',
                    'r.TglLahir',
                    'r.KodePos',
                    'r.KodyaKeterangan',
                    'a.Keterangan as KetAgama'
                )
                ->leftJoin('pekerjaan as p', 'p.Kode', '=', 'r.Pekerjaan')
                ->leftJoin('keterkaitan as k', 'k.Kode', '=', 'r.keterkaitan')
                ->leftJoin('agama as a', 'a.Kode', '=', 'r.Agama')
                ->where('r.Kode', '=', $vaRequestData['Kode'])
                ->first();
            if ($vaData) {
                $vaResult = [
                    'Kode' => $vaRequestData['Kode'],
                    'Nama' => $vaData->Nama ? $vaData->Nama : '',
                    'Alamat' => $vaData->Alamat ? $vaData->Alamat : '',
                    'KTP' => $vaData->KTP ? $vaData->KTP : '',
                    'TempatLahir' => $vaData->TempatLahir ? $vaData->TempatLahir : '',
                    'TglLahir' => $vaData->TglLahir ? $vaData->TglLahir : '',
                    'KodePos' => $vaData->KodePos ? $vaData->KodePos : '',
                    'KodyaKeterangan' => $vaData->KodyaKeterangan ? $vaData->KodyaKeterangan : '',
                    'Agama' => $vaData->KetAgama ? $vaData->KetAgama : '',
                    'Tanggal' => GetterSetter::getTglTransaksi(),
                    'Telepon' => $vaData->Telepon ? $vaData->Telepon : '',
                    'Pekerjaan' => $vaData->Pekerjaan ? $vaData->Pekerjaan : '',
                    'NamaPekerjaan' => $vaData->NamaPekerjaan ? $vaData->NamaPekerjaan : '',
                    'Keterkaitan' => $vaData->Keterkaitan ? $vaData->Keterkaitan : '',
                    'NamaKeterkaitan' => $vaData->NamaKeterkaitan ? $vaData->NamaKeterkaitan : ''
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataCIF', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataCIF', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pembukaan Rekening Simpanan', 'getDataCIF', $vaRequestData, $vaRetVal, $cUser);
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
            if ($nReqCount > 10 || $nReqCount < 10) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make($request->all(), [
                'Rekening' => 'required|max:15',
                'Tgl' => 'date',
                'Kode' => 'required|max:12',
                'NamaNasabah' => 'required|max:255',
                'GolonganTabungan' => 'required|max:6',
                'AO' => 'required|max:10',
                'StatusBunga' => 'required|max:1',
                'StatusAdministrasi' => 'required|max:1',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            ]);
            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }
            $vaArray = [
                'Rekening' => $vaRequestData['Rekening'] ?? '',
                'Tgl' => $vaRequestData['Tgl'] ?? '',
                'Kode' => $vaRequestData['Kode'] ?? '',
                'NamaNasabah' => $vaRequestData['NamaNasabah'] ?? '',
                'GolonganTabungan' => $vaRequestData['GolonganTabungan'] ?? '',
                'AO' => $vaRequestData['AO'] ?? '',
                'AhliWaris' => $vaRequestData['AhliWaris'] ?? '',
                'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                'NoBuku' => $vaRequestData['NoBuku'] ?? '',
                'StatusBunga' => $vaRequestData['StatusBunga'] ?? '',
                'StatusAdministrasi' => $vaRequestData['StatusAdministrasi'] ?? ''
            ];
            Tabungan::create($vaArray);
            GetterSetter::setRekening('1');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pembukaan Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pembukaan Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function getDataEdit(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = DB::table('tabungan as t')
                ->select(
                    't.Rekening',
                    't.Tgl',
                    't.AO',
                    'a.Nama as NamaAo',
                    't.GolonganTabungan',
                    'g.Keterangan as KetGolTab',
                    't.AhliWaris',
                    't.StatusBunga',
                    't.StatusAdministrasi',
                    't.NoBuku',
                    't.NamaNasabah',
                    't.Kode',
                    'r.Telepon',
                    'r.Alamat',
                    'g.SaldoMinimum',
                    'g.SetoranMinimum',
                    'g.SaldoMinimumDapatBunga'
                )
                ->leftJoin('ao as a', 'a.Kode', '=', 't.AO')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->where('t.Rekening', '=', $vaRequestData['Rekening'])
                ->first();
            if ($vaData) {
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pembukaan Rekening Simpanan', 'getDataEdit', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function update(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 12 || $vaRequestData < 12) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'update', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make($request->all(), [
                'Rekening' => 'required|max:15',
                'Tgl' => 'date',
                'Kode' => 'required|max:12',
                'AO' => 'required|max:10',
                'GolTab' => 'required|max:6',
                'AhliWaris' => 'max:50',
                'StatusBunga' => 'required|max:1',
                'StatusAdministrasi' => 'required|max:1',
                'NoBuku' => 'required|max:25',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            ]);
            if ($vaValidator->fails()) {
                $vaErrorMsgs = $vaValidator->errors()->first();
                $vaRetVal = [
                    "status" => "99",
                    "message" => $vaErrorMsgs
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'update', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => $vaErrorMsgs
                ]);
            }
            $cRekening = $vaRequestData['Rekening'];
            $vaData = DB::table('tabungan')
                ->where('Rekening', '=', $cRekening)
                ->exists();
            if ($vaData) {
                $vaArray = [
                    'Kode' => $vaRequestData['Kode'],
                    'Rekening' => $cRekening,
                    'Tgl' => $vaRequestData['Tgl'],
                    'NamaNasabah' => $vaRequestData['Nama'],
                    'AO' => $vaRequestData['AO'],
                    'GolonganTabungan' => $vaRequestData['GolTab'],
                    'AhliWaris' => $vaRequestData['AhliWaris'],
                    'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                    'StatusBunga' => $vaRequestData['StatusBunga'],
                    'StatusAdministrasi' => $vaRequestData['StatusAdministrasi'],
                    'NoBuku' => $vaRequestData['NoBuku'],
                ];
                Tabungan::where('Rekening', $cRekening)->update($vaArray);
                $vaRetVal = [
                    "status" => "00",
                    "message" => "SUKSES"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'update', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'success']);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];

                Func::writeLog('Pembukaan Rekening Simpanan', 'update', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Pembukaan Rekening Simpanan', 'update', $vaRequestData, $vaRetVal, $cUser);
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
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Pembukaan Rekening Simpanan', 'delete', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaData = Tabungan::findOrFail($vaRequestData['Rekening']);
            $vaData->delete();
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pembukaan Rekening Simpanan', 'delete', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Pembukaan Rekening Simpanan', 'delete', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
