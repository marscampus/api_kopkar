<?php

namespace App\Http\Controllers\api\laporanakuntansi;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use App\Helpers\GetterSetter;
use App\Helpers\Perhitungan;
use Carbon\Carbon;

class LabaRugiController extends Controller
{
    function data(Request $request)
    {
        $previous_max_execution_time = ini_get('max_execution_time');
        ini_set('max_execution_time', 0);
        $tglAwal = Carbon::parse($request->TglAwal)->subDay()->toDateString();
        $tglAkhir = $request->TglAkhir;
        $cJenisGabungan = $request['JenisGabungan'];
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $request['Cabang'];
        }

        //4
        $totalPendapatanAwal = 0;
        $totalPendapatanAkhir = 0;
        $totalPendapatanDebet = 0;
        $totalPendapatanKredit = 0;

        //5
        $totalBiayaAwal = 0;
        $totalBiayaAkhir = 0;
        $totalBiayaDebet = 0;
        $totalBiayaKredit = 0;

        $totalBiayaNonAwal = 0;
        $totalBiayaNonAkhir = 0;
        $totalBiayaNonDebet = 0;
        $totalBiayaNonKredit = 0;

        $KodeRekeningLaba = GetterSetter::getDBConfig("msRekeningLaba");
        $data = DB::table('rekening')
            ->select('Kode', 'Keterangan', 'Jenis')
            ->where('Kode', 'NOT LIKE', '1%')
            ->where('Kode', 'NOT LIKE', '2%')
            ->where('Kode', 'NOT LIKE', '3%')
            ->orderBy('Kode')
            ->get();

        foreach ($data as $item) {
            $NomorAkuntansi = substr($item->Kode, 0, 1);

            if ($NomorAkuntansi === "4") {
                if ($item->Jenis === 'I') {
                    $saldoAwal = "SELECT SUM(b.kredit- b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.kredit- b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwal . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;
                } else {
                    $saldoAwal = "SELECT SUM(b.kredit- b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.kredit- b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwal . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;

                    $totalPendapatanAwal = $totalPendapatanAwal + $saldoAwalResult[0]->Saldo;
                    $totalPendapatanAkhir = $totalPendapatanAkhir + $SaldoAkhirResult[0]->Saldo;
                    $totalPendapatanDebet = $totalPendapatanDebet + $saldoMutasiResult[0]->Debet;
                    $totalPendapatanKredit =  $totalPendapatanKredit + $saldoMutasiResult[0]->Kredit;
                }
            } else {
                if ($item->Jenis === 'I') {
                    $saldoAwal = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwal . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;
                } else {
                    $saldoAwal = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwal . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;

                    $totalBiayaAwal = $totalBiayaAwal + $saldoAwalResult[0]->Saldo;
                    $totalBiayaAkhir = $totalBiayaAkhir + $SaldoAkhirResult[0]->Saldo;
                    $totalBiayaDebet = $totalBiayaDebet + $saldoMutasiResult[0]->Debet;
                    $totalBiayaKredit = $totalBiayaKredit + $saldoMutasiResult[0]->Kredit;
                }
            }

            if ($item->Kode === $KodeRekeningLaba) {
                $saldoAwal = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening = '" . $item->Kode . "'";
                if ($cJenisGabungan !== 'C') {
                    $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldoAwalResult = DB::select($saldoAwal);
                $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                $SaldoAkhir = "SELECT SUM(b.debet-b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening = '" . $item->Kode . "'";
                if ($cJenisGabungan !== 'C') {
                    $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $SaldoAkhirResult = DB::select($SaldoAkhir);
                $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwal . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                if ($cJenisGabungan !== 'C') {
                    $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldoMutasiResult = DB::select($saldoMutasi);
                $item->Debet = $saldoMutasiResult[0]->Debet;
                $item->Kredit = $saldoMutasiResult[0]->Kredit;

                $totalBiayaAwal = $totalBiayaAwal + $saldoAwalResult[0]->Saldo;
                $totalBiayaAkhir = $totalBiayaAkhir + $SaldoAkhirResult[0]->Saldo;
                $totalBiayaDebet = $totalBiayaDebet + $saldoMutasiResult[0]->Debet;
                $totalBiayaKredit = $totalBiayaKredit + $saldoMutasiResult[0]->Kredit;

                $totalBiayaNonAwal = $totalBiayaNonAwal + $saldoAwalResult[0]->Saldo;
                $totalBiayaNonAkhir = $totalBiayaNonAkhir + $SaldoAkhirResult[0]->Saldo;
                $totalBiayaNonDebet = $totalBiayaNonDebet + $saldoMutasiResult[0]->Debet;
                $totalBiayaNonKredit = $totalBiayaNonKredit + $saldoMutasiResult[0]->Kredit;
            }
        }
        // $result = [
        //     "data" => $data,
        // ];

        // return response()->json($result);

        $arrayStartsWith1 = [];
        $arrayNotStartsWith1 = [];

        foreach ($data as $item) {
            if (substr($item->Kode, 0, 1) === "4") {
                $arrayStartsWith1[] = $item;
            } else {
                $arrayNotStartsWith1[] = $item;
            }

            // ... (Your existing code)
        }

        ////////////////////////////////

        // Add record at the beginning
        $recordBeginningStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "PENDAPATAN OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => "-",
            "SaldoAkhir" => 0,
            "Debet" => 0,
            "Kredit" => 0,
        ];
        array_unshift($arrayStartsWith1, $recordBeginningStartsWith1);

        // Add record at the end
        $recordEndStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "TOTAL PENDAPATAN OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => $totalPendapatanAwal,
            "SaldoAkhir" => $totalPendapatanAkhir,
            "Debet" => $totalPendapatanDebet,
            "Kredit" => $totalPendapatanKredit,
        ];
        array_push($arrayStartsWith1, $recordEndStartsWith1);

        // Iterate through $data and populate $arrayStartsWith1 and $arrayNotStartsWith1

        // ... (Your existing code)

        // Add record at the beginning
        $recordBeginningNotStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "BIAYA OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => "-",
            "SaldoAkhir" => 0,
            "Debet" => 0,
            "Kredit" => 0,
        ];
        array_unshift($arrayNotStartsWith1, $recordBeginningNotStartsWith1);

