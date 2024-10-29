<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\SuratKredit;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class SuratKreditController extends Controller
{
    function data(Request $request)
    {
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $SuratKredit = SuratKredit::select(
                'Kode',
                'Keterangan',
                'FileName',
                'File'
            )->where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($SuratKredit);
        }
    }
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $SuratKredit = SuratKredit::select(
                'Kode',
                'Keterangan',
                'FileName',
                'File'
            )->get();
            return response()->json($SuratKredit);
        }
    }
        $SuratKredit = SuratKredit::select(
                'Kode',
                'Keterangan',
                'FileName',
                'File'
        )->paginate(10);
        return response()->json($SuratKredit);
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $keterangan = $request->Keterangan;
        $FileName = $request->FileName;
        $File = $request->File;
        try {
            $SuratKredit = SuratKredit::create([
                'Kode' => $Kode,
                'Keterangan' => $keterangan,
                'FileName' => $FileName,
                'File' => $File
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $SuratKredit = SuratKredit::where('Kode', $Kode)->update([
            'Keterangan' => $request->Keterangan,
            'FileName' => $request->FileName,
            'File' => $request->File
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $SuratKredit = SuratKredit::findOrFail($request->Kode);
            $SuratKredit->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
