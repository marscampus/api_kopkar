<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\master\ProvisiDanAdministrasi;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;

class ProvisiDanAdministrasiController extends Controller
{

    function data(Request $request)
    {
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $ProvisiDanAdministrasi = ProvisiDanAdministrasi::select(
                'GolonganKredit',
                'Lama',
                'Provisi',
                'Administrasi'
            )->where($k, "LIKE", '%'.$v.'%')->paginate(10);
            return response()->json($ProvisiDanAdministrasi);
        }
    }
        if(!empty($request->filters)){
        foreach($request->filters as $k => $v){
            $ProvisiDanAdministrasi = ProvisiDanAdministrasi::select(
                'GolonganKredit',
                'Lama',
                'Provisi',
                'Administrasi'
            )->get();
            return response()->json($ProvisiDanAdministrasi);
        }
    }
            $ProvisiDanAdministrasi = ProvisiDanAdministrasi::select(
            'GolonganKredit',
            'Lama',
            'Provisi',
            'Administrasi'
        )->paginate(10);
        return response()->json($ProvisiDanAdministrasi);
            }
    function store(Request $request)
    {
        $GolonganKredit = $request->GolonganKredit;
        $lama = $request->Lama;
        $provisi = $request->Provisi;
        $administrasi = $request->Administrasi;
        try {
            $ProvisiDanAdministrasi = ProvisiDanAdministrasi::create([
                'GolonganKredit' => $GolonganKredit,
                'Lama' => $lama,
                'Provisi' => $provisi,
                'Administrasi' => $administrasi,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $GolonganKredit)
    {
        $ProvisiDanAdministrasi = ProvisiDanAdministrasi::where('GolonganKredit', $GolonganKredit)->update([
            'Lama' => $request->Lama,
            'Provisi' => $request->Provisi,
            'Administrasi' => $request->Administrasi
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $ProvisiDanAdministrasi = ProvisiDanAdministrasi::findOrFail($request->GolonganKredit);
            $ProvisiDanAdministrasi->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
