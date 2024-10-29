<?php

namespace App\Http\Controllers\api\laporanakuntansi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use App\Helpers\GetterSetter;
use Carbon\Carbon;

class NeracaController extends Controller
{
    function data(Request $request)
    {
        ini_set('max_execution_time', 0); //3 minutes
        // ini_set('max_execution_time', 1800); //3 minutes
        $tglAwal = Carbon::parse($request->TglAwal)->subDay()->toDateString();
        $tglAwalTidakDikurang = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $cJenisGabungan = $request['JenisGabungan'];
        $cCabang = null;
        if ($cJenisGabungan !== "C") {
            $cCabang = $request['Cabang'];
        }

        //1
        $totalAsetAwal = 0;
        $totalAsetAkhir = 0;
        $totalAsetDebet = 0;
        $totalAsetKredit = 0;

        //2&3
        $totalPasivaAwal = 0;
        $totalPasivaAkhir = 0;
        $totalPasivaDebet = 0;
        $totalPasivaKredit = 0;

        $KodeRekeningLaba = GetterSetter::getDBConfig("msRekeningLaba");
        $data = DB::table('rekening')
            ->select('Kode', 'Keterangan', 'Jenis')
            ->where('Kode', 'NOT LIKE', '4%')
            ->where('Kode', 'NOT LIKE', '5%')
            ->orderBy('Kode')
            ->get();

        foreach ($data as $item) {
            $NomorAkuntansi = substr($item->Kode, 0, 1);

            if ($NomorAkuntansi === "1") {
                if ($item->Jenis === 'I') {
                    $saldoAwal = "SELECT SUM(b.debet - b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.debet - b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwalTidakDikurang . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;
                } else {
                    $saldoAwal = "SELECT SUM(b.debet - b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.debet - b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang where b.tgl >= '" . $tglAwalTidakDikurang . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;

                    $totalAsetAwal = $totalAsetAwal + $saldoAwalResult[0]->Saldo;
                    $totalAsetAkhir = $totalAsetAkhir + $SaldoAkhirResult[0]->Saldo;
                    $totalAsetDebet = $totalAsetDebet + $saldoMutasiResult[0]->Debet;
                    $totalAsetKredit =  $totalAsetKredit + $saldoMutasiResult[0]->Kredit;
                }
            } else {
                if ($item->Jenis === 'I') {
                    $saldoAwal = "SELECT SUM(b.kredit-b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode = b.Cabang WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.kredit-b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;

                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode where b.tgl >= '" . $tglAwalTidakDikurang . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;
                } else {
                    $saldoAwal = "SELECT SUM(b.kredit-b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode WHERE b.tgl <= '" . $tglAwal . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoAwal .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoAwalResult = DB::select($saldoAwal);
                    $item->SaldoAwal = $saldoAwalResult[0]->Saldo;

                    $SaldoAkhir = "SELECT SUM(b.kredit-b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode WHERE b.tgl <= '" . $tglAkhir . "' AND b.rekening = '" . $item->Kode . "'";
                    if ($cJenisGabungan !== 'C') {
                        $SaldoAkhir .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $SaldoAkhirResult = DB::select($SaldoAkhir);
                    $item->SaldoAkhir = $SaldoAkhirResult[0]->Saldo;


                    $saldoMutasi = "SELECT sum(Debet) as Debet, sum(Kredit) as Kredit from bukubesar b LEFT JOIN cabang c ON c.Kode where b.tgl >= '" . $tglAwalTidakDikurang . "' and b.tgl <= '" . $tglAkhir . "' AND b.rekening LIKE '" . $item->Kode . "%'";
                    if ($cJenisGabungan !== 'C') {
                        $saldoMutasi .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                    }
                    $saldoMutasiResult = DB::select($saldoMutasi);
                    $item->Debet = $saldoMutasiResult[0]->Debet;
                    $item->Kredit = $saldoMutasiResult[0]->Kredit;

                    $totalPasivaAwal = $totalPasivaAwal + $saldoAwalResult[0]->Saldo;
                    $totalPasivaAkhir = $totalPasivaAkhir + $SaldoAkhirResult[0]->Saldo;
                    $totalPasivaDebet = $totalPasivaDebet + $saldoMutasiResult[0]->Debet;
                    $totalPasivaKredit = $totalPasivaKredit + $saldoMutasiResult[0]->Kredit;
                }
            }

            if ($item->Kode === $KodeRekeningLaba) {
                // dd("DESTRY");

                $saldo4 = "SELECT SUM(b.kredit - b.debet) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode INNER JOIN rekening r ON b.rekening = r.Kode WHERE b.tgl <= '" . $tglAwal . "' AND r.Jenis = 'D' AND b.Rekening LIKE '4%'";
                $saldo5 = "SELECT SUM(b.debet - b.kredit) as Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode INNER JOIN rekening r ON b.rekening = r.Kode WHERE b.tgl <= '" . $tglAwal . "' AND r.Jenis = 'D' AND b.Rekening LIKE '5%'";

                $saldoKredit = "SELECT SUM(CASE WHEN b.rekening LIKE '5%' THEN b.debet ELSE 0 END) AS Debet, SUM(CASE WHEN b.rekening LIKE '5%' THEN b.kredit ELSE 0 END) AS Kredit, SUM(CASE WHEN b.rekening LIKE '5%' THEN b.debet ELSE 0 END)-SUM(CASE WHEN b.rekening LIKE '5%' THEN b.kredit ELSE 0 END) AS Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode INNER JOIN rekening r ON b.rekening = r.Kode WHERE b.tgl >= '" . $tglAwalTidakDikurang . "' AND b.tgl <= '" . $tglAkhir . "' AND r.Jenis = 'D'";
                $saldoDebet = "SELECT SUM(CASE WHEN b.rekening LIKE '4%' THEN b.debet ELSE 0 END) AS Debet, SUM(CASE WHEN b.rekening LIKE '4%' THEN b.kredit ELSE 0 END) AS Kredit, SUM(CASE WHEN b.rekening LIKE '4%' THEN b.kredit ELSE 0 END)-SUM(CASE WHEN b.rekening LIKE '4%' THEN b.debet ELSE 0 END)AS Saldo FROM bukubesar b LEFT JOIN cabang c ON c.Kode INNER JOIN rekening r ON b.rekening = r.Kode WHERE b.tgl >= '" . $tglAwalTidakDikurang . "' AND b.tgl <= '" . $tglAkhir . "' AND r.Jenis = 'D'";

                if ($cJenisGabungan !== 'C') {
                    $saldo4 .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldo4Result = DB::select($saldo4);

                if ($cJenisGabungan !== 'C') {
                    $saldo5 .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldo5Result = DB::select($saldo5);

                if ($cJenisGabungan !== 'C') {
                    $saldoKredit .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldoDebetResult = DB::select($saldoKredit);

                if ($cJenisGabungan !== 'C') {
                    $saldoDebet .= " AND " . GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
                }
                $saldoKreditResult = DB::select($saldoDebet);

                $item->SaldoAwal = $saldo4Result[0]->Saldo - $saldo5Result[0]->Saldo;
                $item->SaldoAkhir = ($saldo4Result[0]->Saldo - $saldo5Result[0]->Saldo) + $saldoKreditResult[0]->Saldo - $saldoDebetResult[0]->Saldo;
                $item->Kredit = $saldoKreditResult[0]->Saldo;
                $item->Debet = $saldoDebetResult[0]->Saldo;

                $totalPasivaAwal = $totalPasivaAwal + $saldo4Result[0]->Saldo - $saldo5Result[0]->Saldo;
                $totalPasivaAkhir = $totalPasivaAkhir + ($saldo4Result[0]->Saldo - $saldo5Result[0]->Saldo) + $saldoKreditResult[0]->Saldo - $saldoDebetResult[0]->Saldo;
                $totalPasivaDebet = $totalPasivaDebet + $saldoDebetResult[0]->Saldo;
                $totalPasivaKredit = $totalPasivaKredit + $saldoKreditResult[0]->Saldo;
            }
        }
        // $result = [
        //     "data" => $data,
        // ];



        // return response()->json($result);

        $arrayStartsWith1 = [];
        $arrayNotStartsWith1 = [];

        foreach ($data as $item) {
            if (substr($item->Kode, 0, 1) === "1") {
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
            "Keterangan" => "ASET",
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
            "Keterangan" => "TOTAL ASET",
            "Jenis" => "I",
            "SaldoAwal" => $totalAsetAwal,
            "SaldoAkhir" => $totalAsetAkhir,
            "Debet" => $totalAsetDebet,
            "Kredit" => $totalAsetKredit,
        ];
        array_push($arrayStartsWith1, $recordEndStartsWith1);

        // Iterate through $data and populate $arrayStartsWith1 and $arrayNotStartsWith1

        // ... (Your existing code)

        // Add record at the beginning
        $recordBeginningNotStartsWith1 = [
            "Kode" => "",
            "Keterangan" => "KEWAJIBAN DAN MODAL",
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
            "Keterangan" => "TOTAL KEWAJIBAN DAN MODAL",
            "Jenis" => "I",
            "SaldoAwal" => $totalPasivaAwal,
            "SaldoAkhir" => $totalPasivaAkhir,
            "Debet" => $totalPasivaDebet,
            "Kredit" => $totalPasivaKredit,
        ];
        array_push($arrayNotStartsWith1, $recordEndNotStartsWith1);

        //////////////////////////////////////

        $result = [
            "dataAktiva" => array_merge(
                $arrayStartsWith1,
                // $arrayNotStartsWith1,
            ),
            "dataPasiva" => array_merge(
                // $arrayStartsWith1,
                $arrayNotStartsWith1,
            ),
        ];

        // $result = [
        //     "data" => array_merge(
        //         $arrayStartsWith1,
        //         $arrayNotStartsWith1,
        //     ),
        // ];

        return response()->json($result);
    }
}
