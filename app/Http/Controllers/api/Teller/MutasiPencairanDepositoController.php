<?php

namespace App\Http\Controllers\api\Teller;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\simpananberjangka\Deposito;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MutasiPencairanDepositoController extends Controller
{
    public function getDataPencairanDeposito(Request $request)
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
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataPencairanDeposito', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $dTgl = GetterSetter::getTglTransaksi();
        $cRekening = $vaRequestData['Rekening'];
        $vaData = DB::table('deposito as d')
            ->select(
                'd.CaraPerhitungan',
                'r.Nama',
                'r.Alamat',
                'g.Lama',
                'g.Bunga',
                'd.Tgl',
                'd.JthTmp',
                'd.RekeningTabungan'
            )
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongandeposito as g', 'g.Kode', '=', 'd.GolonganDeposito')
            ->where('d.Rekening', '=', $cRekening)
            ->first();
        if ($vaData) {
            $cNama = $vaData->Nama;
            $cAlamat = $vaData->Alamat;
            $nJangkaWaktu = $vaData->Lama;
            $cNamaTabungan = '-';
            $nNominalDeposito = PerhitunganDeposito::getNominalDeposito($cRekening, $dTgl);
            $nSukuBunga = $vaData->Bunga;
            $nSukuBungaPersen = $nSukuBunga / 100;
            $dTglValuta = $vaData->Tgl;
            $dJthTmp = $vaData->JthTmp;
            $dJthTmpFormatted = date("d-m-Y", strtotime($dJthTmp));
            $dTglValutaFormatted = date("d-m-Y", strtotime($dTglValuta));
            $cRekTabungan = $vaData->RekeningTabungan;
            if (!empty($cRekTabungan)) {
                $cNamaTabungan = GetterSetter::getNamaRegisterNasabah($cRekTabungan);
                $nSaldoTabungan = PerhitunganTabungan::getSaldoTabungan($cRekTabungan, $dTgl);
            } else {
                $cNamaTabungan = "";
                $nSaldoTabungan = "";
            }
            $nBunga = intval($nNominalDeposito * $nSukuBungaPersen / 12);

            $vaResult = [
                'Faktur' => GetterSetter::getLastFaktur("DP", 6),
                'NominalDeposito' => $nNominalDeposito,
                'SukuBunga' => $nSukuBunga,
                'JangkaWaktu' => $nJangkaWaktu,
                'TglValuta' => $dTglValutaFormatted,
                'JthTmp' => $dJthTmpFormatted,
                'RekTabungan' => $cRekTabungan,
                'SaldoTabungan' => $nSaldoTabungan,
                'NamaTabungan' => GetterSetter::getNamaRegisterNasabah($cRekTabungan),
                'Nominal' => $nNominalDeposito,
                'Bunga' => $nBunga
            ];
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataPencairanDeposito', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "DATA TIDAK DITEMUKAN"
            ];
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataPencairanDeposito', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataTable(Request $request)
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
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = GetterSetter::getTglTransaksi();
            $vaData = DB::table('mutasideposito')
                ->select(
                    'Faktur',
                    'Tgl',
                    'Bunga',
                    'Pajak',
                    'Kas',
                    'UserName','SetoranPlafond','PencairanPlafond'
                )
                ->where('Rekening', $cRekening)
                ->where('Tgl', '<=', $dTgl)
                // ->where('SetoranPlafond', '<=', 0)
                ->orderBy('Tgl')//, 'Faktur')
                // ->limit(10)
                ->get();
            if (count($vaData) > 0) {
                $vaResults = [];
                foreach ($vaData as $d) {
                    $vaResult = [
                        'NoTransaksi' => $d->Faktur,
                        'Tgl' => $d->Tgl,
                        'Bunga' => $d->Bunga,
                        'Pajak' => $d->Pajak,
                        'SetoranPlafond' => $d->SetoranPlafond,
                        'PencairanPlafond' => $d->PencairanPlafond,
                        'CaraPencairan' => $d->Kas,
                        'UserName' => $d->UserName
                    ];
                    $vaResults[] = $vaResult;
                }
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResults
                ];
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResults);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getDataTable', $vaRequestData, $vaRetVal, $cUser);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public function getPokok(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['page']);
        unset($vaRequestData['auth']);
        $nReqCount = count($vaRequestData);
        if ($nReqCount > 2 || $nReqCount < 2 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'] || $vaRequestData['JthTmp'] == null || empty($vaRequestData['JthTmp']))) {
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID"
            ];
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getPokok', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $cRekening = $vaRequestData['Rekening'];
        $dTgl = GetterSetter::getTglTransaksi();
        $dJthTmp = date('Y-m-d', strtotime($vaRequestData['JthTmp']));
        $vaJthtmp = explode('-', $dJthTmp);
        $vaTgl = explode('-', $dTgl);

        $vaData = DB::table('mutasideposito')
            ->select(DB::raw('SUM(SetoranPlafond - PencairanPlafond) as Nominal'))
            ->where('Rekening', '=', $cRekening)
            ->get();
        foreach ($vaData as $d) {
            $nominal = $d->Nominal;
        }
        $vaData2 = DB::table('deposito')
            ->select('StatusBlokir')
            ->where('Rekening', '=', $cRekening)
            ->first();
        if ($vaData2) {
            if ($vaData2->StatusBlokir == 'T') {
                // Cek kalau belum jatuh tempo maka tidak bisa dicairkan
                if ($vaJthtmp[2] !== $vaTgl[2]) {
                    $vaRetVal = [
                        "status" => "03",
                        "message" => "ANDA MELAKUKAN PENCAIRAN SEBELUM JATUH TEMPO " . $dJthTmp . "!"
                    ];
                    Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getPokok', $vaRequestData, $vaRetVal, $cUser);
                    // return $vaRetVal;
                    return response()->json(
                        ['status' => 'error', 'message' => 'Anda Melakukan Pencairan Sebelum Jatuh Tempo' . $dJthTmp . '!']
                    );
                } else {
                    $vaResult = [
                        'Nominal' => $nominal
                    ];
                    // JIKA REQUEST SUKSES
                    $vaRetVal = [
                        "status" => "00",
                        "message" => $vaResult
                    ];
                    Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getPokok', $vaRequestData, $vaRetVal, $cUser);
                    return response()->json($vaResult);
                }
            } else if ($vaData2->StatusBlokir == 'Y') {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "REKENING SIMPANAN BERJANGKA TELAH DIBLOKIR!"
                ];
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getPokok', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Deposito Telah Diblokir!']
                );
            }
        }
    }

    public function getBunga(Request $request)
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
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getBunga', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $cRekening = $vaRequestData['Rekening'];
        $nSukuBungaPersen = 0;
        $vaData = DB::table('mutasideposito')
            ->select('SetoranPlafond')
            ->where('Rekening', $cRekening)
            ->first();
        if ($vaData) {
            $nSetoranPlafond = $vaData->SetoranPlafond;
        }
        $vaData2 = DB::table('deposito as d')
            ->select('Bunga')
            ->leftJoin('golongandeposito as g', 'g.Kode', '=', 'd.GolonganDeposito')
            ->where('Rekening', '=', $cRekening)
            ->first();
        if ($vaData2) {
            $nSukuBunga = $vaData2->Bunga;
            $nSukuBungaPersen = $nSukuBunga / 100;
        }
        $vaResult = [
            'Bunga' => intval(($nSetoranPlafond * $nSukuBungaPersen) / 12)
        ];
        // JIKA REQUEST SUKSES
        $vaRetVal = [
            "status" => "00",
            "message" => $vaResult
        ];
        Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'getBunga', $vaRequestData, $vaRetVal, $cUser);
        return response()->json($vaResult);
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 9 | $nReqCount < 9) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $vaValidator = validator::make($request->all(), [
                'Rekening' => 'required|max:15',
                'Bunga' => 'required|max:10',
                'Pajak' => 'required|max:10',
                'Pinalty' => 'required|max:10',
                'AccrueBunga' => 'required|max:10',
                'Nominal' => 'required|max:10',
                'CaraPencairan' => 'required|max:10',
                'Total' => 'required|max:10',
            ]);
            if ($vaValidator->fails()) {
                $vaRetVal = [
                    "status" => "99",
                    "message" =>  $vaValidator->errors()
                ];
                Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(['status' => 'error']);
            }
            $cFaktur = GetterSetter::getLastFaktur("DP", 6);
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = GetterSetter::getTglTransaksi();
            $dTglAwal = PerhitunganDeposito::getTglBilyet($cRekening);
            $vaAro = PerhitunganDeposito::getAro($cRekening);
            $cAro = $vaAro['ARO'];
            $nBunga = $vaRequestData['Bunga'];
            $nPajak = $vaRequestData['Pajak'];
            $cCaraPerhitungan = $vaAro['CaraPerhitungan'];
            $cCaraPerpanjangan = $vaAro['CaraPerpanjangan'];
            $cRekAkutansi = $vaRequestData['RekeningAkuntansi'];
            $nPinalty = $vaRequestData['Pinalty'];
            $nAccrueBunga = $vaRequestData['AccrueBunga'];
            $cCaraPencairan = $vaRequestData['CaraPencairan'];
            $vaData = DB::table('deposito')
                ->select(
                    'GolonganDeposito',
                    'NamaNasabah',
                    'RekeningTabungan',
                    'JthTmp'
                )
                ->where('Rekening', $cRekening)
                ->first();
            if ($vaData) {
                $cGolDeposito = $vaData->GolonganDeposito;
                $cNama = $vaData->NamaNasabah;
                $cRekTabungan = $vaData->RekeningTabungan;
                $dJthTmp = $vaData->JthTmp;
            }

            $nNominal = $vaRequestData['Nominal'];
            $nLama = PerhitunganDeposito::getLamaDeposito($cGolDeposito);

            $cKeterangan = "Pencairan Bunga Deposito an. " . $cNama;

            if ($nNominal > 0) {
                $cKeterangan = "Pencairan Plafond & Bunga Dep an. " . $cNama;
                Upd::updPencairanDeposito($cRekening, $dTgl);
            }
            Upd::updMutasiDeposito(false, '0', $cFaktur, $cRekening, GetterSetter::getDBConfig('msKodeCabang'), $dTgl, $dJthTmp, 0, $nNominal, $nBunga, $nPajak, 0, 0, $nPinalty, $cCaraPencairan, $cKeterangan, true, $nAccrueBunga, 0, $cRekAkutansi);

            if ($cCaraPencairan == 'T') {
                $cPencairanBungaDeposito = GetterSetter::getDBConfig('msKodeBungaDeposito');
                $nPajakBungaDeposito = GetterSetter::getDBConfig('msKodePajakDeposito');
                $cKodeCabang = GetterSetter::getDBConfig('msKodeCabang');
                $cKetPajak = "Pajak Bunga Deposito an. " . $cNama;
                Upd::updMutasiTabungan($cFaktur, $dTgl, $cRekTabungan, $cPencairanBungaDeposito, $cKeterangan, $nBunga - $nPajak, $cKodeCabang, true);
            }
            GetterSetter::setLastFaktur("DP");
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Mutasi Pencairan Simpanan Berjangka', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
