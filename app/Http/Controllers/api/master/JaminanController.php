<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\Jaminan;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class JaminanController extends Controller
{
    //
    function data(Request $request)
    {
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $Jaminan = Jaminan::select(
                'Kode',
                'Keterangan'
            )->where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($Jaminan);
        }
    }
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $Jaminan = Jaminan::select(
                'Kode',
                'Keterangan'
            )->get(10);
            return response()->json($Jaminan);
        }
    }
        $Jaminan = Jaminan::select(
            'Kode',
            'Keterangan'
        )->paginate(10);
        return response()->json($Jaminan);
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
            $Jaminan = Jaminan::create([
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
        $Jaminan = Jaminan::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        // return response()->json([$request]);
        try {
            $Jaminan = Jaminan::findOrFail($request->Kode);
            $Jaminan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
