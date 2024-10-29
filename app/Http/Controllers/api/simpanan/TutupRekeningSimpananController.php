<?php

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\master\RegisterNasabah;
use App\Models\master\Rekening;
use App\Models\simpanan\Tabungan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutupRekeningSimpananController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            $nLimit = 10;
            // if ($nReqCount < 2) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Tutup Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $vaData = DB::table('tabungan as t')
                ->select('t.Rekening AS Rekening', 'g.Keterangan as JenisTabungan', 'r.Nama AS Nama', 't.TglPenutupan')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->where('t.Close', '=', 1);

            // if ($dTglAwal == null || $dTglAkhir == null || empty($dTglAwal) || empty($dTglAkhir)) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Tutup Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json(['status' => 'error']);
            // }
            $vaData->whereBetween('t.TglPenutupan', [$dTglAwal, $dTglAkhir]);
            $vaData->orderByDesc('t.TglPenutupan');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->get();
            $vaResult = [
                'data' => $vaData,
                'total_data' => count($vaData)
            ];
            // JIKA REQUEST SUKSES
            if ($vaData) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Tutup Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
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

            Func::writeLog('Tutup Rekening Simpanan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function getRekening(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['page']);
        unset($vaRequestData['auth']);
        $nReqCount = count($vaRequestData);
        if ($nReqCount > 1 || $nReqCount < 1 || $vaRequestData['Rekening'] == null || empty($vaRequestData['Rekening'])) {
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID"
            ];
            Func::writeLog('Tutup Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return $vaRetVal;
            return response()->json(['status' => 'error']);
        }
        $dTglTransaksi = GetterSetter::getTglTransaksi();
        $dTglAwal = Carbon::parse($dTglTransaksi)->startOfMonth();
        $cRekening = $vaRequestData['Rekening'];
        // Validasi No. Rekening
        $vaData = DB::table('tabungan as t')
            ->select(
                't.Rekening',
                't.Close',
                'g.AdministrasiTutup',
                't.GolonganTabungan',
                'g.Keterangan',
                'g.PenjualanBukuTabungan',
                'r.Nama',
                'r.Alamat'
            )
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
            ->where('t.Rekening', $cRekening)
            ->first();
        if ($vaData) {
            $vaBunga = GetterSetter::getTabungan($cRekening, $dTglAwal, $dTglTransaksi);
            // Validasi Rekening
            if ($vaData->Close == 1) {
                return response()->json(
                    ['status' => 'error', 'message' => 'No. Rekening Sudah Ditutup!']
                );
            }
            $faktur = GetterSetter::getLastFaktur("TB", false);
            $nBunga = $vaBunga['Bunga'];
            // $nBunga = 0;
            $nSaldoAkhir = PerhitunganTabungan::getSaldoTabungan($cRekening, $dTglTransaksi);
            $nAdministrasiTutup = $vaData->AdministrasiTutup;
            $nAdministrasi = min($nAdministrasiTutup, $nSaldoAkhir);
            $cGolonganTabungan = $vaData->GolonganTabungan;
            $cKetGolTabungan = $vaData->Keterangan;
            $nPenjualanBukuTabungan = $vaData->PenjualanBukuTabungan;
            $nPajak = 0;
            $nPenarikanTunai = ($nSaldoAkhir + $nBunga) - $nPajak - $nAdministrasi - $nPenjualanBukuTabungan;
            if (($nSaldoAkhir + $nBunga) >= ($nAdministrasi + $nPajak + $nPenjualanBukuTabungan)) {
                $vaResult = [
                    'Nama' => $vaData->Nama,
                    'Alamat' => $vaData->Alamat,
                    'Golongan' => $cGolonganTabungan,
                    'KetGolongan' => $cKetGolTabungan,
                    'SaldoTabungan' => $nSaldoAkhir,
                    'BungaTabungan' => $nBunga,
                    'PajakTabungan' => $nPajak,
                    'Administrasi' => $nAdministrasi,
                    'PenjualanBukuTabungan' => $nPenjualanBukuTabungan,
                    'PenarikanTunai' => $nPenarikanTunai,
                    'NoTransaksi' => $faktur,
                    'Next' => 1
                ];
            } else {
                $vaResult = [
                    'Nama' => $vaData->Nama,
                    'Alamat' => $vaData->Alamat,
                    'Golongan' => $cGolonganTabungan,
                    'KetGolongan' => $cKetGolTabungan,
                    'SaldoTabungan' => $nSaldoAkhir,
                    'BungaTabungan' => $nBunga,
                    'PajakTabungan' => $nBunga,
                    'Administrasi' => $nAdministrasi,
                    'PenjualanBukuTabungan' => $nPenjualanBukuTabungan,
                    'PenarikanTunai' => '0.00'
                ];

                $vaRetVal = [
                    "status" => "03",
                    "message" => "REK. SIMPANAN TIDAK DAPAT DITUTUP"
                ];
                Func::writeLog('Tutup Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
                // return response()->json($vaRetVal);
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Simpanan Tidak Dapat Ditutup!']
                );
            } // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Tutup Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
        } else {
            $vaRetVal = [
                "status" => "03",
                "message" => "DATA TIDAK DITEMUKAN"
            ];
            Func::writeLog('Tutup Rekening Simpanan', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(
                ['status' => 'error', 'message' => 'No. Rekening Tabungan Tidak Terdaftar!']
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 10 || $nReqCount < 10) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Tutup Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $cFaktur = $vaRequestData['NoTransaksi'];
            $dTgl = GetterSetter::getTglTransaksi();
            $cRekening = $vaRequestData['Rekening'];
            $cNama = $vaRequestData['Nama'];
            // Bunga Tabungan
            Upd::updMutasiTabungan(
                $cFaktur,
                $dTgl,
                $cRekening,
                GetterSetter::getDBConfig('msKodeBukuTabungan'),
                'Bunga Tabungan ' . substr($dTgl, 3, 2) . ' ' . substr($dTgl, 6, 4) . ' ' . '[ ' . $cRekening . ' ]' . ' ' . $cNama,
                $request->BungaTabungan
            );
            // Pajak Tabungan
            Upd::updMutasiTabungan(
                $cFaktur,
                $dTgl,
                $cRekening,
                GetterSetter::getDBConfig('msKodePajakBungaTabungan'),
                'Pajak Tabungan ' . substr($dTgl, 3, 2) . ' ' . substr($dTgl, 6, 4) . ' ' . '[ ' . $cRekening . ' ]' . ' ' . $cNama,
                $request->PajakTabungan
            );
            // Administrasi Tutup Tabungan
            Upd::updMutasiTabungan(
                $cFaktur,
                $dTgl,
                $cRekening,
                GetterSetter::getDBConfig('msKodeAdministrasiTutupTabungan'),
                'Adm. Tutup Rekening Tabungan ' . substr($dTgl, 3, 2) . ' ' . substr($dTgl, 6, 4) . ' ' . '[ ' . $cRekening . ' ]' . ' ' . $cNama,
                $request->Administrasi
            );
            // Penjualan Buku Tabungan
            Upd::updMutasiTabungan(
                $cFaktur,
                $dTgl,
                $cRekening,
                GetterSetter::getDBConfig('msKodePenjualanBukuTabungan'),
                'Penjualan Buku Tabungan ' . substr($dTgl, 3, 2) . ' ' . substr($dTgl, 6, 4) . ' ' . '[ ' . $cRekening . ' ]' . ' ' . $cNama,
                $request->PenjualanBukuTabungan
            );
            // Penarikan Tunai
            Upd::updMutasiTabungan(
                $cFaktur,
                $dTgl,
                $cRekening,
                GetterSetter::getDBConfig('msKodePenarikanTunai'),
                'Tutup Rekening Tabungan ' . substr($dTgl, 3, 2) . ' ' . substr($dTgl, 6, 4) . ' ' . '[ ' . $cRekening . ' ]' . ' ' . $cNama,
                $request->PenarikanTunai
            );

            $vaArray = [
                'Close' => '1',
                'TglPenutupan' => $dTgl
            ];
            GetterSetter::setLastFaktur('TB');
            Tabungan::where('Rekening', '=', $cRekening)->update($vaArray);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Tutup Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
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
            Func::writeLog('Tutup Rekening Simpanan', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
