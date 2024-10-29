<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\KasKeluar;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;



class KasKeluarController extends Controller
{
    function data(Request $request)
    {
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $KasKeluar = KasKeluar::where($k, "LIKE", '%' . $v . '%')->paginate(10);
                return response()->json($KasKeluar);
            }
        }
        $KasKeluar = KasKeluar::where("Rekening", "=", "1.100.01")->paginate(10);
        return response()->json($KasKeluar);
    }

    function store(Request $request)
    {
        $Faktur = "";
        // $data = $request->all();
        try {
            foreach ($request->input('datadebet') as $item) {
                $KasKeluar = KasKeluar::create([
                    'Faktur'        => $item['Faktur'],
                    'Tgl'           => $item['Tgl'],
                    'Rekening'      => $item['Rekening'],
                    'Debet'         => $item['Jumlah'],
                    'Kredit'        => 0,
                    'Keterangan'    => $item['Keterangan'],
                    'UserName'      => "Mars" //$item['UserName'],
                ]);
                $Faktur = $item['Faktur'];
            }
        } catch (\Throwable $th) {
            return response()->json($th);
            // return response()->json($request);
        }

        if (substr($Faktur, 0, 2) == "KM") {
            return response()->json(['status' => 'success']);
        } else {
            GetterSetter::setLastFaktur('KK');
            return response()->json(['status' => 'success']);
        }
        // return response()->json($request);
    }

    // function update(Request $request, $Faktur)
    // {
    //     $KasKeluar = KasKeluar::where('Faktur', $Faktur)->update([
    //         'Faktur' => $request->Faktur,
    //         'Tgl' => $request->Tgl,
    //         'Rekening' => $request->Rekening,
    //         'Debet' => $request->Debet,
    //         'Kredit' => $request->Kredit,
    //         'Keterangan' => $request->Keterangan,
    //     ]);
    //     return response()->json(['status' => 'success']);
    // }

    function update(Request $request)
    {
        try {
            $items = $request->input('dataupdate');
            foreach ($items as $item) {
                $ID = $item['ID'];
                $KasKeluar = KasKeluar::where('ID', $ID)->update([
                    'Faktur' => $item['Faktur'],
                    'Tgl' => $item['Tgl'],
                    'Rekening' => $item['Rekening'],
                    'Debet' => $item['Debet'],
                    'Kredit' => $item['Kredit'],
                    'Keterangan' => $item['Keterangan'],
                ]);
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    function delete(Request $request)
    {
        try {
            $KasKeluar = KasKeluar::findOrFail($request->ID);
            $KasKeluar->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
