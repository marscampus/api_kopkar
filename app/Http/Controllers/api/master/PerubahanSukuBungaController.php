<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\PerubahanSukuBunga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;

class PerubahanSukuBungaController extends Controller
{

    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nLimit = 10;
            $vaData = DB::table('detailsukubunga as d')
                ->select(
                    'd.Kode',
                    'd.Tgl',
                    'd.Maximum',
                    'd.SukuBunga',
                    'g.Keterangan'
                )
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 'd.Kode');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->orderByDesc('d.Tgl');
            if ($vaRequestData['page'] == null) {
                $vaData = $vaData->get();
            } else {
                $vaData = $vaData->paginate($nLimit);
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaData
                ];
                Func::writeLog('Perubahan Suku Bunga', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaData);
            }
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];

            Func::writeLog('Perubahan Suku Bunga', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $tgl = $request->Tgl;
        $Maximum = $request->Maximum;
        $SukuBunga = $request->SukuBunga;
        try {
            $PerubahanSukuBunga = PerubahanSukuBunga::create([
                'Kode' => $Kode,
                'Tgl' => $tgl,
                'Maximum' => $Maximum,
                'SukuBunga' => $SukuBunga
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    function update(Request $request, $Kode)
    {
        $PerubahanSukuBunga = PerubahanSukuBunga::where('Kode', $Kode)->update([
            'Tgl' => $request->Tgl,
            'Maximum' => $request->Maximum,
            'SukuBunga' => $request->SukuBunga,
        ]);
        return response()->json(['status' => 'success']);
    }

    function delete(Request $request)
    {
        try {
            $PerubahanSukuBunga = PerubahanSukuBunga::findOrFail($request->Kode);
            $PerubahanSukuBunga->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
