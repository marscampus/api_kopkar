<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\KasMasuk;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;



class KasMasukController extends Controller
{
    function data(Request $request)
    {
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $KasMasuk = KasMasuk::where($k, "LIKE", '%' . $v . '%')->paginate(10);
                return response()->json($KasMasuk);
            }
        }
        // $KasMasuk = KasMasuk::where("Rekening", "=", "1.100.01")->paginate(10);
        $KasMasuk = KasMasuk::paginate(10);
        return response()->json($KasMasuk);
    }

    function store(Request $request)
    {
        $Faktur = "";
        // $data = $request->all();
        try {
            foreach ($request->input('datadebet') as $item) {
                $KasMasuk = KasMasuk::create([
                    'Faktur'        => $item['Faktur'],
                    'Tgl'           => $item['Tgl'],
                    'Rekening'      => $item['Rekening'],
                    'Debet'         => 0,
                    'Kredit'        =>  $item['Jumlah'],
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
            GetterSetter::setLastFaktur('KM');
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'success']);
        }
        // return response()->json($request);

    }

    function update(Request $request, $ID)
    {
        $KasMasuk = KasMasuk::where('ID', $ID)->update([
            'Faktur' => $request->Faktur,
            'Tgl' => $request->Tgl,
            'Rekening' => $request->Rekening,
            'Debet' => $request->Debet,
            'Kredit' => $request->Kredit,
            'Keterangan' => $request->Keterangan,
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $KasMasuk = KasMasuk::findOrFail($request->ID);
            $KasMasuk->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
