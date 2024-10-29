<?php

namespace App\Http\Controllers\api\Teller;

use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;

use App\Models\master\Rekening;

class TellerController extends Controller
{
    public function getTipeTransaksi(Request $request)
    {
        $cRekening = $request->Rekening;
        // $vaGolongan = ['simpanan', 'tabungan', 'deposito', 'kredit'];
        $vaTable = ['registernasabah', 'tabungan', 'deposito', 'debitur'];
        $cGolongan = substr($cRekening, 3, 1);

        $cField = 'Rekening';
        $cTable = $vaTable[$cGolongan];

        if ($cGolongan == '0') {
            $cField = 'Kode';
            $cTable = 'registernasabah';
        }

        $query = DB::table($cTable);

        if ($cTable !== 'registernasabah') {
            $query->join('registernasabah', 'registernasabah.Kode', '=', $cTable . '.Kode');
        }

        $query->select(
            $cTable . '.' . $cField,
            'registernasabah.Nama',
            'registernasabah.Alamat',
            $cTable . '.Kode AS KodeValue' // Gunakan alias yang jelas untuk menghindari ambiguitas
        )->where($cTable . '.' . $cField, $cRekening);

        $row = $query->first();

        if ($row) {
            $cGolongan = $row->$cField;

            if ($cTable === 'tabungan') {
                // Panggil seekTabungan dengan parameter $cRekening
                $resultTabungan = $this->seekTabungan($request);

                if ($resultTabungan === true) {
                    return $row;
                } else {
                    // Jika seekTabungan mengembalikan pesan, tangani sesuai kebutuhan
                    return $resultTabungan;
                }
            }

            if ($cTable === 'deposito') { // Jika rekening adalah rekening deposito
                $result = $this->seekDeposito($request); // Panggil fungsi seekDeposito
                if ($result === "Mutasi") {
                    $row->StatusDeposito = "1";
                } else if ($result === "Baru") {
                    $row->StatusDeposito = "0";
                } else {
                    return $result; // Kembalikan pesan error jika rekening deposito tidak valid
                }
            }

            if ($cTable === 'debitur') {
                // Panggil seekTabungan dengan parameter $cRekening
                $resultKredit = $this->seekKredit($request);

                if ($resultKredit === true) {
                    return $row;
                } else {
                    // Jika seekTabungan mengembalikan pesan, tangani sesuai kebutuhan
                    return $resultKredit;
                }
            }
        }

        return $row;
    }

    function seekTabungan(Request $request)
    {
        $cRekening = $request->Rekening;
        $tabungan = DB::table('tabungan')
            ->select('Rekening', 'Close', 'Kode', 'StatusOtorisasi')
            ->where('Rekening', $cRekening)
            ->first();

        if ($tabungan) {
            if ($tabungan->Close == '0') {
                if ($tabungan->StatusOtorisasi == '1') {
                    // Lakukan tindakan yang sesuai, misalnya:
                    // LoadTabungan($cRekening);
                    return true;
                } else {
                    return '44';
                }
            } else {
                return '99';
            }
        } else {
            return '99';
        }

        return false;
    }

    function seekDeposito(Request $request)
    {
        $cRekening = $request->Rekening;
        $deposito = DB::table('deposito')
            ->select('Rekening', 'status as StatusPencairan', 'Kode', 'StatusOtorisasi')
            ->where('Rekening', $cRekening)
            ->first();

        if ($deposito) {
            $nSaldo = self::getNominalDeposito(GetterSetter::getTglTransaksi(), $cRekening);
            if ($nSaldo > 0) {
                // Lakukan tindakan yang sesuai, misalnya:
                // LoadDeposito($cRekening);
                return 'Mutasi';
                return true;
            } else {
                if ($deposito->StatusPencairan == "1") {
                    return '99';
                } else {
                    if ($deposito->StatusOtorisasi == "1") {
                        
                        // Lakukan tindakan yang sesuai, misalnya:
                        // LoadPembukaanDeposito($cRekening);
                        return 'Baru';
                        return true;
                    } else {
                        return '44';
                    }
                }
            }
        } else {
            return 'Rekening Deposito Tidak Ditemukan ....!';
        }

        return false;
    }

    function seekDeposito2(Request $request)
    {
        $cRekening = $request->Rekening;

        return $cRekening;
    }

