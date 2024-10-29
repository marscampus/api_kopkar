<?php

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakBukuSimpananController extends Controller
{
    public function getRekening(Request $request)
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
                Func::writeLog('Cetak Buku Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $dTglAwal = $vaRequestData['TglAwal'];
            $nSaldoAwal = 0;
            $vaData = DB::table('mutasitabungan')
                ->select(DB::raw('SUM(Kredit-Debet) as SaldoAwal'))
                ->where('rekening', '=', $cRekening)
                ->where('tgl', '<', $dTglAwal)
                ->first();
            if ($vaData) {
                $nSaldoAwal = $vaData->SaldoAwal;
            }
            $vaData2 = DB::table('tabungan as t')
                ->select(
                    'r.Nama',
                    'r.Alamat',
                    't.SaldoAkhir',
                    't.BarisPencetakan',
                    't.Kode'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->where('t.Rekening', $cRekening)
                ->first();
            if ($vaData2) {
                if ($vaData2->BarisPencetakan == 0) {
                    $barisCetak = 1;
                } else {
                    $barisCetak = $vaData2->BarisPencetakan;
                }
                $vaResult = [
                    'Rekening' => $cRekening,
                    'Nama' => GetterSetter::getKeterangan($vaData2->Kode, 'Nama', 'registernasabah'),
                    'Alamat' => $vaData2->Alamat,
                    'SaldoAwal' => $nSaldoAwal,
                    'SaldoAkhir' => $vaData2->SaldoAkhir,
                    'BarisCetak' => $barisCetak
                ];
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Cetak Buku Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Cetak Buku Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Cetak Buku Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            // if ($nReqCount < 3 || $nReqCount > 3) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            $cRekening = $vaRequestData['Rekening'];
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $dBackDate = Carbon::parse($dTglAwal)->subDay();
            $nSaldo = PerhitunganTabungan::getSaldoTabungan($cRekening, $dBackDate->format('Y-m-d'));
            $vaData = DB::table('mutasitabungan as m')
                ->select('StatusPrinter', 'ID', 'Tgl', 'KodeTransaksi', 'Jumlah', 'DK', 'Debet', 'Kredit', 'UserName')
                ->selectSub(function ($query) {
                    $query->select('useracc')
                        ->from('request')
                        ->whereColumn('faktur', 'm.faktur')
                        ->limit(1);
                }, 'UserACC')
                ->where('Rekening', $cRekening)
                ->where('Tgl', '>=', $dTglAwal)
                ->where('Tgl', '<=', $dTglAkhir)
                ->orderBy('Tgl')
                ->orderBy('ID')
                ->get();
            // JIKA REQUEST SUKSES
            if ($vaData->count() > 0) {
                $vaResults = [];
                foreach ($vaData as $d) {
                    $cStatus = 1;
                    if ($d->StatusPrinter !== '0') {
                        $cStatus = 0;
                    }
                    $nSaldo += $d->Kredit - $d->Debet;
                    $vaResult = [
                        ' ' => $cStatus,
                        'Tgl' => $d->Tgl,
                        'Jumlah' => $d->Jumlah,
                        'SD' => $d->KodeTransaksi,
                        'Debet' => $d->Debet,
                        'Kredit' => $d->Kredit,
                        'Saldo' => $nSaldo,
                        'UserName' => $d->UserName . ' ' . $d->UserACC,
                        'ID' => $d->ID
                    ];
                    $vaResults[] = $vaResult;
                }

                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResults
                ];
                Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResults);
            } else {
                $vaRetVal = [
                    "status" => "03",
                    "message" => "DATA TIDAK DITEMUKAN"
                ];
                Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
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

            Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function updBarisCetak(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            if ($nReqCount < 2 || $nReqCount > 2) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cRekening = $vaRequestData['Rekening'];
            $nBarisCetak = $vaRequestData['BarisCetak'];
            $vaArray = [
                'BarisCetak' => $nBarisCetak
            ];
            Tabungan::where('Rekening', '=', $cRekening)->update($vaArray);
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

            Func::writeLog('Cetak Buku Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
