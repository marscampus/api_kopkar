<?php

namespace App\Http\Controllers\api\pinjaman;

use App\Http\Controllers\Controller;
use App\Models\pinjaman\Notaris;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;

class NotarisController extends Controller
{
    function data(Request $request)
    {        
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $Notaris = Notaris::where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($Notaris);
        }
    }
        $Notaris = Notaris::paginate(10);
        return response()->json($Notaris);
    }
    
    function getNotarisByCIF(Request $request)
    {        
        $kode = $request->input('Kode');
        $query = Notaris::query();
        if (!empty($kode)) {
            $query->where('Kode', $kode);
        }
        $Notaris = $query->paginate(10);
        return response()->json($Notaris);
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        try {
            $Notaris = Notaris::create([
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
        $Notaris = Notaris::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $Notaris = Notaris::findOrFail($request->Kode);
            $Notaris->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
