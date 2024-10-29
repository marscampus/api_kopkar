<?php

namespace App\Http\Controllers\api\master;

use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use App\Http\Controllers\Controller;
use App\Models\master\Agama;
use Illuminate\Support\Facades\DB;
use Psy\CodeCleaner\ReturnTypePass;

class CustomController extends Controller
{
    public function data(Request $request, Connection $connection)
    {
        $customQuery = $request->input('custom_query');
        if (!empty($customQuery)) {
            try {
                // Gunakan connection untuk mengeksekusi query kustom
                $results = $connection->select($customQuery);
                return response()->json($results);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Query execution failed'], 500);
            }
        }

        // Jika tidak ada query kustom yang diberikan, gunakan query default
        $results = DB::table('agama')->paginate(10);
        return response()->json($results);
    }


    function data2(Request $request)
    {
        if (!empty($request->filters)) {
            foreach ($request->filters as $k => $v) {
                $Agama = Agama::where($k, "LIKE", '%' . $v . '%')->paginate(10);
                return response()->json($Agama);
            }
        }
        $Agama = Agama::paginate(10);
        return response()->json($Agama);
    }
}