    function getNominalDeposito($dTgl, $cRekening, $lLain = false)
    {
        $dTgl  = date('Y-m-d', strtotime($dTgl));
        $cKode =  $this->getKode($cRekening); // Ganti dengan pemanggilan fungsi yang sesuai

        if ($lLain) {
            $query = DB::table('deposito as d')
                ->leftJoin('mutasideposito as m', function ($join) use ($cRekening, $dTgl) {
                    $join->on('m.rekening', '=', 'd.rekening')
                        ->where('m.rekening', '<>', $cRekening)
                        ->where('m.tgl', '<', $dTgl);
                })
                ->where('d.kode', $cKode)
                ->select(DB::raw('sum(m.setoranplafond - m.pencairanplafond) as saldo'))
                ->first();
        } else {
            $query = DB::table('mutasideposito')
                ->where('rekening', $cRekening)
                ->where('tgl', '<=', $dTgl)
                ->select(DB::raw('sum(setoranplafond - pencairanplafond) as saldo'))
                ->first();
        }

        $nSaldo = 0;
        if ($query) {
            $nSaldo = $query->saldo;
        }
        return $nSaldo;
    }

    function getKode($cRekening)
    {
        $cKode = "";
        $vaArray = ['', "tabungan", "deposito", "debitur"];

        foreach ($vaArray as $key => $value) {
            if (empty($cKode) && !empty($value)) {
                $query = DB::table($value)
                    ->select('Kode')
                    ->where('Rekening', $cRekening)
                    ->first();

                if ($query) {
                    $cKode = $query->Kode;
                }
            }
        }
        return $cKode;
    }

    public function seekKredit(Request $request)
    {
        $cRekening = $request->Rekening;
        $kredit = DB::table('debitur')
            ->select('Rekening', 'StatusPencairan', 'Kode', 'CaraPerhitungan')
            ->where('Rekening', $cRekening)
            ->first();

        if ($kredit) {
            $nBakiDebet = $this->GetBakiDebet($cRekening, GetterSetter::getTglTransaksi());

            if ($kredit->StatusPencairan == '1') {
                if ($kredit->CaraPerhitungan == "11") {
                    echo ('Load Rekening Koran ' . $cRekening);
                } else if ($nBakiDebet > 0) {
                    // echo($cRekening);
                    return true;
                } else {
                    // return '99'; //  echo('Pinjaman Sudah Lunas !!!');
                    return true;
                }
                return true;
            } else {
                return '98'; // echo('Rekening Belum Di Cairkan !!!');
            }
        } else {
            echo ('Rekening Kredit Tidak Ditemukan ....!');
        }
        return false;
    }

    function GetBakiDebet($cRekening, $dTgl)
    {
        $dTgl = $dTgl; // Pastikan ini sesuai dengan implementasi Date2String yang Anda miliki

        $saldo = DB::table('angsuran')
            ->where('rekening', $cRekening)
            ->where('tgl', '<=', $dTgl)
            ->select(DB::raw('sum(DPokok - KPokok) as Saldo'))
            ->first();

        $nSaldo = 0;
        if ($saldo) {
            $nSaldo = $saldo->Saldo;
        }
        return $nSaldo;
    }

    function seekRekening(Request $request)
    {
        $result1 = DB::table('registernasabah')
            ->select(DB::raw("'0' AS Kode"), 'Kode AS Rekening', 'Nama', 'Alamat')
            ->paginate(100000000000000);

        $result2 = DB::table('tabungan as t')
            ->join('registernasabah as r', 't.Kode', '=', 'r.Kode')
            ->select(DB::raw("'0' AS Kode"), 't.rekening AS Rekening', 'r.nama AS Nama', 'r.alamat AS Alamat')
            ->paginate(100000000000000);

        $result3 = DB::table('deposito as t')
            ->join('registernasabah as r', 't.Kode', '=', 'r.Kode')
            ->select(DB::raw("'0' AS Kode"), 't.rekening AS Rekening', 'r.nama AS Nama', 'r.alamat AS Alamat')
            ->paginate(100000000000000);

        $result4 = DB::table('debitur as t')
            ->join('registernasabah as r', 't.Kode', '=', 'r.Kode')
            ->select(DB::raw("'0' AS Kode"), 't.rekening AS Rekening', 'r.nama AS Nama', 'r.alamat AS Alamat')
            ->paginate(100000000000000);

        $items1 = $result1->getCollection();
        $items2 = $result2->getCollection();
        $items3 = $result3->getCollection();
        $items4 = $result4->getCollection();

        // Gabungkan koleksi items dari keempat hasil
        $mergedItems = $items1->merge($items2)->merge($items3)->merge($items4);

        // Setel koleksi items baru ke hasil pertama (misalnya, $result1)
        $result1->setCollection($mergedItems);

        return $result1;
    }

