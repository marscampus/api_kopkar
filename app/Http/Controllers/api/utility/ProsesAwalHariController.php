<?php

namespace App\Http\Controllers\api\utility;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\fun\TglTransaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProsesAwalHariController extends Controller
{

    public function tampilTgl()
    {
        $data = DB::table('tgltransaksi')
            ->select('Status', 'Tgl')
            ->get();
        foreach ($data as $d) {
            $color = "black";
            $status = $d->Status;
            if ($status == '0') {
                $color = "blue";
            } else {
                $color = "#808080";
            }
            $array[] = [
                'date' => $d->Tgl,
                'display' => 'background',
                'backgroundColor' => $color
            ];
        }
        return $array;
    }

    public function cekTglTransaksi(Request $request)
    {
        $tgl = $request->Tgl;
        $string2Date = Carbon::parse($tgl)->format('Y-m-d');
        // Mencari apakah ada data yang memiliki status sama dengan 0
        $datesNotClosed = DB::table('tgltransaksi')
            ->select('Tgl')
            ->where('status', '=', 0)
            ->where('Tgl', '!=', $string2Date)
            ->get();

        if ($datesNotClosed->isNotEmpty()) {
            $dates = $datesNotClosed->pluck('Tgl')->toArray();
            $formattedDates = [];
            foreach ($dates as $date) {
                $formattedDate = Carbon::parse($date)->format('d-m-Y');
                $formattedDates[] = $formattedDate;
            }
            $message = 'Tanggal ' . implode(', ', $formattedDates) . ' Belum Ditutup!';
            return response()->json(['status' => 'error', 'message' => $message]);
        } else {
            return response()->json(['status' => 'success']);
        }
    }

    public function cekStatusTgl(Request $request)
    {
        try {
            $tgl = $request->Tgl;
            $string2Date = Carbon::parse($tgl)->format('Y-m-d');
            // Cek dulu apakah tanggal sudah masuk ke tabel atau belum
            $exists = DB::table('tgltransaksi')
                ->where('Tgl', '=', $string2Date)
                ->exists();
            //Jika ada maka cek statusnya, apakah sudah ditutup atau belum
            if ($exists) {
                $data = DB::table('tgltransaksi')
                    ->select('Status')
                    ->where('Tgl', '=', $string2Date)
                    ->first();
                if ($data) {
                    // Jika sudah ditutup, apakah mw dibuka kembali
                    if ($data->Status == '1') {
                        return response()->json(['status' => 'error', 'message' => 'Tgl. Transaksi Sudah Ditutup, Apakah Mau Dibuka Kembali ?']);
                    } else {
                        return response()->json(['status' => 'error', 'message' => 'Apakah Tgl. Transaksi Mau Ditutup ?']);
                    }
                }
            } else {
                $array = ['Tgl' => $string2Date];
                TglTransaksi::create($array);
                return response()->json(['status' => 'success', 'message' => 'Memulai Proses Awal Hari ' . Carbon::parse($tgl)->format('d-m-Y')]);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public function saveTglTransaksi(Request $request)
    {
        $tgl = $request->Tgl;
        $string2Date = Carbon::parse($tgl)->format('Y-m-d');
        // Jika ga ada tambahkan ke tabel
        $array = ['Tgl' => $string2Date];
        TglTransaksi::create($array);
    }

    public function updTglTransaksi(Request $request)
    {
        $tgl = $request->Tgl;
        $string2Date = Carbon::parse($tgl)->format('Y-m-d');
        $data = DB::table('tgltransaksi')
            ->select('Status', 'Tgl')
            ->where('Tgl', '=', $string2Date)
            ->first();
        if ($data) {
            if ($data->Status == 1) {
                $array = ['Status' => 0];
                TglTransaksi::where('Tgl', '=', $string2Date)->update($array);
                return response()->json(['status' => 'success', 'message' => 'Tanggal Transaksi Berhasil Dibuka!']);
            } else {
                $array = ['Status' => 1];
                TglTransaksi::where('Tgl', '=', $string2Date)->update($array);
                return response()->json(['status' => 'success', 'message' => 'Tanggal Transaksi Berhasil Ditutup!']);
            }
        }
    }
}