        // Add record at the end
        $recordEndNotStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "TOTAL BIAYA OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => $totalBiayaAwal,
            "SaldoAkhir" => $totalBiayaAkhir,
            "Debet" => $totalBiayaDebet,
            "Kredit" => $totalBiayaKredit,
        ];
        array_push($arrayNotStartsWith1, $recordEndNotStartsWith1);

        // Add record at the end
        $recordEndNotStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "LABA RUGI OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => $totalPendapatanAwal - $totalBiayaAwal,
            "SaldoAkhir" => $totalPendapatanAkhir - $totalBiayaAkhir,
            "Debet" => $totalPendapatanDebet + $totalBiayaDebet,
            "Kredit" => $totalPendapatanKredit + $totalBiayaKredit,
        ];
        array_push($arrayNotStartsWith1, $recordEndNotStartsWith1);


        // // Add record at the end
        // $recordEndNotStartsWith1 = [
        //     "Kode" => "",
        //     "Keterangan" => "TOTAL BIAYA NON OPERASIONAL",
        //     "Jenis" => "I",
        //     "SaldoAwal" => $totalPendapatanAwal - $totalBiayaAwal,
        //     "SaldoAkhir" => $totalPendapatanAkhir - $totalBiayaAkhir,
        //     "Debet" => $totalPendapatanDebet + $totalBiayaDebet,
        //     "Kredit" => 0,
        // ];

        // dd($totalBiayaNonAwal);
        // array_push($arrayNotStartsWith1, $recordEndNotStartsWith1);

        // Add record at the end
        $recordEndNotStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "TOTAL BIAYA NON OPERASIONAL",
            "Jenis" => "I",
            "SaldoAwal" => $totalBiayaNonAwal,
            "SaldoAkhir" => $totalBiayaNonAkhir,
            "Debet" => $totalBiayaNonDebet,
            "Kredit" => $totalBiayaNonKredit,
        ];
        array_push($arrayNotStartsWith1, $recordEndNotStartsWith1);

        //////////////////////////////////////

        // $result = [
        //     "data" => array_merge(
        //         $arrayStartsWith1,
        //         $arrayNotStartsWith1,
        //     ),
        // ];

        $result = [
            "dataPendapatan" => array_merge(
                $arrayStartsWith1,
                // $arrayNotStartsWith1,
            ),
            "dataBiaya" => array_merge(
                // $arrayStartsWith1,
                $arrayNotStartsWith1,
            ),
        ];
        ini_set('max_execution_time', $previous_max_execution_time);
        return response()->json($result);
    }
}
