<?php

namespace App\Http\Controllers\api\konversi;

use App\Http\Controllers\Controller;
use App\Models\master\Aktiva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KonversiAktivaController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Hapus data berdasarkan tglCutOff
            $tglCutOff = !empty($request->tglCutOff) ? Carbon::parse($request->tglCutOff) : null;

            if ($tglCutOff) {
                Aktiva::where('TglPerolehan', '<', $tglCutOff)->delete();
            }

            $aktivaArray = $request->input('Data Aktiva', []);

            if (!empty($aktivaArray)) {
                // Gunakan metode chunk untuk menyisipkan data dalam batch
                $aktivaChunks = array_chunk($aktivaArray, 1000);

                foreach ($aktivaChunks as $chunk) {
                    // Dapatkan kode tertinggi sebelumnya di luar loop
                    $highestKode = Aktiva::max('Kode');
                    $nextNumber = ($highestKode) ? (intval(substr($highestKode, 4)) + 1) : 1;

                    $dataToInsert = [];

                    foreach ($chunk as $data) {
                        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                        $aktivaData = [
                            "Kode" => "101." . $formattedNumber,
                            "Nama" => $data['NAMA'],
                            "TglPerolehan" => $data['TGL PEROLEHAN'],
                            "TglPenyusutan" => $data['TGL PENYUSUTAN'],
                            "StartPenyusutan" => $data['MULAI PENYUSUTAN'],
                            "TarifPenyusutan" => $data['TARIF PENYUSUTAN'],
                            "Golongan" => $data['GOLONGAN'],
                            "Kelompok" => $data['KELOMPOK'],
                            "Lama" => $data['LAMA'],
                            "JenisPenyusutan" => $data['JENIS PENYUSUTAN'],
                            "HargaPerolehan" => $data['HARGA PEROLEHAN'],
                            "Residu" => $data['RESIDU'],
                            "PenyusutanPerBulan" => $data['PENYUSUTAN PERBULAN'],
                            "Unit" => $data['UNIT'],
                            "RekPenyusutan" => $data['REK PENYUSUTUAN'],
                            "RekBiayaPenyusutan" => $data['REK BIAYA PENYUSUTAN'],
                            "Status" => $data['STATUS'],
                            "NilaiBuku" => $data['NILAI BUKU'],
                            "PenyusutanAwal" => $data['PENYUSUTAN AWAL'],
                        ];

                        // Tambahkan data ke array yang akan disisipkan
                        $dataToInsert[] = $aktivaData;

                        $nextNumber++;
                    }

                    // Gunakan metode insert untuk menyisipkan data dalam batch
                    Aktiva::insert($dataToInsert);
                }

                DB::commit();

                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            dd($th); // Ini untuk debugging, Anda bisa menggantinya dengan respons error jika diperlukan
            return response()->json(['status' => 'error']);
        }
    }

    public function storess(Request $request)
    {
        // dd($request->all());
        try {
            // Hapus data berdasarkan tglCutOff
            $tglCutOff = !empty($request->tglCutOff) ? Carbon::parse($request->tglCutOff) : null;
            // $tglCutOff = $request->tglCutOff;
            if ($tglCutOff) {
                Aktiva::where('TglPerolehan', '<', $tglCutOff)->delete();
            }
            $aktivaArray = $request->input('Data Aktiva', []);
            if (!empty($aktivaArray)) {
                $highestKode = Aktiva::max('Kode');
                $nextNumber = ($highestKode) ? (intval(substr($highestKode, 4)) + 1) : 1;

                foreach ($aktivaArray as $data) {
                    $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    // dd($nextNumber);
                    $aktivaData = [
                        "Kode" => "101." . $formattedNumber,
                        "Nama" => $data['NAMA'],
                        "TglPerolehan" => $data['TGL PEROLEHAN'],
                        "TglPenyusutan" => $data['TGL PENYUSUTAN'],
                        "StartPenyusutan" => $data['MULAI PENYUSUTAN'],
                        "TarifPenyusutan" => $data['TARIF PENYUSUTAN'],
                        "Golongan" => $data['GOLONGAN'],
                        "Kelompok" => $data['KELOMPOK'],
                        "Lama" => $data['LAMA'],
                        "JenisPenyusutan" => $data['JENIS PENYUSUTAN'],
                        "HargaPerolehan" => $data['HARGA PEROLEHAN'],
                        "Residu" => $data['RESIDU'],
                        "PenyusutanPerBulan" => $data['PENYUSUTAN PERBULAN'],
                        "Unit" => $data['UNIT'],
                        "RekPenyusutan" => $data['REK PENYUSUTUAN'],
                        "RekBiayaPenyusutan" => $data['REK BIAYA PENYUSUTAN'],
                        "Status" => $data['STATUS'],
                        "NilaiBuku" => $data['NILAI BUKU'],
                        "PenyusutanAwal" => $data['PENYUSUTAN AWAL'],
                    ];
                    // dd($aktivaData['Kode']);

                    // Mencari data dengan Kode yang sama di database
                    // $existingAktiva = Aktiva::where('Kode', $aktivaData['Kode'])->first();
                    // if ($existingAktiva) {
                    //     dd('masuk exist');
                    //     $existingAktiva->update($aktivaData);
                    // } else {
                        Aktiva::create($aktivaData);
                        $nextNumber++;
                    // }
                }
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            dd($th); // Ini untuk debugging, Anda bisa menggantinya dengan respons error jika diperlukan
            return response()->json(['status' => 'error']);
        }
    }
}
