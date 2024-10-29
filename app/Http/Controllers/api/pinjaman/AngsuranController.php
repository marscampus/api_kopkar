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
 * Created on Fri Apr 05 2024 - 08:26:38
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Func;
use App\Helpers\PerhitunganPinjaman;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use Illuminate\Support\Facades\Validator;

class AngsuranController extends Controller
{
    function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            if ($nReqCount < 1) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Angsuran', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = now()->format('Y-m-d');

            $vaData = DB::table("angsuran")
                ->select(
                    'Faktur',
                    'Tgl as TglTrans',
                    'KBunga as Bunga',
                    'KRRA',
                    'DPokok',
                    'KPokok',
                    'Denda',
                    'UserName',
                    'DTitipan',
                    'KTitipan'
                )
                ->where('Rekening', '=', $cRekening)
                ->where('Tgl', '<=', $dTgl)
                ->orderByDesc('Rekening')
                ->paginate($nLimit);

            $nBakiDebet = 0;
            $vaArray = [];

            foreach ($vaData as $d) {
                $nBakiDebet = $nBakiDebet + $d->DPokok - $d->KPokok;

                $vaArray[] = [
                    "Faktur" => $d->Faktur,
                    "Tgl" => $d->TglTrans,
                    "Pokok" => $d->KPokok,
                    "BakiDebet" => $nBakiDebet,
                    "Bunga" => $d->Bunga + $d->KRRA,
                    "Denda" => $d->Denda,
                    "DTitipan" => $d->DTitipan,
                    "KTitipan" => $d->KTitipan,
                    "UserName" => $d->UserName
                ];
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Angsuran', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaArray);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Angsuran', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaRetVal);
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

