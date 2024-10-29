<?php

namespace App\Http\Controllers\api\tks;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTKS;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PostingSettingController extends Controller
{
    // Untuk start process pada panel Posting
    public static function postingTKS(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUsername = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);

            // Ambil periode dan ubah menjadi tanggal format Y-m-d
            $dYear = $vaRequestData['Periode'];
            $dTgl = Carbon::createFromDate($dYear, 12, 31);
            $dTglFormatter = $dTgl->format('Y-m-d');

            // Proses sandi menjadi array atau string
            $vaSandi = $vaRequestData['Sandi'];
            if (is_array($vaSandi)) {
                $cSandi = implode(',', $vaSandi); // Ubah array jadi string dipisahkan koma
            } else {
                $cSandi = $vaSandi; // Jika sudah string
            }

            // Jika lebih dari satu sandi, gunakan whereIn untuk delete dan get
            if (is_array($vaSandi) && count($vaSandi) > 1) {
                // Menggunakan whereIn jika $vaSandi adalah array
                // DB::table('tkspearls')
                //     ->where('Periode', '=', $dTglFormatter)
                //     ->whereIn('Kategori', $vaSandi) // Gunakan whereIn untuk array
                //     ->delete();

                $vaData = DB::table('mastertkspearls')
                    ->select('Kategori', 'Sandi', 'Keterangan', 'Sasaran', 'Rumus', 'Jenis')
                    ->whereIn('Kategori', $vaSandi) // Gunakan whereIn untuk array
                    ->orderByDesc('ID')
                    ->orderByDesc('Sandi')
                    ->get();
            } else {
                // Jika hanya satu elemen, gunakan where biasa
                // DB::table('tkspearls')
                //     ->where('Periode', '=', $dTglFormatter)
                //     ->where('Kategori', '=', $cSandi) // Pastikan $cSandi adalah string
                //     ->delete();

                $vaData = DB::table('mastertkspearls')
                    ->select('Kategori', 'Sandi', 'Keterangan', 'Sasaran', 'Rumus', 'Jenis')
                    ->where('Kategori', '=', $cSandi) // Pastikan $cSandi adalah string
                    ->orderByDesc('ID')
                    ->orderByDesc('Sandi')
                    ->get();
            }
            $cSandiInduk = '';
            $cSandiDetail = '';
            foreach ($vaData as $data) {
                $cUrutSandi = $data->Sandi;
                $vaSandi = explode(" ", $cUrutSandi);
                $cSandi = isset($vaSandi[1]) ? $vaSandi[1] : null;
                $cJenis = $data->Jenis;

                if ($cJenis === "I") {
                    $cSandiInduk = $cSandi;
                } else {
                    $cSandiDetail = $cSandi;
                }
                $nPerhtungan = PerhitunganTKS::getNilaiMasukRekening($cSandiInduk, $cSandiDetail, $dYear);

                $vaArray = [
                    'Periode' => $dTglFormatter,
                    'Kategori' => $data->Kategori,
                    'Sandi' => $cUrutSandi,
                    'Keterangan' => $data->Keterangan,
                    'Sasaran' => $data->Sasaran,
                    'Rumus' => $data->Rumus,
                    'Saldo' => $nPerhtungan,
                    'Jenis' => $data->Jenis
                ];

                DB::table('tkspearls')->updateOrInsert(
                    [
                        'Periode' => $dTglFormatter,
                        'Kategori' => $cSandi,
                    ],
                    $vaArray
                );


                // DB::table('tkspearls')->insert($vaArray);
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // return response()->json(['status' => 'error']);
            throw $th;
        }
    }

    // Untuk mengisi data tabel pada masing-masing panel
    public static function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount < 1) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Posting Setting', 'data', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }

            $cKategori = $vaRequestData['Kategori'];

            $vaData = DB::table('mastertkspearls as m')
                ->select(
                    'm.Sandi',
                    'm.Keterangan',
                    'm.Config',
                    'c.Keterangan as Rekening',
                    'm.Jenis'
                )
                ->leftJoin('config as c', 'c.Kode', '=', 'm.Config')
                ->where('Kategori', '=', $cKategori)
                ->get();
            foreach ($vaData as $data) {
                $sandiArray = explode(' ', $data->Sandi);
                $cSandi = isset($sandiArray[1]) ? $sandiArray[1] : null;
                $vaArray[] = [
                    'Sandi' => $cSandi,
                    'Keterangan' => $data->Keterangan,
                    'Rekening' => $data->Rekening ?? '',
                    'Config' => $data->Config,
                    'Jenis' => $data->Jenis
                ];
            }
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaArray
                ];
                Func::writeLog('Posting Setting', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaArray);
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

            Func::writeLog('Posting Setting', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    // Untuk mengambil data
    public static function getData(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['page']);
        unset($vaRequestData['auth']);
        try {
            $vaArray = [];
            $vaData = DB::table('rekening')
                ->select('Keterangan')
                ->where('Kode', '=', $vaRequestData['Rekening'])
                ->first();
            if ($vaData) {
                $vaArray = [
                    'Keterangan' => $vaData->Keterangan
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaArray
            ];
            Func::writeLog('Posting Setting', 'getData', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaArray);
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
            Func::writeLog('Posting Setting', 'getData', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    // Untuk menyimpan (update) perubahan rekening
    public static function update(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);

            $keteranganFromRekeningList = '';

            // Cek jika RekeningList ada
            if (isset($vaRequestData['RekeningList'])) {
                $kodeRekeningSet = [];

                // Pastikan hanya kode rekening yang valid (tidak kosong) yang dimasukkan
                foreach ($vaRequestData['RekeningList'] as $rekening) {
                    // Hanya masukkan kode rekening yang tidak kosong
                    if (!empty($rekening['kodeRekening'])) {
                        // Pastikan tidak ada kode rekening duplikat
                        if (!in_array($rekening['kodeRekening'], $kodeRekeningSet)) {
                            $kodeRekeningSet[] = $rekening['kodeRekening'];
                        }
                    }
                }

                // Gabungkan kode rekening yang sudah difilter
                $keteranganFromRekeningList = implode('; ', $kodeRekeningSet);
            }

            // Hapus tanda pemisah di awal jika ada
            $keteranganFromRekeningList = ltrim($keteranganFromRekeningList, '; ');

            // Update Keterangan di DB
            GetterSetter::setDBConfig($vaRequestData['Kode'], $keteranganFromRekeningList);

            $retVal = ["status" => "00", "message" => "SUKSES"];
            Func::writeLog('Posting Setting', 'update', $vaRequestData, $retVal, $cUser);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            $retVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Posting Setting', 'update', $vaRequestData, $th, $cUser);
            return response()->json(['status' => 'error']);
        }
    }

    // Untuk menghapus rekening 
    public static function delete(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            GetterSetter::setDBConfig($vaRequestData['Kode'], "");

            $retVal = ["status" => "00", "message" => "SUKSES"];
            Func::writeLog('Posting Setting', 'update', $vaRequestData, $retVal, $cUser);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            $retVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Posting Setting', 'update', $vaRequestData, $th, $cUser);
            return response()->json(['status' => 'error']);
        }
    }
}
