<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use App\Models\simpananberjangka\Bilyet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BilyetSimpananBerjangkaController extends Controller
{
    function data(Request $request)
    {
        if (!empty($request->filter)) {
            foreach ($request->filters as $k => $v) {
                $data = DB::table('bilyetdeposito')
                    ->select(
                        'Kode',
                        'Keterangan',
                        'FileName',
                        'File'
                    )
                    ->where($k, 'LIKE', '%' . $v . '%')
                    ->get();
            }
        }
        $data = DB::table('bilyetdeposito')
            ->select(
                'Kode',
                'Keterangan',
                'FileName',
                'File'
            )
            ->paginate(10);
        return response()->json($data);
    }

    function store(Request $request)
    {
        try {
            Bilyet::create([
                'Kode' => $request->Kode,
                'Keterangan' => $request->Keterangan,
                'FileName' => $request->FileName,
                'File' => $request->File
            ]);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request)
    {
        try {
            Bilyet::where('Kode', $request->Kode)->update([
                'Keterangan' => $request->Keterangan,
                'FileName' => $request->FileName,
                'File' => $request->File
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $bilyet = Bilyet::findOrFail($request->Kode);
            $bilyet->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }
}
