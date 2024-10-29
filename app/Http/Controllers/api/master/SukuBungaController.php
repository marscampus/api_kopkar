<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\SukuBunga;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class SukuBungaController extends Controller
{
    //
    function data(Request $request)
    {
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $SukuBunga = SukuBunga::select(
                'Kode',
                'Keterangan'
            )->where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($SukuBunga);
        }
    }
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $SukuBunga = SukuBunga::select(
                'Kode',
                'Keterangan'
            )->get();
            return response()->json($SukuBunga);
        }
    }
        $SukuBunga = SukuBunga::select(
            'Kode',
            'Keterangan'
        )->paginate(10);
        return response()->json($SukuBunga);
    }

    function store(Request $request)
    {
        // $request->validate([
        //     'Kode'=> 'required|max:4',
        //     'KETERANGAN'=>'required'
        // ]);
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        try {
            $SukuBunga = SukuBunga::create([
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
        // $request->validate([
        //     'Kode'=> 'required|max:4',
        //     'KETERANGAN'=>'required'
        // ]);
        $SukuBunga = SukuBunga::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        // return response()->json([$request]);
        try {
            $SukuBunga = SukuBunga::findOrFail($request->Kode);
            $SukuBunga->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
