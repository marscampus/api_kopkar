<?php

namespace App\Http\Controllers\api\pinjaman;

use App\Http\Controllers\Controller;
use App\Models\pinjaman\Agunan;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;

class AgunanController extends Controller
{
    function data(Request $request)
    {        
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $Agunan = Agunan::where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($Agunan);
        }
    }
        $Agunan = Agunan::paginate(10);
        return response()->json($Agunan);
    }
    
    function getAgunanByCIF(Request $request)
    {        
        $kode = $request->input('Kode');
        $query = Agunan::query();
        if (!empty($kode)) {
            $query->where('Kode', $kode);
        }
        $Agunan = $query->paginate(10);
        return response()->json($Agunan);
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        try {
            $Agunan = Agunan::create([
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
        $Agunan = Agunan::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $Agunan = Agunan::findOrFail($request->Kode);
            $Agunan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
