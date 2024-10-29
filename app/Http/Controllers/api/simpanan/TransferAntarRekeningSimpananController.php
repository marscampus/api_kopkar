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
 * Created on Tue Dec 19 2023 - 11:21:10
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferAntarRekeningSimpananController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);

            if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($request['Rekening'])) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];

                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request Tidak Valid !'
                ]);
            }

            $cRekening = $request['Rekening'];
            $nSaldoMinimum = 0;
            $nSetoranMinimum = 0;

            // Validasi Golongan Tabungan
            $cGolongan = GetterSetter::getGolongan($cRekening);

            $vaData = DB::table('golongantabungan')
                ->select(
                    'Kode',
                    'Keterangan',
                    'SaldoMinimum',
                    'SetoranMinimum'
                )
                ->where('Kode', '=', $cGolongan)
                ->first();

            if ($vaData) {
                $cGolTabungan = $vaData->Kode;
                $cKetGolTabungan = $vaData->Keterangan;
                $nSaldoMinimum = ($vaData->SaldoMinimum !== null) ? $vaData->SaldoMinimum : 0;
                $nSetoranMinimum = ($vaData->SetoranMinimum !== null) ? $vaData->SetoranMinimum : 0;
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "GOLONGAN SIMPANAN TIDAK DITEMUKAN"
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Golongan Simpanan Tidak Ditemukan!'
                ]);
            }

            $vaData2 = DB::table('tabungan')
                ->select('SaldoAkhir')
                ->where('Rekening', '=', $cRekening)
                ->first();
            if ($vaData2) {
                $nSaldoAwal = $vaData2->SaldoAkhir;
            } else {
                $vaRetVal = [
                    'status' => '03',
                    'message' => 'NO. REKENING TIDAK TERDAFTAR'
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => 'NO. REKENING TIDAK TERDAFTAR'
                ]);
            }

            // Validasi Nasabah
            $cKode = GetterSetter::getKode($cRekening);
            $vaData3 = DB::table('registernasabah')
                ->select(
                    'Nama',
                    'Alamat'
                )
                ->where('Kode', '=', $cKode)
                ->first();
            if ($vaData3) {
                $cNama = $vaData3->Nama;
                $cAlamat = $vaData3->Alamat;
            } else {
                $vaRetVal = [
                    'status' => "03",
                    'message' => 'NO. ANGGOTA TIDAK DITEMUKAN'
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json([
                    'status' => 'error',
                    'message' => 'NO. REKENING TIDAK TERDAFTAR'
                ]);
            }

            // Get Nomor Transaksi
            $cFaktur = GetterSetter::getLastFaktur('TB', 7);
            $cKeterangan = "Transfer" . " [" . $cRekening . "] " . $cNama;

            // Validasi Nomor getRekening
            $vaData4 = DB::table('tabungan')
                ->select(
                    'Close',
                    'StatusBlokir',
                    'JumlahBlokir',
                    'SaldoAkhir'
                )
                ->where('Rekening', '=', $cRekening)
                ->first();
            if ($vaData4) {
                $nSaldoAwal = PerhitunganTabungan::getSaldoTabungan($cRekening, GetterSetter::getTglTransaksi());
                $cClose = $vaData4->Close;
                $cStatusBlokir = $vaData4->StatusBlokir;
                $nSaldoAvail = $nSaldoAwal - $vaData4->JumlahBlokir + $nSaldoMinimum;
                if ($cClose == '1') {
                    $vaRetVal = [
                        'status' => "03",
                        'message' => 'NO. REKENING ASAL SUDAH DITUTUP'
                    ];
                    Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return response()->json(
                        ['status' => 'error', 'message' => 'NO. REKENING ASAL SUDAH DITUTUP!']
                    );
                }

                if ($cStatusBlokir > 1) {
                    if ($nSaldoAvail <= 0) {
                        $vaRetVal = [
                            'status' => "03",
                            'message' => "SALDO SUDAH DIBLOKIR KESELURUHAN"
                        ];
                        Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                        // return response()->json($vaRetVal);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'SALDO SUDAH DIBLOKIR KESELURUHAN'
                        ]);
                    }
                }
            }

            $vaResult = [
                'Rekening' => $cRekening,
                'Nama' => $cNama,
                'Alamat' => $cAlamat,
                'NoTransaksi' => $cFaktur,
                'GolTabungan' => $cGolTabungan,
                'KetGolTabungan' => $cKetGolTabungan,
                'SaldoAwal' => $nSaldoAwal,
                'SaldoMinimum' => $nSaldoMinimum,
                'SaldoBlokir' => $vaData4->JumlahBlokir,
                'SaldoEfektif' => $nSaldoAvail,
                'SetoranMin' => $nSetoranMinimum,
                'SaldoAkhir' => $vaData4->SaldoAkhir,
                'Keterangan' => $cKeterangan
            ];

            // JIKA REQUEST SUKSES
            $vaRetVal = $vaResult;
            Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
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
                    // tambahkan informasi lainnya yang ingin Anda sertakan
                ]
            ];
            Func::writeLog('Tranfer Antar Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }

    public function getRekeningTujuan(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            // if ($nReqCount > 3 || $nReqCount < 3) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            // if (
            //     $vaRequestData['Rekening'] == null
            //     || $vaRequestData['RekeningTujuan'] == null
            //     || empty($vaRequestData['Rekening'])
            //     || empty($vaRequestData['RekeningTujuan'])
            // ) {
            //     $vaRetVal = ["status" => "99", "message" => "REQUEST TIDAK VALID"];
            //     Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json(['status' => 'error']);
            // }

            // Kedua Rekening Tidak Boleh Sama
            $cRekening = $vaRequestData['Rekening'];
            $cRekeningTujuan = $vaRequestData['RekeningTujuan'];
            $cGolongan = GetterSetter::getGolongan($cRekeningTujuan);
            if ($cRekening == $cRekeningTujuan) {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "NO. REKENING TUJUAN TIDAK BOLEH SAMA DENGAN NO. REKENING ASAL"
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(
                    ['status' => 'error', 'message' => 'No. Rekening Tujuan Tidak Boleh Sama Dengan No. Rekening Asal!']
                );
            }

            // Validasi Golongan Tabungan Tujuan
            $nNilaiTransfer = $vaRequestData['NilaiTransfer'];
            $vaData = DB::table('golongantabungan')
                ->select('SetoranMinimum')
                ->where('Kode', '=', $cGolongan)
                ->first();
            if ($vaData) {
                $nSetoranMinimum = $vaData->SetoranMinimum;
                if ($nNilaiTransfer < $nSetoranMinimum) {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "NILAI TRANSFER LEBIH KECIL DARI SETORAN MINIMUM PADA REKENING TUJUAN"
                    ];
                    Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => 'Nilai Transfer Lebih Kecil Dari Setoran Minimum Pada Rekening Tujuan!'
                        ]
                    );
                }
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "GOLONGAN TABUNGAN TUJUAN TIDAK TERDAFTAR"
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(
                    [
                        'status' => 'error', 'message' => 'Golongan Tabungan Tujuan Tidak Terdaftar!'
                    ]
                );
            }

            // Validasi Nasabah
            $cKode = GetterSetter::getKode($cRekeningTujuan);
            $vaData2 = DB::table('registernasabah')
                ->select(
                    'Nama',
                    'Alamat'
                )
                ->where('Kode', '=', $cKode)
                ->first();
            if ($vaData2) {
                $cNama = $vaData2->Nama;
                $cAlamat = $vaData2->Alamat;
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "NO. REKENING TUJUAN TIDAK TERDAFTAR"
                ];
                Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'No.Rekening Tujuan Tidak Terdaftar!'
                    ],

                );
            }

            // Get Saldo
            $vaData3 = DB::table('tabungan')
                ->select(
                    'SaldoAkhir',
                    'Close'
                )
                ->where('Rekening', '=', $cRekeningTujuan)
                ->first();
            if ($vaData3) {
                $nSaldoAkhir = $vaData3->SaldoAkhir;
                $nSaldoAkhir2 = $vaRequestData['NilaiTransfer'] + $nSaldoAkhir;
                if ($vaData3->Close == '1') {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "REKENING TUJUAN SUDAH DITUTUP"
                    ];
                    Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
                    // return response()->json($vaRetVal);
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => 'Rekening Tujuan Sudah Ditutup!'
                        ]
                    );
                }
            }
            $vaResult = [
                'RekeningTujuan' => $cRekeningTujuan,
                'NamaTujuan' => $cNama,
                'AlamatTujuan' => $cAlamat,
                'SaldoAwalTujuan' => $nSaldoAkhir,
                'SaldoAkhirTujuan' => $nSaldoAkhir2
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
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
                    // tambahkan informasi lainnya yang ingin Anda sertakan
                ]
            ];
            Func::writeLog('Transfer Antar Rekening Simpanan', 'getRekeningTujuan', $vaRequestData, $vaRetVal, $cUser);
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
            $nReqCount = count($vaRequestData);
            $cRekening = $vaRequestData['Rekening'];
            $cRekTujuan = $vaRequestData['RekeningTujuan'];
            $cNama = $vaRequestData['Nama'];
            $cKeterangan = "Transfer" . " [" . $cRekening . "] " . $cNama;

            // Validasi Register Nasabah
            $cKode = GetterSetter::getKode($cRekening);
            $vaData = DB::table('registernasabah')
                ->select('Kode')
                ->where('Kode', '=', $cKode)
                ->first();
            if (!$vaData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening Nasabah Tidak Ditemukan!'
                ]);
            }

            // Validasi Register Rekening Tujuan
            $cKodeTujuan = GetterSetter::getKode($cRekTujuan);
            $vaData2 = DB::table('registernasabah')
                ->select('Kode')
                ->where('Kode', '=', $cKodeTujuan)
                ->first();

            if (!$vaData2) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening Tujuan Tidak Ditemukan!'
                ]);
            }

            // Validasi Tabungan Tujuan
            $vaData3 = DB::table('tabungan')
                ->select('Rekening')
                ->where('Rekening', '=', $cRekening)
                ->first();

            if (!$vaData3) {
                return response()->json(['status' => 'error', 'message' => 'Rekening Asal Tidak Ditemukan!']);
            }

            // Cek Nilai Transfer
            $nNilaiTransfer = $vaRequestData['NilaiTransfer'];
            $nSaldoMinimum = $vaRequestData['SaldoMinimum'];
            $nSetoranMinimum = $vaRequestData['SetoranMinimum'];
            $nSaldoAwal = $vaRequestData['SaldoAwal'];
            $nSaldoAkhir = $nSaldoAwal - $nNilaiTransfer;

            if ($nNilaiTransfer > $nSaldoAwal || $nNilaiTransfer < $nSetoranMinimum || $nSaldoAkhir < $nSaldoMinimum) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nilai Transfer Belum Diisi Atau Tidak Sesuai Dengan Saldo Yang Ada!'
                ]);
            }

            // Setelah Melewati Semua Validasi
            $cPenarikan = GetterSetter::getDBConfig("msKodePenarikanPB");
            $cSetoran = GetterSetter::getDBConfig("msKodeSetoranPB");
            $cFaktur = GetterSetter::getLastFaktur("TB", 7);

            Upd::updMutasiTabungan($cFaktur, $vaRequestData['Tgl'], $cRekening, $cPenarikan, $cKeterangan, $nNilaiTransfer);
            Upd::updMutasiTabungan($cFaktur, $vaRequestData['Tgl'], $cRekTujuan, $cSetoran, 'Terima ' . $cKeterangan, $nNilaiTransfer);
            GetterSetter::setLastFaktur('TB');
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Transfer Antar Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Transfer Antar Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