            Func::writeLog('Angsuran', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getAngsuranData(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Angsuran', 'getAngsuranData', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = GetterSetter::getTglTransaksi();
            $vaData = DB::table('debitur as d')
                ->select(
                    'd.Kode',
                    'd.RekeningTabungan',
                    'r.Nama',
                    'r.Alamat',
                    'd.Plafond',
                    'd.Tgl',
                    'd.Lama',
                    'd.SukuBunga',
                    'd.Rekening',
                    'd.CaraPerhitungan'
                )
                ->where('d.Rekening', '=', $cRekening)
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('angsuran as a', 'a.Rekening', '=', 'd.Rekening')
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.Ao')
                ->groupBy('d.Rekening')
                ->first();

            if ($vaData) {
                $vaData->BakiDebetAwal = GetterSetter::getBakiDebet($cRekening, $dTgl);
                $nPlafond = $vaData->Plafond;
                $nLama = $vaData->Lama;
                $nSukuBunga = $vaData->SukuBunga;
                $nKe = GetterSetter::getKe($vaData->Tgl, GetterSetter::getTglTransaksi(), $nLama);
                $nBunga = intval($nPlafond * $nSukuBunga / 12 / 100);
                if (GetterSetter::getBakiDebet($cRekening, $dTgl) == 0) {
                    $nPokok = 0;
                } else {
                    $nPokok = intval($nPlafond / $nLama);
                }
                if ($vaData->CaraPerhitungan == '10') {
                    $va = PerhitunganPinjaman::getAnuitasEfektif($nSukuBunga, $nPlafond, $nLama, $nKe);
                    $nPokok = $va['Pokok'];
                }
                $dTgl = Carbon::parse($vaData->Tgl);
                $dTglAkhir = $dTgl->copy()->addMonths($nLama);
                $dJthTmp = $dTglAkhir->format('Y-m-d');
                $cNamaRekTab = '';
                if ($vaData->RekeningTabungan != null || !$vaData->RekeningTabungan || !empty($vaData->RekeningTabungan)) {
                    $vaData2 = DB::table('tabungan')
                        ->select('NamaNasabah')
                        ->where('Rekening', '=', $vaData->RekeningTabungan)
                        ->first();
                    if ($vaData2) {
                        $cNamaRekTab = $vaData2->NamaNasabah;
                    }
                    $nSaldoAkhir = PerhitunganTabungan::getSaldoTabungan($vaData->RekeningTabungan, GetterSetter::getTglTransaksi());
                }

                $vaResult = [
                    'Nama' => $vaData->Nama,
                    // 'BakiDebetAwal' => Func::RoundUp(GetterSetter::getBakiDebet($cRekening, $dTgl), 1),
                    'BakiDebetAwal' => $vaData->BakiDebetAwal,
                    'Pokok' => Func::RoundUp($nPokok, 1),
                    'Bunga' => Func::RoundUp($nBunga, 1),
                    'RekeningTabungan' => $vaData->RekeningTabungan,
                    'NamaRekTabungan' => $cNamaRekTab,
                    'SaldoAkhirTabungan' => $nSaldoAkhir,
                    'Tgl' => Carbon::parse($dTgl)->format('Y-m-d'),
                    'TglJatuhTempo' => $dJthTmp,
                    'Rekening' => $vaData->Rekening,
                    'Plafond' => $nPlafond,
                    'SukuBunga' => $nSukuBunga,
                    'Lama' => $nLama,
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Angsuran', 'getAngsuranData', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Angsuran', 'getAngsuranData', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            // if ($nReqCount > 8 || $nReqCount < 8) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Angsuran', 'store', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            // $vaValidator = validator::make($request->all(), [
            //     'Rekening' => 'required|max:15',
            //     'Tgl' => 'date',
            //     'CaraAngsuran' => 'max:2',
            //     'KPokok' => 'max:16',
            //     'KBunga' => 'max:16',
            //     'Denda' => 'max:16',
            //     'Administrasi' => 'max:16',
            //     'Pinalti' => 'max:16',
            // ]);
            // if ($vaValidator->fails()) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" =>  $vaValidator->errors()
            //     ];
            //     Func::writeLog('Angsuran', 'store', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json(['status' => 'error']);
            // }
            $cRekening = $vaRequestData['Rekening'];
            // $cKas = $vaRequestData['CaraAngsuran'];
            $dTgl = GetterSetter::getTglTransaksi();
            $cStatus = Upd::msAngsuranKredit;
            $nPokok = Func::String2Number($vaRequestData['KPokok']);
            $nBunga = Func::String2Number($vaRequestData['KBunga']);
            $nDenda = Func::String2Number($vaRequestData['Denda']);
            $cKeterangan = $vaRequestData['Keterangan'];
            $nAdministrasi = Func::String2Number($vaRequestData['Administrasi']);
            $nPinalti = Func::String2Number($vaRequestData['Pinalti']);
            $cRekeningPB = "";
            $cFaktur = GetterSetter::getLastFaktur("AG", 6);
            // if ($cKas != 'P') {
            //     $cRekeningPB = "";
            // }

            Upd::updAngsuranKredit(
                $cStatus,
                $cFaktur,
                $dTgl,
                $cRekening,
                $cKeterangan,
                0,
                $nPokok,
                0,
                $nBunga,
                $nDenda,
                '',
                'K', // $cKas,
                true,
                0,
                0,
                $nAdministrasi,
                '',
                0,
                $nPinalti,
                $cRekeningPB,
                $cUser,
                0
            );
            GetterSetter::setLastFaktur('AG');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Angsuran', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Angsuran', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function cetakValidasi(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 2 || $nReqCount < 2 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Angsuran', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $array = [];
            $cRekening = $vaRequestData['Rekening'];
            $cFaktur = $vaRequestData['Faktur'];
            $vaData = DB::table('angsuran AS a')
                ->select(
                    'a.Faktur',
                    'a.Rekening',
                    'a.Tgl',
                    'a.Denda',
                    'a.DPokok',
                    'a.KPokok',
                    'a.DBunga',
                    'a.KBunga',
                    'a.UserName',
                    'a.ID',
                    'a.SimpananWajib',
                    'r.Nama',
                    'd.RekeningLama'
                )
                ->leftJoin('debitur AS d', 'd.Rekening', '=', 'a.Rekening')
                ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
                ->where('a.Rekening', '=', $cRekening)
                ->where('a.Faktur', '=', $cFaktur)
                ->first();
            if ($vaData) {
                $totalAngsuran = $vaData->KPokok + $vaData->KBunga + $vaData->Denda + $vaData->SimpananWajib;
                $bakiDebet = GetterSetter::getBakiDebet($cRekening, $vaData->Tgl);
                $jumlah = "";
                if ($vaData->KPokok > 0) {
                    $jumlah .= "Pokok " . Func::formatCurrency($vaData->KPokok);
                }
                if ($vaData->KBunga > 0) {
                    if ($jumlah) {
                        $jumlah .= " ";
                    }
                    $jumlah .= "Bunga " . Func::formatCurrency($vaData->KBunga);
                }
                if ($vaData->Denda > 0) {
                    if ($jumlah) {
                        $jumlah .= " ";
                    }
                    $jumlah .= "Denda " . Func::formatCurrency($vaData->Denda);
                }
                if ($vaData->SimpananWajib > 0) {
                    if ($jumlah) {
                        $jumlah .= " ";
                    }
                    $jumlah .= "Simpanan Wajib " . Func::formatCurrency($vaData->SimpananWajib);
                }
                $vaResult = [
                    'Faktur' => $vaData->Faktur,
                    'User' => $vaData->UserName,
                    'Rekening' => $vaData->Rekening . ' / ' . $vaData->RekeningLama,
                    'Nama' => $vaData->Nama,
                    'TAngsuran' => Func::formatCurrency($totalAngsuran),
                    'Jumlah' => $jumlah
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Angsuran', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Angsuran', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Angsuran', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function cetakSlip(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 2 || $nReqCount < 2 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Angsuran', 'cetakSlip', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $request->Rekening;
            $cFaktur = $request->Faktur;
            $vaData = DB::table('debitur AS d')
                ->select(
                    'd.Tgl',
                    'd.Lama',
                    'd.RekeningLama',
                    'r.Nama',
                    'r.NoBerkas',
                    'r.Alamat',
                    'd.Plafond',
                    'd.Lama',
                    'o.Nama AS namaAO',
                    'd.SimpananWajib'
                )
                ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('ao AS o', 'o.Kode', '=', 'd.AO')
                ->where('d.Rekening', '=', $cRekening)
                ->first();
            if ($vaData) {
                $dTglRealisasi = $vaData->Tgl;
                $cRekLama = $vaData->RekeningLama;
                $cNama = $vaData->Nama;
                $cAlamat = $vaData->Alamat;
                $nLama = $vaData->Lama;
                $dJthTmp = Carbon::parse($dTglRealisasi)->addMonths($nLama)->format('Y-m-d');
                $nPlafond = $vaData->Plafond;
                $cNamaAO = $vaData->namaAO;
            }
            $vaData2 = DB::table('angsuran AS a')
                ->select(
                    'a.Tgl',
                    'a.KPokok',
                    DB::raw('IFNULL(SUM(a.KBunga + a.KRRA),0) AS KBunga'),
                    'a.KTitipan',
                    'a.BungaTunggakan',
                    'a.BungaPinalty',
                    'a.Denda',
                    'a.Administrasi',
                    'a.DTitipan',
                    'a.Keterangan',
                    DB::raw('m.Jumlah AS SimpananWajib')
                )
                ->leftJoin('mutasianggota AS m', 'm.Faktur', '=', 'a.Faktur')
                // ->where('a.status', 5)
                ->where('a.Faktur', $cFaktur)
                ->first();
            if ($vaData2) {
                $optJenis = 'A';
                $nPokok = $vaData2->KPokok;
                $nBunga = $vaData2->KBunga;
                $nBungaPelunasan = $vaData2->BungaPinalty;
                $nDenda = $vaData2->Denda;
                $nAdmin = $vaData2->Administrasi;
                $nTitipan = $vaData2->KTitipan;
                $dTglSetor = $vaData2->Tgl;
                $nSetoranTitipan = $vaData2->DTitipan;
                $nBakiDebet = GetterSetter::getBakiDebet($cRekening, $dTglSetor);
                $nSimpananWajib = $vaData2->SimpananWajib;
                $nAngsuranKe = GetterSetter::getAngsuranKe($cRekening, $dTglSetor, 'KBunga');
                $vaData3 = DB::table('debitur AS d')
                    ->select('d.Rekening', DB::raw('SUM(a.DPokok - a.KPokok) as BakiDebetAwal'))
                    ->leftJoin('angsuran AS a', function ($join) use ($dTglSetor) {
                        $join->on('a.Rekening', '=', 'd.Rekening')
                            ->where('a.Tgl', '<', $dTglSetor);
                    })
                    ->where('d.Rekening', '=', $cRekening)
                    ->groupBy('d.Rekening')
                    ->first();
                if ($vaData3) {
                    $nBakiAwalDebet = $vaData3->BakiDebetAwal;
                }
                $cKeterangan = $vaData2->Keterangan;
                $va = GetterSetter::getKewajibanPokok($cRekening, $dTglSetor);
                // if ($pokok > 0) {
                //     $kePokok = $va['nKePokok'];
                // } else {
                //     $kePokok = "";
                // }
                if (substr($cFaktur, 0, 1) == "R") {
                    $nTotalAngsuran = $vaData2->KPokok + $vaData2->KBunga + $vaData2->Denda + $vaData2->Administrasi + $nBungaPelunasan;
                } else {
                    $nTotalAngsuran = $vaData2->KPokok + $vaData2->KBunga + $vaData2->Denda + $vaData2->Administrasi + $nBungaPelunasan + $nSimpananWajib;
                }
                $nNetto = $nTotalAngsuran - $nTitipan;

                $vaResult = [
                    'AngsuranPokok' => $nPokok,
                    'AngsuranBunga' => $nBunga,
                    'TglTransaksi' => GetterSetter::getTglTransaksi(),
                    // 'PinaltyPelunasan' => $nBungaPelunasan,
                    'Denda' => $nDenda,
                    'Admin' => $vaData2->Administrasi,
                    // 'SimpananWajib' => $nSimpananWajib,
                    'Jumlah' => $nTotalAngsuran,
                    'AngsuranKe' => $nAngsuranKe,
                    'Plafond' => $nPlafond,
                    'SaldoPlafond' => $nBakiDebet,
                    'JangkaWaktu' => $nLama . ' Bulan',
                    'TglPinjam' => $dTglRealisasi,
                    'NamaAO' => $cNamaAO,
                    'JthTmp' => $dJthTmp,
                    'Terbilang' => Func::Terbilang(round(Func::String2Number($nTotalAngsuran), 0)),
                    'Nama' => $cNama,
                    'Alamat' => $cAlamat
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Angsuran', 'cetakSlip', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Angsuran', 'cetakSlip', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
