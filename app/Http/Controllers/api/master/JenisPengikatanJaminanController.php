<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\JenisPengikatanJaminan;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class JenisPengikatanJaminanController extends Controller
{
    //
    function data(Request $request)
    {
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $JenisPengikatanJaminan = JenisPengikatanJaminan::select(
                'Kode',
                'Keterangan',
                'Prosentase',
                'Sid'
            )->where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($JenisPengikatanJaminan);
        }
    }
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $JenisPengikatanJaminan = JenisPengikatanJaminan::select(
                'Kode',
                'Keterangan',
                'Prosentase',
                'Sid',
            )->get(10);
            return response()->json($JenisPengikatanJaminan);
        }
    }
        $JenisPengikatanJaminan = JenisPengikatanJaminan::select(
            'Kode',
                'Keterangan',
                'Prosentase',
                'Sid'
        )->paginate(10);
        return response()->json($JenisPengikatanJaminan);
    }

    function store(Request $request)
    {
        // $request->validate([
        //     'Kode'=> 'required|max:4',
        //     'KETERANGAN'=>'required'
        // ]);
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        $prosentase = $request->Prosentase;
        $sid = $request->Sid;
        try {
            $JenisPengikatanJaminan = JenisPengikatanJaminan::create([
                'Kode' => $Kode,
                'Keterangan' => $keterangan,
                'Prosentase' => $prosentase,
                'Sid' => $sid
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $JenisPengikatanJaminan = JenisPengikatanJaminan::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan,
            'Prosentase' => $request->Prosentase,
            'Sid' => $request->Sid
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        // return response()->json([$request]);
        try {
            $JenisPengikatanJaminan = JenisPengikatanJaminan::findOrFail($request->Kode);
            $JenisPengikatanJaminan->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
