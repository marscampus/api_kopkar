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
 * Created on Wed Dec 06 2023 - 16:23:44
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\Teller;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiSimpananController extends Controller
{
    public function getRekTabungan(Request $request)
    {
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
            Func::writeLog('Mutasi Simpanan', 'getRekTabungan', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $dTglBayar = GetterSetter::getTglTransaksi();
        $vaData = DB::table('tabungan')
            ->select(
                'Close',
                'StatusOtorisasi'
            )
            ->where('Rekening', '=', $vaRequestData['Rekening'])
            ->first();
        if ($vaData) {
            if ($vaData->Close == '0') {
                if ($vaData->StatusOtorisasi == '1') {
                    $vaData1 = DB::table('mutasitabungan')
                        ->select('Faktur')
                        ->where('Tgl', '=', $dTglBayar)
                        ->first();
                    if ($vaData1) {
                        $vaRetVal = [
                            "status" => "03",
                            "message" => "MUTASI LEBIH DARI 1 KALI " . $vaData1->Faktur . ' !'
                        ];
                        Func::writeLog('Mutasi Simpanan', 'getRekTabungan', $vaRequestData, $vaRetVal, $cUser);
                        // return response()->json($vaRetVal);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Mutasi Lebih Dari 1 Kali ' . $vaData1->Faktur . ' !'
                        ]);
                    }
                } else {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "REKENING SIMPANAN BELUM DIOTORISASI!"
                    ];
                    Func::writeLog('Mutasi Simpanan', 'getRekTabungan', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Rekening Tabungan Belum Diotorisasi!'
                    ]);
                }
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "REKENING SIMPANAN TELAH DITUTUP!'"
                ];
                Func::writeLog('Mutasi Simpanan', 'getRekTabungan', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening Simpanan Telah Ditutup!'
                ]);
            }
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "Rekening Simpanan Tidak Ditemukan!"
            ];
            Func::writeLog('Mutasi Simpanan', 'getRekTabungan', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening Tabungan Tidak Ditemukan!'
            ]);
        }
    }

    public function getDataSimpanan(Request $request)
    {
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
            Func::writeLog('Mutasi Simpanan', 'getDataSimpanan', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $cRekening = $vaRequestData['Rekening'];
        $dTgl = GetterSetter::getTglTransaksi();
        $nSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($cRekening, $dTgl);
        $vaData = DB::table('tabungan as t')
            ->select('g.SaldoMinimum', 't.JumlahBlokir', 'g.SetoranMinimum')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->where('t.Rekening', $cRekening)
            ->first();
        if ($vaData) {
            $vaResult = [
                "Faktur" => GetterSetter::getLastFaktur("TB", 6),
                "SaldoMinimum" => $vaData->SaldoMinimum,
                "SetoranMinimum" => $vaData->SetoranMinimum,
                "Blokir" => $vaData->JumlahBlokir,
                "SaldoEfektif" => $nSaldoTabungan - ($vaData->JumlahBlokir + $vaData->SetoranMinimum),
                "SaldoAwal" => $nSaldoTabungan,
                "SaldoAkhir" => $nSaldoTabungan
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Simpanan', 'getDataSimpanan', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "DATA TIDAK DITEMUKAN"
            ];
            Func::writeLog('Mutasi Simpanan', 'getDataSimpanan', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }

    public function getMutasi(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['page']);
        unset($vaRequestData['auth']);
        $nReqCount = count($vaRequestData);
        if ($nReqCount > 6 || $nReqCount < 6) {
            $vaRetVal = ["status" => "99", "message" => "REQUEST TIDAK VALID"];
            Func::writeLog('Mutasi Simpanan', 'getMutasi', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $nMutasi = $vaRequestData['Mutasi'];
        $cDK = $vaRequestData['DK'];
        $cNamaKodeTransaksi = $vaRequestData['KetKodeTransaksi'];
        $cRekening = $vaRequestData['Rekening'];
        $cNama = $vaRequestData['Nama'];
        $nSaldoAwal = $vaRequestData['SaldoAwal'];
        if ($cDK == "D") {
            $nMutasi = $nSaldoAwal - $nMutasi;
        }
        $nSaldoAkhir = $nSaldoAwal + $nMutasi;
        $vaResult = [
            "Keterangan" => $cNamaKodeTransaksi . ' [' . $cRekening . '] ' . ' ' . $cNama,
            "SaldoAkhir" => $nSaldoAkhir,
            "Mutasi" => $nMutasi
        ];
        // JIKA REQUEST SUKSES
        $vaRetVal = [
            "status" => "00",
            "message" => $vaResult
        ];
        Func::writeLog('Mutasi Simpanan', 'getMutasi', $vaRequestData, $vaRetVal, $cUser);
        return response()->json($vaResult);
    }

    public function getDataTable(Request $request)
    {
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
            Func::writeLog('Mutasi Simpanan', 'getMutasi', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $cRekening =  $vaRequestData['Rekening'];
        $dTgl = GetterSetter::getTglTransaksi();
        $vaData = DB::table('mutasitabungan')
            ->select(
                'Faktur',
                'Tgl',
                'KodeTransaksi',
                'Debet',
                'Kredit',
                'Keterangan'
            )
            ->where('Rekening', '=', $cRekening)
            ->where('Tgl', '<=', $dTgl)
            ->orderBy('Tgl', 'asc') // Tambahkan order by Tgl
            // ->orderBy('Tgl', 'Faktur')
            // ->orderByDesc('Tgl', 'Faktur')
            ->limit(10)
            ->get();
        if (count($vaData) > 0) {
            $vaResults = [];
            
            // foreach ($vaData as $d) {
            //     $vaResult = [
            //         "NoTransaksi" => $d->Faktur,
            //         "Tgl" => $d->Tgl,
            //         "Kode" => $d->KodeTransaksi,
            //         "Debet" => $d->Debet,
            //         "Kredit" => $d->Kredit,
            //         "Saldo" => $nSaldoTabungan,
            //         "Keterangan" => $d->Keterangan
            //     ];
            //     $nSaldoTabungan -= $d->Kredit - $d->Debet;
            //     $vaResults[] = $vaResult;
            // }
            $nSaldoTabungan = 0;//$vaData->Debet;//PerhitunganTabungan::getSaldoTabungan($cRekening, $vaData->Tgl);
            foreach ($vaData as $d) {
                $nSaldoTabungan +=  $d->Kredit - $d->Debet; // Ubah urutan debet dan kredit

                $vaResult = [
                    "NoTransaksi" => $d->Faktur,
                    "Tgl" => $d->Tgl,
                    "Kode" => $d->KodeTransaksi,
                    "Debet" => $d->Debet,
                    "Kredit" => $d->Kredit,
                    "Saldo" => $nSaldoTabungan,
                    "Keterangan" => $d->Keterangan
                ];
                // Perubahan pada perhitungan saldo
                $vaResults[] = $vaResult;
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Mutasi Simpanan', 'getMutasi', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "DATA TIDAK DITEMUKAN"
            ];
            Func::writeLog('Mutasi Simpanan', 'getMutasi', $vaRequestData, $vaRetVal, $cUser);
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
            if ($nReqCount < 9 || $nReqCount > 9) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = $vaRequestData['Tgl'];
            $nMutasi = $vaRequestData['Mutasi'];
            $cKodeTransaksi = $vaRequestData['KodeTransaksi'];
            $cKeterangan = $vaRequestData['Keterangan'];
            $vaData = DB::table('tabungan')
                ->where('Rekening', '=', $cRekening)
                ->exists();
            if (!$vaData) {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "REKENING TABUNGAN TIDAK VALID!"
                ];
                Func::writeLog('Mutasi Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening Tabungan Tidak Valid!'
                ]);
            }
            if ($nMutasi > 0) {
                $cFaktur = GetterSetter::getLastFaktur("TB", 6);
                Upd::updMutasiTabungan($cFaktur, $dTgl, $cRekening, $cKodeTransaksi, $cKeterangan, $nMutasi, '', true, $cUser);
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => "SUKSES"
                ];
                GetterSetter::setLastFaktur("TB");
                Func::writeLog('Mutasi Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                return response()->json(['status' => 'success']);
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
            Func::writeLog('Pembukaan Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function cetakValidasi(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 2 || $nReqCount < 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Simpanan', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $cFaktur = $vaRequestData['Faktur'];
            $vaData = DB::table('mutasitabungan as mt')
                ->select(
                    'mt.Faktur',
                    'mt.Rekening',
                    'mt.DK',
                    'mt.Jumlah',
                    'mt.KodeTransaksi',
                    'mt.UserName',
                    'mt.Keterangan',
                    'mt.Kredit',
                    'mt.Debet',
                    't.RekeningLama',
                    'mt.UserACC',
                    'r.Nama',
                    't.Tgl'
                )
                ->leftJoin('tabungan as t', 't.Rekening', '=', 'mt.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->where('mt.Rekening', '=', $cRekening)
                ->where('mt.Faktur', '=', $cFaktur)
                ->first();
            $vaResult = [];
            if ($vaData) {
                $vaResult = [
                    'Faktur' => $vaData->Faktur,
                    'User' => $cUser,
                    'Rekening' => $vaData->Rekening . ' / ' . $vaData->RekeningLama,
                    'Nama' => $vaData->Nama,
                    'KodeTransaksi' => $vaData->KodeTransaksi . ' ' . GetterSetter::getKeterangan($vaData->KodeTransaksi, 'Keterangan', 'kodetransaksi'),
                    'Jumlah' => $vaData->Jumlah,
                    'Tgl' => $vaData->Tgl,
                    'SaldoAkhir' => PerhitunganTabungan::getSaldoTabungan($cRekening, $vaData->Tgl),
                    'Jenis' => $vaData->DK,
                    'Terbilang' => Func::Terbilang(round(Func::String2Number($vaData->Jumlah), 0))
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Simpanan', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Mutasi Simpanan', 'cetakValidasi', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