    function seekSimpanan(Request $request)
    {
        $rekening = $request->Rekening;
    
        $result = DB::table('tabungan as t')
            ->leftJoin('mutasitabungan as m', 'm.Rekening', '=', 't.Rekening')
            ->leftJoin('golongantabungan as g', 'g.KODE', '=', 't.GolonganTabungan') // Join with golongantabungan
            ->select(
                't.Rekening',
                't.NamaNasabah as Nama',
                DB::raw('IFNULL(SUM(m.Kredit - m.Debet), 0) as Saldo'),
                't.RekeningLama',
                // 't.GolonganTabungan',
                'g.KETERANGAN as GolonganTabungan' // Select the description column
            )
            ->where('t.Kode', '=', $rekening)
            ->groupBy('t.Rekening', 't.NamaNasabah', 't.RekeningLama', 't.GolonganTabungan', 'g.KETERANGAN') // Group by the description column
            ->orderBy('t.Rekening')
            ->get();
    
        return response()->json($result);
    }

    function seekSimpananBerjangka(Request $request)
    {
        $rekening = $request->Rekening;
    
        $result = DB::table('deposito as t')
            ->leftJoin('mutasideposito as m', 'm.Rekening', '=', 't.Rekening')
            ->select(
                't.Rekening',
                DB::raw('IFNULL(SUM(m.SetoranPlafond - m.PencairanPlafond), 0) as Saldo'),
                't.RekeningLama',
                't.NoBilyet',
                DB::raw("CASE WHEN t.ARO = 'P' THEN 'ARO POKOK BUNGA' WHEN t.ARO = 'Y' THEN 'ARO' ELSE t.ARO END AS ARO")
            )
            ->where('t.Kode', '=', $rekening)
            ->groupBy('t.Rekening', 't.RekeningLama', 't.NoBilyet', 't.ARO')
            ->orderBy('t.Rekening')
            ->get();
    
        return response()->json($result);
    }

    function seekPinjaman(Request $request)
    {
        $rekening = $request->Rekening;
    
        $result = DB::table('debitur as t')
            ->leftJoin('angsuran as m', 'm.Rekening', '=', 't.Rekening')
            ->select(
                't.StatusPencairan',
                't.TglLunas',
                DB::raw('(SELECT Keterangan FROM golongankredit WHERE KODE = t.GolonganKredit) AS golongankredit'),
                't.Rekening',
                DB::raw('IFNULL(SUM(m.DPokok - m.KPokok), 0) AS Saldo'),
                't.RekeningLama',
                't.Lama',
                't.NoSPK',
                't.TglWriteOff'
            )
            ->where('t.Kode', '=', $rekening)
            ->groupBy(
                't.Rekening',
                't.StatusPencairan',
                't.TglLunas',
                't.GolonganKredit',
                't.RekeningLama',
                't.Lama',
                't.NoSPK',
                't.TglWriteOff'
            )
            ->orderBy('t.Rekening')
            ->get();
    
        return response()->json($result);
    }

    function seekJaminan(Request $request)
    {
        $rekening = $request->Rekening;
    
        $result = DB::table('debitur as t')
            ->leftJoin('pengajuankredit as p', 'p.Rekening', '=', 't.NoPengajuan')
            ->leftJoin('agunan as g', 'g.Rekening', '=', 'p.Jaminan')
            ->select(
                't.Rekening',
                'p.Rekening AS RekPengajuan',
                't.rekeningjaminan AS RekJaminan'
            )
            ->where('t.Kode', '=', $rekening)
            ->orderBy('t.Rekening')
            ->get();
    
        return response()->json($result);
    }

    // function seekJaminanDetail(Request $request)
    // {
    //     $rekening = $request->Rekening;

    //     $result = DB::table('agunan')
    //         ->select('Rekening', 'No', 'Jaminan')
    //         ->where('Rekening', '=', $rekening)
    //         ->where('status', '=', 1)
    //         ->orderBy('Rekening')
    //         ->get();

    //     GetterSetter::getDetailJaminan();

    //     return response()->json($result);
    // }
    
    function seekJaminanDetail(Request $request)
    {
        $rekening = $request->Rekening;
    
        $resultAgunan = DB::table('agunan')
            ->select('Rekening', 'No', 'Jaminan')
            ->where('Rekening', '=', $rekening)
            ->where('status', '=', 1)
            ->orderBy('Rekening')
            ->get();
    
        // Check if agunan data is available
        if (!$resultAgunan->isEmpty()) {
            $response = [];
    
            foreach ($resultAgunan as $agunanData) {
                // Call getDetailJaminan function with necessary parameters for each row
                $detailJaminan = GetterSetter::getDetailJaminan($agunanData->Rekening, $agunanData->No, $agunanData->Jaminan, '2024-01-01');
    
                // Add the details to the response for each row
                $response[] = [
                    'Rekening' => $agunanData->Rekening,
                    'No' => $agunanData->No,
                    'Jaminan' => $agunanData->Jaminan,
                    'DetailJaminan' => $detailJaminan,
                ];
            }
    
            return response()->json($response);
        } else {
            // Handle case when agunan data is not found
            // return response()->json(['error' => 'Agunan data not found'], 404);
            // return response()->json($response);
        }
    }
    
    
}
