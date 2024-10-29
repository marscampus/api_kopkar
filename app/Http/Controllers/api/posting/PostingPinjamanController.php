<?php

namespace App\Http\Controllers\api\posting;

use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostingPinjamanController extends Controller
{
    public function PostingDataDouble(Request $request)
    {
        ini_set('max_execution_time', '0');
        $tgl = $request->Tgl; // Mengambil nilai tgl dari request

        $hasil = DB::table('angsuran')
            ->select('*')
            ->where('Tgl', '=', $tgl)
            ->get();
        // dd($hasil);
        foreach ($hasil as $data) {
            $hasilBukuBesar = DB::table('bukubesar')
                ->select('*')
                ->where('Faktur', '=', $data->Faktur)
                ->where('Tgl', '=', $tgl) // Menambahkan kondisi untuk tanggal hari ini
                ->get();

            // var_dump($hasilBukuBesar);
            if ($hasilBukuBesar->isNotEmpty()) {
                echo ' Ketemu ' . $data->Faktur . ' ------- ';
            } else {
                echo ' Tidak Ketemu ' . $data->Faktur . ' ------- ';
            }
        }
    }

    function PostingDataKredit(Request $request)
    {
        ini_set('max_execution_time', '0');
        $cTgl = $request->Tgl; // Mengambil nilai tgl dari request
        $dbData = DB::table('angsuran')
            ->select('Faktur', 'Status')
            ->where('tgl', '=', $cTgl)
            ->groupBy('Faktur', 'status')
            ->get();

        foreach ($dbData as $dbRow) {
            // echo $dbRow->Faktur.'------'.$dbRow->Status."<br>";
            if ($dbRow->Status == "5") {
                Upd::UpdRekAngsuranPembiayaan($dbRow->Faktur);
                // echo "UpdRekAngsuranPembiayaan " . ($dbRow->Faktur) . "<br>";
            } elseif ($dbRow->Status == "6") {
                // UpdRekAngsuranPembiayaanRekeningKoran($dbRow->Faktur);
                echo "UpdRekAngsuranPembiayaanRekeningKoran " . ($dbRow->Faktur) . "<br>";
            } else {
                echo "UpdRekeningRealisasiKredit " . ($dbRow->Faktur) . "<br>";
                Upd::UpdRekeningRealisasiKredit($dbRow->Faktur);
            }
        }
    }
}
