<?php

namespace App\Http\Controllers\api\pinjaman;

use App\Http\Controllers\Controller;
use App\Models\pinjaman\Asuransi;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;

class AsuransiController extends Controller
{
    function data(Request $request)
    {        
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $Asuransi = Asuransi::where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($Asuransi);
        }
    }
        $Asuransi = Asuransi::paginate(10);
        return response()->json($Asuransi);
    }
    
    function getAsuransiByCIF(Request $request)
    {        
        $kode = $request->input('Kode');
        $query = Asuransi::query();
        if (!empty($kode)) {
            $query->where('Kode', $kode);
        }
        $Asuransi = $query->paginate(10);
        return response()->json($Asuransi);
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        try {
            $Asuransi = Asuransi::create([
                'Kode' => $Kode,
                'Keterangan' => $keterangan
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $Asuransi = Asuransi::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $Asuransi = Asuransi::findOrFail($request->Kode);
            $Asuransi->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
