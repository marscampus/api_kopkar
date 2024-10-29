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
 * Created on Wed Jan 03 2024 - 15:53:12
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpananberjangka;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimpananBerjangkaJatuhTempoController extends Controller
{
    public static function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTglAwal = Func::Date2String($vaRequestData['TglAwal']);
            $dTglAkhir = Func::Date2String($vaRequestData['TglAkhir']);
            $dTglAkhirBulanLalu = Func::BOM($dTglAkhir);
            $dTglAkhirBulanLalu = Func::Date2String(Func::EOM(date('Y-m-d', Date::nextMonth(Func::Tgl2Time($dTglAkhirBulanLalu), -1))));
            $vaData = DB::table('deposito as d')
                ->select(
                    'd.Rekening',
                    'd.RekeningLama',
                    'r.Nama',
                    'r.Alamat',
                    'g.Lama',
                    'd.SukuBunga',
                    'd.Tgl',
                    DB::raw('SUM(m.setoranplafond - m.pencairanplafond) as Nominal'),
                    'd.GolonganDeposito',
                    'g.Keterangan as NamaGolonganDeposito'
                )
                ->leftJoin('mutasideposito as m', 'm.Rekening', '=', 'd.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('golongandeposito as g', 'g.Kode', '=', 'd.GolonganDeposito')
                ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->where('m.Tgl', '<=', $dTglAkhir)
                ->groupBy('d.Rekening')
                ->having('Nominal', '>', 0)
                ->orderBy('d.GolonganDeposito')
                ->orderBy('d.Rekening')
                ->get();
            $nRow = 0;
            $nTotalNominal = 0;
            if ($vaData->count() > 0) {
                $vaResults = [];
                $vaResult = [];
                foreach ($vaData as $d) {
                    $nNominal = $d->Nominal;
                    $cRekening = $d->Rekening;
                    $nSukuBunga = GetterSetter::getRate($dTglAkhir, $cRekening);
                    $dJthTmp = PerhitunganDeposito::getTglJthTmpDeposito($cRekening, $dTglAkhirBulanLalu);
                    $dJthTmp = Func::Date2String($dJthTmp);
                    if ($dJthTmp >= $dTglAwal && $dJthTmp <= $dTglAkhir) {
                        $vaResult[] = [
                            'No' => ++$nRow,
                            'GolDeposito' => $d->GolonganDeposito . ' - ' . $d->NamaGolonganDeposito,
                            'Rekening' => $cRekening,
                            'RekLama' => $d->RekeningLama,
                            'Nama' => $d->Nama,
                            'Alamat' => $d->Alamat,
                            'SukuBunga' => $nSukuBunga,
                            'TglValuta' => Func::String2Date($d->Tgl),
                            'JthTmp' => Func::String2Date($dJthTmp),
                            'Nominal' => $nNominal
                        ];
                        $nTotalNominal += $nNominal;
                    }
                    $vaResults = [
                        'data' => $vaResult,
                        'total_data' => count($vaResult),
                        'totals' => $nTotalNominal
                    ];
                }
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResults
                ];
                Func::writeLog('Simpanan Berjangka Jatuh Tempo', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResults);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Simpanan Berjangka Jatuh Tempo', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Simpanan Berjangka Jatuh Tempo', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
