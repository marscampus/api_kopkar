<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetterSetter;

class PictureController extends Controller
{
    function store(Request $request)
    {
        $Kode = $request->Kode;
        $Jenis = $request->Jenis;
        $ID = $request->ID;
        $Picture = $request->Picture;
        $StatusTeller = $request->StatusTeller;
        $Tgl = $request->Tgl;
    try {
        // Simpan data ke database menggunakan DB facade
        DB::table('picture')->insert([
            'Kode' => $Kode,
            'Jenis' => 'Pr',
            'ID' => $ID,
            'Picture' => $Picture,
            'StatusTeller' => $StatusTeller,
            'Tgl' => $Tgl,
        ]);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    // function data(Request $request)
    // {
    //     $Kode = $request->Kode;
    //     $query = DB::table('picture')->select('Kode', 'Picture', 'ID')
    //                                  ->where('Kode', $Kode);
    //     $picture = $query->get();
    //     return response()->json($picture);
    // }

    function data(Request $request)
{
    $Kode = $request->Kode;
    $query = DB::table('picture')->select('Kode', 'Picture', 'ID');

    // Tambahkan kondisi if untuk mengecek $request->Like
    if ($request->Like == '1') {
        $query->where('Kode', 'like', $Kode . '0%');
    } else {
        $query->where('Kode', $Kode);
    }

    $picture = $query->get();
        return response()->json($picture);
    }

    function dataKasir(Request $request)
    {
        $Kode = $request->Kode;

        if (strlen($Kode) >= 4 && $Kode[3] !== '0') {
            $Kode = GetterSetter::getKode($Kode);
        }

        $query = DB::table('picture')->select('Kode', 'Picture', 'ID')
                                    ->where('Kode', $Kode)
                                    ->orderBy('ID', 'desc')
                                    ->limit(3);
        $picture = $query->get();
        return response()->json($picture);
    }

    function delete(Request $request)
{
    $Kode = $request->Kode;
    $ID = $request->ID;

    $query = DB::table('picture')->where('Kode', $Kode);

    // Tambahkan kondisi where untuk ID jika ID diisi
    if ($ID) {
        $query->where('ID', $ID);
    }

    $deletedRows = $query->delete();
    if ($deletedRows > 0) {
        return response()->json(['status' => 'success']);
    } else {
        return response()->json(['status' => 'error']);
    }
}
}
