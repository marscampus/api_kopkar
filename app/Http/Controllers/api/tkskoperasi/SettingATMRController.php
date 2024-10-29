<?php

namespace App\Http\Controllers\api\tkskoperasi;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingATMRController extends Controller
{
    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {

            $vaData = DB::table('tks_atmr')
                ->select('Kode', 'Keterangan', 'Persen', 'Rekening')
                ->get();
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Setting ATMR', 'data', $vaRequestData, $vaRetVal, $cUser);
                $vaArray = [
                    "data" => $vaData,
                    "total" => $vaData->count()
                ];
                return response()->json($vaArray);
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

            Func::writeLog('Setting ATMR', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function data1(Request $request)
    {
        $vaArray = [
            "SimpananPokok" => GetterSetter::getDBConfig("msSimpananPokokATMR"),
            "SimpananWajib" => GetterSetter::getDBConfig("msSimpananWajibATMR"),
            "ModalPenyetaraan" => GetterSetter::getDBConfig("msModalPenyetaraanATMR"),
            "ModalSumbanganHibah" => GetterSetter::getDBConfig("msModalSumbanganHibahATMR"),
            "CadanganUmum" => GetterSetter::getDBConfig("msCadanganUmumATMR"),
            "CadanganTujuanResiko" => GetterSetter::getDBConfig("msCadanganTujuanResikoATMR"),
            "JumlahSHUBelumDibagi" => GetterSetter::getDBConfig("msJumlahSHUBelumDibagi"),
            "Kas" => GetterSetter::getDBConfig("msKasATMR"),
            "Bank" => GetterSetter::getDBConfig("msBankATMR"),
            "SimpananBerjangka" => GetterSetter::getDBConfig("msSimpananBerjangkaATMR"),
            "SimpananSukarelaKopLain" => GetterSetter::getDBConfig("msSimpananSukarelaKopLainATMR"),
            "SimpananBerjangkaKopLain" => GetterSetter::getDBConfig("msSimpananBerjangkaKopLainATMR"),
            "SuratBerharga" => GetterSetter::getDBConfig("msSuratBerhargaATMR"),
            "PiutangPinjamanAnggota" => GetterSetter::getDBConfig("msPiutangPinjamanAnggotaATMR"),
            "PiutangPinjamanNonAnggota" => GetterSetter::getDBConfig("msPiutangPinjamanNonAnggotaATMR"),
            "PiutangPinjamanKopLain" => GetterSetter::getDBConfig("msPiutangPinjamanKopLainATMR"),
            "PenyisihanPiutangTakTertagih" => GetterSetter::getDBConfig("msPenyisihanPiutangTakTertagihATMR"),
            "BebanDibayarDimuka" => GetterSetter::getDBConfig("msBebanDibayarDimukaATMR"),
            "PendapatanAkanDiterima" => GetterSetter::getDBConfig("msPendapatanAkanDiterimaATMR"),
            "AktivaLancarLainnya" => GetterSetter::getDBConfig("msAktivaLancarLainnyaATMR"),
            "PenyertaanKopSekunder" => GetterSetter::getDBConfig("msPenyertaanKopSekunderATMR"),
            "InvestasiSaham" => GetterSetter::getDBConfig("msInvestasiSahamATMR"),
            "InvestasiJangkaPanjangLain" => GetterSetter::getDBConfig("msInvestasiJangkaPanjangLainATMR"),
            "HartaTetap" => GetterSetter::getDBConfig("msHartaTetapATMR"),
            "AkumulasiPenyusutanHartaTetap" => GetterSetter::getDBConfig("msAkumulasiPenyusutanHartaTetapATMR"),
            "AktivaLainLain" => GetterSetter::getDBConfig("msAktivaLainLainATMR")
        ];
        return response()->json($vaArray);
    }

    public function store(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $vaArray = [
                "Kode" => $vaRequestData['Kode'],
                "Keterangan" => $vaRequestData['Keterangan'],
                "Persen" => $vaRequestData['Persen'],
                "Rekening" => $vaRequestData['Rekening'] || ""
            ];
            DB::table('tks_atmr')->insert($vaArray);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 'error']);
        }
    }

    public function store1(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $user =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            GetterSetter::setDBConfig("msSimpananPokokATMR", $vaRequestData['SimpananPokok']);
            GetterSetter::setDBConfig("msSimpananWajibATMR", $vaRequestData['SimpananWajib']);
            GetterSetter::setDBConfig("msModalPenyetaraanATMR", $vaRequestData['ModalPenyetaraan']);
            GetterSetter::setDBConfig("msModalSumbanganHibahATMR", $vaRequestData['ModalSumbanganHibah']);
            GetterSetter::setDBConfig("msCadanganUmumATMR", $vaRequestData['CadanganUmum']);
            GetterSetter::setDBConfig("msCadanganTujuanResikoATMR", $vaRequestData['CadanganTujuanResiko']);
            GetterSetter::setDBConfig("msJumlahSHUBelumDibagiATMR", $vaRequestData['JumlahSHUBelumDibagi']);
            GetterSetter::setDBConfig("msKasATMR", $vaRequestData['Kas']);
            GetterSetter::setDBConfig("msBankATMR", $vaRequestData['Bank']);
            GetterSetter::setDBConfig("msSimpananBerjangkaATMR", $vaRequestData['SimpananBerjangka']);
            GetterSetter::setDBConfig("msSimpananSukarelaKopLainATMR", $vaRequestData['SimpananSukarelaKopLain']);
            GetterSetter::setDBConfig("msSimpananBerjangkaKopLainATMR", $vaRequestData['SimpananBerjangkaKopLain']);
            GetterSetter::setDBConfig("msSuratBerhargaATMR", $vaRequestData['SuratBerharga']);
            GetterSetter::setDBConfig("msPiutangPinjamanAnggotaATMR", $vaRequestData['PiutangPinjamanAnggota']);
            GetterSetter::setDBConfig("msPiutangPinjamanNonAnggotaATMR", $vaRequestData['PiutangPinjamanNonAnggota']);
            GetterSetter::setDBConfig("msPiutangPinjamanKopLainATMR", $vaRequestData['PiutangPinjamanKopLain']);
            GetterSetter::setDBConfig("msPenyisihanPiutangTakTertagihATMR", $vaRequestData['PenyisihanPiutangTakTertagih']);
            GetterSetter::setDBConfig("msBebanDibayarDimukaATMR", $vaRequestData['BebanDibayarDimuka']);
            GetterSetter::setDBConfig("msPendapatanAkanDiterimaATMR", $vaRequestData['PendapatanAkanDiterima']);
            GetterSetter::setDBConfig("msAktivaLancarLainnyaATMR", $vaRequestData['AktivaLancarLainnya']);
            GetterSetter::setDBConfig("msPenyertaanKopSekunderATMR", $vaRequestData['PenyertaanKopSekunder']);
            GetterSetter::setDBConfig("msInvestasiSahamATMR", $vaRequestData['InvestasiSaham']);
            GetterSetter::setDBConfig("msInvestasiJangkaPanjangLainATMR", $vaRequestData['InvestasiJangkaPanjangLain']);
            GetterSetter::setDBConfig("msHartaTetapATMR", $vaRequestData['HartaTetap']);
            GetterSetter::setDBConfig("msAkumulasiPenyusutanHartaTetapATMR", $vaRequestData['AkumulasiPenyusutanHartaTetap']);
            GetterSetter::setDBConfig("msAktivaLainLainATMR", $vaRequestData['AktivaLainLain']);

            $retVal = ["status" => "00", "message" => "SUKSES"];
            Func::writeLog('Konfigurasi ATMR', 'store', $vaRequestData, $retVal, $user);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $retVal = [
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
            Func::writeLog('Konfigurasi ATMR', 'store', $vaRequestData, $th, $user);
            // return response()->json($retVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function update(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cKode = $vaRequestData['Kode'];
            $vaArray = [
                "Keterangan" => $vaRequestData['Keterangan'],
                "Persen" => $vaRequestData['Persen'],
                "Rekening" => $vaRequestData['Rekening'] || ""
            ];
            DB::table('tks_atmr')->where('Kode', '=', $cKode)->update($vaArray);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 'error']);
        }
    }

    public function delete(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cKode = $vaRequestData['Kode'];
            DB::table('tks_atmr')->where('Kode', '=', $cKode)->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 'error']);
        }
    }
}
