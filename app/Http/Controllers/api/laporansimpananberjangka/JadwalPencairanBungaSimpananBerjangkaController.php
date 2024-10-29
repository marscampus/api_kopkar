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
 * Created on Fri Jan 05 2024 - 03:45:45
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporansimpananberjangka;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use App\Models\simpananberjangka\Deposito;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;

class JadwalPencairanBungaSimpananBerjangkaController extends Controller
{
    public function data(Request $request)
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
            $dTglAkhirBulanKemarin = Func::EOM(date('Y-m-d', Date::nextMonth(Func::Tgl2Time($dTglAwal), -1)));
            $dTglAkhir = Func::Date2String($vaRequestData['TglAkhir']);
            $nHariAwal = date('d', Func::Tgl2Time($dTglAwal));
            $nHariAkhir = date('d', Func::Tgl2Time($dTglAkhir));
            if ($dTglAkhir == Func::Date2String(Func::EOM($dTglAkhir))) {
                $nHariAkhir = 31;
            }
            $vaData = DB::table('deposito as d')
                ->select(
                    'd.Kode',
                    'd.Rekening',
                    'd.CaraPerpanjangan',
                    DB::raw('day(d.tgl) as hari'),
                    'd.Tgl',
                    'd.NoBilyet',
                    'r.Nama',
                    'd.RekeningTabungan',
                    'd.Kode',
                    'd.RekeningLama',
                    'd.GolonganDeposito',
                    'd.ARO',
                    'g.Keterangan as NamaGolonganDeposito',
                    'd.GolonganDeposan',
                    'g.Lama',
                    DB::raw('sum(m.setoranplafond - pencairanplafond) as Nominal'),
                    'd.RekeningPB',
                    'd.KeteranganRekeningPB'
                )
                ->leftJoin('tabungan as t', 't.rekening', '=', 'd.RekeningTabungan')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
                ->leftJoin('golongandeposito as g', 'g.kode', '=', 'd.golongandeposito')
                ->leftJoin('cabang as c', function ($join) use ($dTglAkhir) {
                    $join->on('c.Kode', '=', DB::raw('(select CabangEntry from deposito_cabang where Rekening = d.Rekening and tgl <= "' . $dTglAkhir . '" order by tgl desc limit 1)'))
                        ->orWhere('c.Kode', '=', DB::raw('d.CabangEntry'));
                })
                ->leftJoin('mutasideposito as m', 'm.Rekening', '=', 'd.Rekening')
                ->where('m.Tgl', '<=', $dTglAkhir)
                ->whereDay('d.Tgl', '>=', $nHariAwal)
                ->whereDay('d.Tgl', '<=', $nHariAkhir)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy('d.Rekening')
                ->havingRaw('Nominal > 0')
                ->orderByRaw('day(d.Tgl), d.Rekening')
                ->get();

            $nNo = 0;
            $nTotalNominal = 0;
            $nTotalBunga = 0;
            $nTotalPajak = 0;
            $nTotalBungaNetto = 0;
            $nMaxRow = 0;
            $nBungaNetto = 0;
            $nPajak = 0;
            $nBunga = 0;
            $nDeposito = 0;
            $nAccrual = 0;
            $nTotalPencairanDepositoReport = 0;
            foreach ($vaData as $d) {
                $nMaxRow++;
                $cRekening = $d->Rekening;
                $nNominal = $d->Nominal;
                $dJthTmp = PerhitunganDeposito::getTglJthTmpDeposito($cRekening, $dTglAkhirBulanKemarin);
                $vaBungaDeposito = PerhitunganDeposito::getPencairanDeposito($cRekening, $dTglAkhir);
                $nBunga = $vaBungaDeposito['Bunga'];
                $nAccrual = $vaBungaDeposito['Accrual'];
                $nPajak = $vaBungaDeposito['Pajak'];
                $nBungaNetto = $nBunga - $nPajak;
                $nSukuBunga = GetterSetter::getRate($dTglAkhir, $cRekening);

                $cRekeningTabungan = "";
                if (empty($d->RekeningTabungan)) {
                    $cRekeningTabungan = "Tunai";
                    if ($d->ARO == 'P') {
                        $cRekeningTabungan = "ARO P+I";
                    }
                } else {
                    $cRekeningTabungan = $d->RekeningTabungan;
                }

                if (!empty($d->RekeningPB)) {
                    $cRekeningTabungan = $d->KeteranganRekeningPB;
                }

                $vaTglValuta = explode("-", $d->Tgl);
                $nTotalPencairanDepositoReport += $d->Nominal;

                $dTglAro = date('Y-m-d', Date::nextMonth(strtotime($dJthTmp), -$d->Lama));
                $nHariAro = date('d', Func::Tgl2Time($dTglAro));
                $nBulanAro = date('m', Func::Tgl2Time($dTglAro));
                $nTahunAro = date('Y', Func::Tgl2Time($dTglAro));
                $nHariValuta = date('d', Func::Tgl2Time($d->Tgl));

                if ($nHariValuta >= $nHariAro) {
                    $nHariAro = $nHariValuta;
                }

                $dTglAro = date('Y-m-d', mktime(0, 0, 0, $nBulanAro, $nHariAro, $nTahunAro));
                // $dTglAro = GetterSetter::getTglARO($cRekening, $dTglAkhirBulanKemarin);

                $dTglValuta = $d->Tgl;
                $dTglValutaCarbon = Carbon::parse($dTglValuta);
                $dDayValuta = $dTglValutaCarbon->format('d');

                $dTglAkhirCarbon = Carbon::parse($dTglAkhir)->addHour(7);
                $dMonthAkhir = $dTglAkhirCarbon->format('m');
                $dYearAkhir = $dTglAkhirCarbon->format('Y');

                $dTglCustom = $dYearAkhir . '-' . $dMonthAkhir . '-' . $dDayValuta;
                Carbon::setLocale('id');
                $carbonTglCustom = Carbon::parse($dTglCustom);
                $cDayCustom = $carbonTglCustom->isoFormat('dddd');
                $vaResult[] = [
                    'No' => ++$nNo,
                    'GolTgl' => $dDayValuta . ' - ' . $cDayCustom,
                    'CIF' => $d->Kode,
                    'Rekening' => $d->Rekening,
                    'Nama' => $d->Nama,
                    'Lama' => $d->Lama,
                    'RekTabungan' => $cRekeningTabungan,
                    'ARO' => $d->ARO == "Y" ? "Y" : "T",
                    'Rate' => $nSukuBunga,
                    'Valuta' => $d->Tgl,
                    'TglAro' => $dTglAro,
                    'JthTmp' => $dJthTmp,
                    'Nominal' => $nNominal,
                    'Bunga' => $nBunga,
                    'Pajak' => $nPajak,
                    'BungaNetto' => $nBungaNetto
                ];

                $nTotalNominal += $nNominal;
                $nTotalBunga = +$nBunga;
                $nTotalPajak = +$nPajak;
                $nTotalBungaNetto = +$nBungaNetto;

                $vaTotal = [
                    'TotalNominal' => $nTotalNominal,
                    'TotalBunga' => $nTotalBunga,
                    'TotalPajak' => $nTotalPajak,
                    'TotalBungaNetto' => $nTotalBungaNetto
                ];
            }


            $vaNumericArray = array_values($vaResult);
            $vaResults = [
                'data' => $vaNumericArray,
                'total_data' => count($vaNumericArray),
                'totals' => $vaTotal
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResults
            ];
            Func::writeLog('Jadwal Pencairan Bunga Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResults);
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
            Func::writeLog('Jadwal Pencairan Bunga Simpanan Berjangka', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
