<?php

namespace App\Http\Controllers\api\konversi;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\jurnal\Jurnal;
use App\Models\master\Rekening;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KonversiNeracaController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff = $vaRequestData->tglCutOff;
            // Hapus data berdasarkan tglCutOff
            if ($dTglCutOff) {
                Jurnal::where('Tgl', '<', $$dTglCutOff)->chunk(200, function ($jurnals) {
                    $jurnals->each->delete();
                });
                BukuBesar::where('Tgl', '<', $$dTglCutOff)->chunk(200, function ($bukuBesar) {
                    $bukuBesar->each->delete();
                });
            }

            if (!empty($request->all())) {
                foreach ($request->except('tglCutOff') as $kategori => $rekeningKategori) {
                    if ($rekeningKategori === "tglCutOff") {
                        continue; // Skip tglCutOff
                    }

                    foreach ($rekeningKategori as $data) {
                        $faktur = GetterSetter::getLastFaktur("JR", 8);
                        $saldoAwal = $data['JENIS'] === 'I' ? 0 : $data['SALDOAWAL'];

                        // Rekening
                        $rekeningData = [
                            "Kode" => $data['REKENING'],
                            "Jenis" => $data['JENIS'],
                            "Cabang" => "101",
                            "Keterangan" => $data['KETERANGAN'],
                        ];
                        $existingRekening = Rekening::where('Kode', $data['REKENING'])->first();
                        if ($existingRekening) {
                            $existingRekening->update($rekeningData);
                        } else {
                            Rekening::create($rekeningData);
                        }

                        // Jurnal
                        $jurnalData = [
                            "Faktur" => $faktur,
                            "Rekening" => $data['REKENING'],
                            "Tgl" => $tglCutOff,
                            "Keterangan" => "[" . $data['REKENING'] . "] " . $data['KETERANGAN'],
                            "Debet" => in_array(substr($data['REKENING'], 0, 1), ['1', '5']) ? $saldoAwal : 0,
                            "Kredit" => in_array(substr($data['REKENING'], 0, 1), ['1', '5']) ? 0 : $saldoAwal,
                        ];
                        $existingJurnal = Jurnal::where('Faktur', $faktur)->first();
                        if ($existingJurnal) {
                            $existingJurnal->update($jurnalData);
                        } else {
                            Jurnal::create($jurnalData);
                            GetterSetter::setLastFaktur("JR");
                        }

                        // Buku Besar
                        $isDebet = in_array(substr($data['REKENING'], 0, 1), ['1', '5']);
                        $debetAmount = $isDebet ? $saldoAwal : 0;
                        $kreditAmount = $isDebet ? 0 : $saldoAwal;
                        $bukuBesarData = [
                            "Cabang" => "101",
                            "Faktur" => $faktur,
                            "Tgl" => $tglCutOff,
                            "Rekening" => $data['REKENING'],
                            "Keterangan" => "[" . $data['REKENING'] . "] " . $data['KETERANGAN'],
                            "Debet" => $debetAmount,
                            "Kredit" => $kreditAmount,
                            "UserName" => $cUser,
                            "Kas" => "N",
                            "StatusPrinter" => "0",
                            "StatusKirim" => "0",
                        ];

                        $existingBukuBesar = BukuBesar::where('Faktur', $faktur)->first();
                        if ($existingBukuBesar) {
                            $existingBukuBesar->update($bukuBesarData);
                        } else {
                            BukuBesar::create($bukuBesarData);
                        }
                    }
                }

                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th); // Ini untuk debugging, Anda bisa menggantinya dengan respons error jika diperlukan
            return response()->json(['status' => 'error']);
        }
    }



    public function storeKU(Request $request)
    {
        try {
            // Hapus data berdasarkan tglCutOff
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff=$vaRequestData->tglCutOff;
            if ($dTglCutOff) {
                Jurnal::where('Tgl', '<', $dTglCutOff)->delete();
                BukuBesar::where('Tgl', '<', $dTglCutOff)->delete();
            }
            if (!empty($request->all())) {
                foreach ($request->except('tglCutOff') as $kategori => $rekeningKategori) {
                    if ($rekeningKategori === "tglCutOff") {
                        continue; // Skip tglCutOff
                    }
                    foreach ($rekeningKategori as $data) {
                        $faktur = GetterSetter::getLastFaktur("JR", 8);
                        $saldoAwal = $data['JENIS'] === 'I' ? 0 : $data['SALDOAWAL'];
                        // ------------------------------------------------------------------------------------------------------- Rekening
                        $rekeningData = [
                            "Kode" => $data['REKENING'],
                            "Jenis" => $data['JENIS'],
                            "Cabang" => "101",
                            "Keterangan" => $data['KETERANGAN'],
                        ];
                        // Mencari data dengan Kode yang sama di database
                        $existingRekening = Rekening::where('Kode', $data['REKENING'])->first();
                        if ($existingRekening) {
                            // dd('masuk exist Rekening');
                            $existingRekening->update($rekeningData);
                        } else {
                            // Jika data belum ada, maka buat data baru
                            Rekening::create($rekeningData);
                        }

                        // ------------------------------------------------------------------------------------------------------- Jurnal
                        // $faktur = GetterSetter::getLastFaktur("JR", 8);
                        $jurnalData = [
                            "Faktur" => $faktur,  // di tabel Jurnal2 nambah panjang nilai faktur jadi 25
                            "Rekening" => $data['REKENING'],
                            "Tgl" => $dTglCutOff,
                            "Keterangan" => "[" . $data['REKENING'] . "] " . $data['KETERANGAN'],
                            "Debet" => in_array(substr($data['REKENING'], 0, 1), ['1', '5']) ? $saldoAwal : 0,
                            "Kredit" => in_array(substr($data['REKENING'], 0, 1), ['1', '5']) ? 0 : $saldoAwal,
                            // Tambahkan field jurnal lainnya sesuai kebutuhan
                        ];
                        $existingJurnal = Jurnal::where('Faktur', $faktur)->first();
                        if ($existingJurnal) {
                            dd('masuk exist Jurnal');
                            $existingJurnal->update($jurnalData);
                        } else {
                            Jurnal::create($jurnalData);
                            GetterSetter::setLastFaktur("JR");
                        }

                        // -------------------------------------------------------------------------------------------------------  Buku Besar
                        // atur yang masuk debet kredit
                        $isDebet = in_array(substr($data['REKENING'], 0, 1), ['1', '5']);
                        $debetAmount = $isDebet ? $saldoAwal : 0;
                        $kreditAmount = $isDebet ? 0 : $saldoAwal;
                        $bukuBesarData = [
                            "Cabang" => "101",
                            "Faktur" => $faktur,
                            "Tgl" => $dTglCutOff,
                            "Rekening" => $data['REKENING'],
                            "Keterangan" => "[" . $data['REKENING'] . "] " . $data['KETERANGAN'],
                            "Debet" => $debetAmount,
                            "Kredit" => $kreditAmount,
                            "UserName" => $cUser,
                            "Kas" => "N",
                            "StatusPrinter" => "0",
                            "StatusKirim" => "0",
                        ];

                        $existingBukuBesar = BukuBesar::where('Faktur', $faktur)->first();
                        if ($existingBukuBesar) {
                            dd('masuk exist Buku Besar');
                            $existingBukuBesar->update($bukuBesarData);
                        } else {
                            // Membuat entri BukuBesar untuk setiap rekening
                            BukuBesar::create($bukuBesarData);
                            // GetterSetter::setLastFaktur("JR");
                        }
                    }
                }
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            dd($th); // Ini untuk debugging, Anda bisa menggantinya dengan respons error jika diperlukan
            return response()->json(['status' => 'error']);
        }
    }
}
