<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\VoltTeller;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;


class VoltTellerController extends Controller
{
    function data(Request $request)
    {
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $VoltTeller = VoltTeller::where($k, "LIKE", '%' . $v . '%')->paginate(10);
                return response()->json($VoltTeller);
            }
        }
        $VoltTeller = VoltTeller::paginate(10);
        return response()->json($VoltTeller);
    }

    function dataJurnal(Request $request)
    {

        $tglAwal = $request->tglAwal;
        $tglAkhir = $request->tglAkhir;

        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $VoltTeller = VoltTeller::select('Faktur', 'Tgl', 'Rekening', VoltTeller::raw('SUM(Debet) AS Penerimaan'), VoltTeller::raw('SUM(Kredit) AS Pengeluaran'), 'Keterangan')
                    ->where($k, "LIKE", '%' . $v . '%')
                    ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
                    ->groupBy('Faktur', 'Tgl', 'Keterangan', 'Rekening')
                    ->havingRaw("(Faktur LIKE 'AA%' AND SUM(Kredit) = 0) OR (Faktur LIKE 'ZZ%' AND SUM(Debet) = 0)")
                    ->paginate(10);
                // VoltTeller::where($k, "LIKE", '%'.$v.'%')->where("Rekening", "=", "1.100.01")->paginate(10);
                // $VoltTeller = VoltTeller::where($k, "LIKE", '%'.$v.'%')->where("Rekening", "=", "1.100.01")->paginate(10);
                return response()->json($VoltTeller);
            }
        }

        $tglAwal = $request->tglAwal;
        $tglAkhir = $request->tglAkhir;

        $VoltTeller = VoltTeller::select('Faktur', 'Tgl', 'Rekening', VoltTeller::raw('SUM(Debet) AS Penerimaan'), VoltTeller::raw('SUM(Kredit) AS Pengeluaran'), 'Keterangan')
            ->whereBetween('Tgl', [$tglAwal, $tglAkhir])
            ->groupBy('Faktur', 'Tgl', 'Keterangan', 'Rekening')
            ->havingRaw("(Faktur LIKE 'AA%' AND SUM(Kredit) = 0) OR (Faktur LIKE 'ZZ%' AND SUM(Debet) = 0)")
            ->paginate(10);

        return response()->json($VoltTeller);
    }

    public function getDataEdit(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $VoltTeller = DB::select("SELECT Kode, Qty, Nominal, Status, (Nominal*Qty) as Jumlah FROM jurnal_uangpecahan WHERE faktur = '$Faktur'");
            return response()->json($VoltTeller);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function getDataJurnalKasirByFaktur(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $VoltTeller = VoltTeller::select('jurnal.ID', 'jurnal.Faktur', 'jurnal.Tgl', 'jurnal.rekening', 'rekening.keterangan AS KeteranganRekening', 'jurnal.Debet AS Jumlah', 'jurnal.Keterangan')
                ->join('rekening', 'jurnal.rekening', '=', 'rekening.kode')
                ->where('jurnal.Faktur', '=', $Faktur)
                // ->where('jurnal.Debet', '!=', 0)
                ->get();

            $summaryData = [];

            if (count($VoltTeller) > 0) {
                // Mengambil data dari array pertama
                $summaryData['Faktur'] = $VoltTeller[0]['Faktur'];
                $summaryData['Tgl'] = $VoltTeller[0]['Tgl'];
                $summaryData['Rekening'] = $VoltTeller[0]['Rekening'];
                $summaryData['KeteranganRekening'] = $VoltTeller[0]['KeteranganRekening'];

                // Mengambil data dari array kedua
                if (count($VoltTeller) > 1) {
                    $summaryData['RekeningKredit'] = $VoltTeller[1]['Rekening'];
                    $summaryData['KeteranganRekeningKredit'] = $VoltTeller[1]['KeteranganRekening'];
                }

                // Menghitung total Jumlah
                $totalJumlah = 0;
                foreach ($VoltTeller as $item) {
                    $totalJumlah += $item['Jumlah'];
                }

                // Mengambil total Jumlah sebagai Mutasi dan Jumlah pada summaryData
                $summaryData['Mutasi'] = $totalJumlah;
                $summaryData['Jumlah'] = $totalJumlah;

                $summaryData['Keterangan'] = $VoltTeller[0]['Keterangan'];
            }

            return response()->json($summaryData);
        } catch (\Throwable $th) {
            return response()->json($th);
        }
    }

    function store(Request $request)
    {
        $Faktur = $request->Faktur;
        $Tgl = $request->Tgl;
        $Rekening = $request->Rekening;
        $RekeningKredit = $request->RekeningKredit;
        $Mutasi = $request->Mutasi;
        $keterangan = $request->Keterangan;
        try {
            $VoltTeller = VoltTeller::create([
                'Faktur' => $Faktur,
                'Tgl' => $Tgl,
                'Rekening' => $Rekening,
                'Debet' => 0,
                'Kredit' => $Mutasi,
                'Keterangan' => $keterangan,
            ]);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        try {
            $VoltTeller = VoltTeller::create([
                'Faktur' => $Faktur,
                'Tgl' => $Tgl,
                'Rekening' => $RekeningKredit,
                'Debet' => $Mutasi,
                'Kredit' => 0,
                'Keterangan' => $keterangan,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }

        GetterSetter::setLastFaktur('ZZ');
        return response()->json(['status' => 'success']);
    }

    function storeVolt(Request $request)
    {
        $Faktur = $request->Faktur;
        $Tgl = $request->Tgl;
        $Rekening = $request->Rekening;
        $RekeningKredit = $request->RekeningKredit;
        $Mutasi = $request->Mutasi;
        $keterangan = $request->Keterangan;
        try {
            $VoltTeller = VoltTeller::create([
                'Faktur' => $Faktur,
                'Tgl' => $Tgl,
                'Rekening' => $Rekening,
                'Debet' => $Mutasi,
                'Kredit' => 0,
                'Keterangan' => $keterangan,
            ]);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        try {
            $VoltTeller = VoltTeller::create([
                'Faktur' => $Faktur,
                'Tgl' => $Tgl,
                'Rekening' => $RekeningKredit,
                'Debet' => 0,
                'Kredit' => $Mutasi,
                'Keterangan' => $keterangan,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }

        GetterSetter::setLastFaktur('AA');
        return response()->json(['status' => 'success']);
    }

    function storeJurnalUangPecahan(Request $request)
    {
        try {
            $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
            foreach ($request->input('datauangpecahan') as $item) {
                try {
                    DB::table('jurnal_uangpecahan')->insert([
                        'Faktur' => $item['Faktur'],
                        'Tgl' => $item['Tgl'],
                        'Qty' => $item['Qty'],
                        'Kode' => $item['Kode'],
                        'Nominal' => $item['Nominal'],
                        'UserName' => "Topek",
                        'Keterangan' => $item['Keterangan'],
                    ]);
                    $Faktur = $item['Faktur'];
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }

            if ($allIterationsSuccessful) {
                return response()->json(['status' => 'success']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'One or more iterations failed']);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }
    function update(Request $request, $ID)
    {
        $VoltTeller = VoltTeller::where('ID', $ID)->update([
            'Faktur' => $request->Faktur,
            'Tgl' => $request->Tgl,
            'Rekening' => $request->Rekening,
            'Debet' => $request->Debet,
            'Kredit' => $request->Kredit,
            'Keterangan' => $request->Keterangan,
        ]);
        return response()->json(['status' => 'success']);
    }

    // function delete(Request $request)
    // {
    //     try {
    //         $VoltTeller = VoltTeller::findOrFail($request->ID);
    //         $VoltTeller->delete();
    //         return response()->json(['status' => 'success']);
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'error']);
    //     }
    // }

    function delete(Request $request)
    {
        try {
            $Faktur = $request->Faktur;
            $JurnalUangPecahan = DB::table('jurnal_uangpecahan')->where('Faktur', $Faktur);
            $JurnalUangPecahan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
