<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KonfigurasiSimpananController extends Controller
{
    public function getDataTabungan()
    {
        $cSetoranTunai = GetterSetter::getDBConfig("msKodeSetoranTunai");
        $cKetSetoranTunai = GetterSetter::getKeterangan($cSetoranTunai, 'Keterangan', 'kodetransaksi');
        $cPenarikanTunai = GetterSetter::getDBConfig('msKodePenarikanTunai');
        $cKetPenarikanTunai = GetterSetter::getKeterangan($cPenarikanTunai, 'Keterangan', 'kodetransaksi');
        $cBungaTabungan = GetterSetter::getDBConfig("msKodeTabungan");
        $cKetBungaTabungan = GetterSetter::getKeterangan($cBungaTabungan, 'Keterangan', 'kodetransaksi');
        $cPajakBungaTabungan = GetterSetter::getDBConfig("msKodePajakBungaTabungan");
        $cKetPajakBungaTabungan = GetterSetter::getKeterangan($cPajakBungaTabungan, 'Keterangan', 'kodetransaksi');
        $cAdmBulanan = GetterSetter::getDBConfig("msKodeAdmBulanan");
        $cKetAdmBulanan = GetterSetter::getKeterangan($cAdmBulanan, 'Keterangan', 'kodetransaksi');
        $cAdmTahunan = GetterSetter::getDBConfig("msKodeAdmTahunan");
        $cKetAdmTahunan = GetterSetter::getKeterangan($cAdmTahunan, 'Keterangan', 'kodetransaksi');
        $cAdmPasif = GetterSetter::getDBConfig("msKodeAdmPasif");
        $cKetAdmPasif = GetterSetter::getKeterangan($cAdmPasif, 'Keterangan', 'kodetransaksi');
        $cAdmTutupTabungan = GetterSetter::getDBConfig("msKodeAdministrasiTutupTabungan");
        $cKetAdmTutupTabungan = GetterSetter::getKeterangan($cAdmTutupTabungan, 'Keterangan', 'kodetransaksi');
        $cPenjualanBukuTabungan = GetterSetter::getDBConfig("msKodePenjualanBukuTabungan");
        $cKetPenjualanBukuTabungan = GetterSetter::getKeterangan($cPenjualanBukuTabungan, 'Keterangan', 'kodetransaksi');

        $vaArray = [
            "setoranTunai" => $cSetoranTunai,
            "ketSetoranTunai" => $cKetSetoranTunai,
            "penarikanTunai" => $cPenarikanTunai,
            "ketPenarikanTunai" => $cKetPenarikanTunai,
            "bungaSimpanan" => $cBungaTabungan,
            "ketBungaSimpanan" => $cKetBungaTabungan,
            "pajakBungaSimpanan" => $cPajakBungaTabungan,
            "ketPajakBungaSimpanan" => $cKetPajakBungaTabungan,
            "admBulanan" => $cAdmBulanan,
            "ketAdmBulanan" => $cKetAdmBulanan,
            "admTahunan" => $cAdmTahunan,
            "ketAdmTahunan" => $cKetAdmTahunan,
            "admPasif" => $cAdmPasif,
            "ketAdmPasif" => $cKetAdmPasif,
            "admTutupSimpanan" => $cAdmTutupTabungan,
            "ketAdmTutupSimpanan" => $cKetAdmTutupTabungan,
            "penjualanBukuSimpanan" => $cPenjualanBukuTabungan,
            "ketPenjualanBukuSimpanan" => $cKetPenjualanBukuTabungan,
        ];
        return response()->json($vaArray);
    }

    public function getDataDeposito()
    {
        $cBungaDeposito = GetterSetter::getDBConfig('msKodeBungaDeposito');
        $cKetBungaDeposito = GetterSetter::getKeterangan($cBungaDeposito, 'Keterangan', 'kodetransaksi');
        $cPajakDeposito = GetterSetter::getDBConfig('msKodePajakDeposito');
        $cKetPajakDeposito = GetterSetter::getKeterangan($cPajakDeposito, 'Keterangan', 'kodetransaksi');

        $vaArray = [
            'bungaSimpananBerjangka' => $cBungaDeposito,
            'ketBungaSimpananBerjangka' => $cKetBungaDeposito,
            'pajakSimpananBerjangka' => $cPajakDeposito,
            'ketPajakSimpananBerjangka' => $cKetPajakDeposito
        ];
        return response()->json($vaArray);
    }

    public function getDataKredit()
    {
        $cAngsuranKolektif = GetterSetter::getDBConfig("msKodeAngsuranKolektif");
        $cKetAngsuranKolektif = GetterSetter::getKeterangan($cAngsuranKolektif, "Keterangan", 'kodetransaksi');
        $cAngsuranPokok = GetterSetter::getDBConfig("msKodeAngsuranPokok");
        $cKetAngsuranPokok = GetterSetter::getKeterangan($cAngsuranPokok, "Keterangan", 'kodetransaksi');
        $cAngsuranBunga = GetterSetter::getDBConfig("msKodeAngsuranBunga");
        $cKetAngsuranBunga = GetterSetter::getKeterangan($cAngsuranBunga, "Keterangan", 'kodetransaksi');
        $cPencairanKredit = GetterSetter::getDBConfig("msKodePencairanKredit");
        $cKetPencairanKredit = GetterSetter::getKeterangan($cPencairanKredit, "Keterangan", 'kodetransaksi');
        $cProvisiKredit = GetterSetter::getDBConfig("msKodeProvisiKredit");
        $cKetProvisiKredit = GetterSetter::getKeterangan($cProvisiKredit, "Keterangan", 'kodetransaksi');
        $cAdminKredit = GetterSetter::getDBConfig("msKodeAdminKredit");
        $cKetAdminKredit = GetterSetter::getKeterangan($cAdminKredit, "Keterangan", 'kodetransaksi');
        $cBiayaKredit = GetterSetter::getDBConfig("msKodeBiayaKredit");
        $cKetBiayaKredit = GetterSetter::getKeterangan($cBiayaKredit, "Keterangan", 'kodetransaksi');
        $cNotarisKredit = GetterSetter::getDBConfig("msKodeNotarisKredit");
        $cKetNotarisKredit = GetterSetter::getKeterangan($cNotarisKredit, "Keterangan", 'kodetransaksi');
        $cAsuransiKredit = GetterSetter::getDBConfig("msKodeAsuransiKredit");
        $cKetAsuransiKredit = GetterSetter::getKeterangan($cAsuransiKredit, "Keterangan", 'kodetransaksi');
        $cSetoranAsuransiKredit = GetterSetter::getDBConfig("msKodeSetoranAsuransiKredit");
        $cKetSetoranAsuransiKredit = GetterSetter::getKeterangan($cSetoranAsuransiKredit, "Keterangan", 'kodetransaksi');
        $cSetoranNotarisKredit = GetterSetter::getDBConfig("msKodeSetoranNotarisKredit");
        $cKetSetoranNotarisKredit = GetterSetter::getKeterangan($cSetoranNotarisKredit, "Keterangan", 'kodetransaksi');

        $array = [
            'angsKolektif' => $cAngsuranKolektif,
            'ketAngsKolektif' => $cKetAngsuranKolektif,
            'angsPokok' => $cAngsuranPokok,
            'ketAngsPokok' => $cKetAngsuranPokok,
            'angsBunga' => $cAngsuranBunga,
            'ketAngsBunga' => $cKetAngsuranBunga,
            'pencairanPinjaman' => $cPencairanKredit,
            'ketPencairanPinjaman' => $cKetPencairanKredit,
            'provisiPinjaman' => $cProvisiKredit,
            'ketProvisiPinjaman' => $cKetProvisiKredit,
            'admPinjaman' => $cAdminKredit,
            'ketAdmPinjaman' => $cKetAdminKredit,
            'biayaPinjaman' => $cBiayaKredit,
            'ketBiayaPinjaman' => $cKetBiayaKredit,
            'notarisPinjaman' => $cNotarisKredit,
            'ketNotarisPinjaman' => $cKetNotarisKredit,
            'asuransiPinjaman' => $cAsuransiKredit,
            'ketAsuransiPinjaman' => $cKetAsuransiKredit,
            'setoranAsuransi' => $cSetoranAsuransiKredit,
            'ketSetoranAsuransi' => $cKetSetoranAsuransiKredit,
            'setoranNotaris' => $cSetoranNotarisKredit,
            'ketSetoranNotaris' => $cKetSetoranNotarisKredit,
        ];
        return response()->json($array);
    }

    public function getDataLainnya()
    {
        $cSetoranPB = GetterSetter::getDBConfig("msKodeSetoranPB");
        $cKetSetoranPB = GetterSetter::getKeterangan($cSetoranPB, 'Keterangan', 'kodetransaksi');
        $cPenarikanPB = GetterSetter::getDBConfig("msKodePenarikanPB");
        $cKetPenarikanPB = GetterSetter::getKeterangan($cPenarikanPB, 'Keterangan', 'kodetransaksi');

        $array = [
            'setoranPB' => $cSetoranPB,
            'ketSetoranPB' => $cKetSetoranPB,
            'penarikanPB' => $cPenarikanPB,
            'ketPenarikanPB' => $cKetPenarikanPB
        ];
        return response()->json($array);
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $user =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            // Save Data Tabungan
            GetterSetter::setDBConfig("msKodeSetoranTunai", $vaRequestData['setoranTunai']);
            GetterSetter::setDBConfig("msKodePenarikanTunai", $vaRequestData['penarikanTunai']);
            GetterSetter::setDBConfig("msKodeBungaTabungan", $vaRequestData['bungaSimpanan']);
            GetterSetter::setDBConfig("msKodePajakBungaTabungan", $vaRequestData['pajakBungaSimpanan']);
            GetterSetter::setDBConfig("msKodeAdmBulanan", $vaRequestData['admBulanan']);
            GetterSetter::setDBConfig("msKodeAdmTahunan", $vaRequestData['admTahunan']);
            GetterSetter::setDBConfig("msKodeAdmPasif", $vaRequestData['admPasif']);
            GetterSetter::setDBConfig("msKodeAdministrasiTutupTabungan", $vaRequestData['admTutupSimpanan']);
            GetterSetter::setDBConfig("msKodePenjualanBukuTabungan", $vaRequestData['penjualanBukuSimpanan']);

            // Save Data Deposito
            GetterSetter::setDBConfig("msKodeBungaDeposito", $vaRequestData['bungaSimpananBerjangka']);
            GetterSetter::setDBConfig("msKodePajakDeposito", $vaRequestData['pajakSimpananBerjangka']);

            // Save Data Kredit
            GetterSetter::setDBConfig("msKodeAngsuranKolektif", $vaRequestData['angsKolektif']);
            GetterSetter::setDBConfig("msKodeAngsuranPokok", $vaRequestData['angsPokok']);
            GetterSetter::setDBConfig("msKodeAngsuranBunga", $vaRequestData['angsBunga']);
            GetterSetter::setDBConfig("msKodePencairanKredit", $vaRequestData['pencairanPinjaman']);
            GetterSetter::setDBConfig("msKodeProvisiKredit", $vaRequestData['provisiPinjaman']);
            GetterSetter::setDBConfig("msKodeAdminKredit", $vaRequestData['admPinjaman']);
            GetterSetter::setDBConfig("msKodeBiayaKredit", $vaRequestData['biayaPinjaman']);
            GetterSetter::setDBConfig("msKodeNotarisKredit", $vaRequestData['notarisPinjaman']);
            GetterSetter::setDBConfig("msKodeAsuransiKredit", $vaRequestData['asuransiPinjaman']);
            GetterSetter::setDBConfig("msKodeSetoranAsuransiKredit", $vaRequestData['setoranAsuransi']);
            GetterSetter::setDBConfig("msKodeSetoranNotarisKredit", $vaRequestData['setoranNotaris']);

            // Save Data Lainnya
            GetterSetter::setDBConfig("msKodeSetoranPB", $vaRequestData['setoranPB']);
            GetterSetter::setDBConfig("msKodePenarikanPB", $vaRequestData['penarikanPB']);

            // JIKA REQUEST SUKSES
            $retVal = ["status" => "00", "message" => "SUKSES"];
            Func::writeLog('Konfigurasi Simpanan', 'store', $vaRequestData, $retVal, $user);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $retVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                    // tambahkan informasi lainnya yang ingin Anda sertakan
                ]
            ];

            Func::writeLog('Konfigurasi Simpanan', 'store', $vaRequestData, $th, $user);
            // return response()->json($retVal);
            return response()->json(['status' => 'error']);
        }
    }
}
