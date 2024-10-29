<?php

namespace App\Http\Controllers\api\konversi;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\simpananberjangka\Deposito;
use App\Models\teller\MutasiAnggota;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KonversiMutasiController extends Controller
{
    public function getKodeAnggotaBaru(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            $registerColumn = 'A';
            $excelFile = $request->file('excel_file');
            $filename = $excelFile->getClientOriginalName();
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $worksheetMutAng = $spreadsheet->getSheetByName('MUTASI ANGGOTA');
                if ($worksheetMutAng) {
                    $highestRow = $worksheetMutAng->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $kodeLama = $worksheetMutAng->getCell($registerColumn . $row)->getValue();
                        $data = DB::table('registernasabah')
                            ->select('Kode', 'Nama')
                            ->where('KodeLama', '=', $kodeLama)
                            ->get();
                        foreach ($data as $d) {
                            $kode = $d->Kode;
                            $nama = $d->Nama;
                        }
                        $result[] = [
                            'Kode' => $kode,
                            'Nama' => $nama
                        ];
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Sheet not found']);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Excel file not provided']);
            }
            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function getRekTabunganBaru(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            $rekeningColumn = 'A';
            $excelFile = $request->file('excel_file');
            $filename = $excelFile->getClientOriginalName();
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $worksheetMutTab = $spreadsheet->getSheetByName('MUTASI SIMPANAN');
                if ($worksheetMutTab) {
                    $highestRow = $worksheetMutTab->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $rekTabLama = $worksheetMutTab->getCell($rekeningColumn . $row)->getValue();
                        $data = DB::table('tabungan as t')
                            ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                            ->select('t.Rekening', 't.Kode', 'r.Nama')
                            ->where('t.RekeningLama', '=', $rekTabLama)
                            ->get();
                        foreach ($data as $d) {
                            $rekTab = $d->Rekening;
                            $kodeCifBaru = $d->Kode;
                            $nama = $d->Nama;
                        }
                        $result[] = [
                            'RekTab' => $rekTab,
                            'Kode' => $kodeCifBaru,
                            'Nama' => $nama
                        ];
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Sheet not found']);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Excel file not provided']);
            }
            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function getRekDepositoBaru(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            $rekeningColumn = 'A';
            $excelFile = $request->file('excel_file');
            $filename = $excelFile->getClientOriginalName();
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $worksheetMutDepo = $spreadsheet->getSheetByName('MUTASI SIMPANAN BERJANGKA');
                if ($worksheetMutDepo) {
                    $highestRow = $worksheetMutDepo->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $rekDepoLama = $worksheetMutDepo->getCell($rekeningColumn . $row)->getValue();
                        $data = DB::table('deposito as d')
                            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                            ->select('d.Rekening', 'd.Kode', 'r.Nama')
                            ->where('d.RekeningLama', '=', $rekDepoLama)
                            ->get();
                        foreach ($data as $d) {
                            $rekDepo = $d->Rekening;
                            $kodeCifBaru = $d->Kode;
                            $nama = $d->Nama;
                        }
                        $result[] = [
                            'RekDepo' => $rekDepo,
                            'Kode' => $kodeCifBaru,
                            'Nama' => $nama
                        ];
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Sheet not found']);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Excel file not provided']);
            }
            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function getRekKreditBaru(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            $rekeningColumn = 'A';
            $excelFile = $request->file('excel_file');
            $filename = $excelFile->getClientOriginalName();
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $worksheetMutKredit = $spreadsheet->getSheetByName('MUTASI PINJAMAN');
                if ($worksheetMutKredit) {
                    $highestRow = $worksheetMutKredit->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $rekKreditLama = $worksheetMutKredit->getCell($rekeningColumn . $row)->getValue();
                        $data = DB::table('debitur as d')
                            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                            ->select('d.Rekening', 'd.Kode', 'r.Nama')
                            ->where('d.RekeningLama', '=', $rekKreditLama)
                            ->get();
                        foreach ($data as $d) {
                            $rekKredit = $d->Rekening;
                            $kodeCifBaru = $d->Kode;
                            $nama = $d->Nama;
                        }
                        $result[] = [
                            'RekKredit' => $rekKredit,
                            'Kode' => $kodeCifBaru,
                            'Nama' => $nama
                        ];
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Sheet not found']);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Excel file not provided']);
            }
            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeMutasiAnggota(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $dTglCutOff = $vaRequestData->tglCutOff;
        DB::beginTransaction();
        try {
            MutasiAnggota::where('Tgl', '<', $dTglCutOff)->delete();
            $mutasiAnggotaArray = $vaRequestData->input('data', []);
            if (!empty($mutasiAnggotaArray)) {
                foreach ($mutasiAnggotaArray as $data) {
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $dk = '';
                    if (
                        $data['DEBETPOKOK'] > 0 ||
                        $data['DEBETWAJIB'] > 0
                    ) {
                        $dk = 'D';
                    }
                    if (
                        $data['KREDITPOKOK'] > 0 ||
                        $data['KREDITWAJIB'] > 0
                    ) {
                        $dk = 'K';
                    }
                    $faktur = GetterSetter::getLastFaktur('MA', 7);
                    $nama = GetterSetter::getKeterangan($data['NOANGGOTA'], 'Nama', 'registernasabah');
                    $mutAngData = [
                        'Faktur' => $faktur,
                        'Kode' => $data['NOANGGOTA'],
                        'Tgl' => $tgl,
                        'DK' => $dk,
                        'Keterangan' => 'Mutasi Anggota [ ' . $data['NOANGGOTA'] . ' ] ' . $nama,
                        'DebetPokok' => $data['DEBETPOKOK'],
                        'KreditPokok' => $data['KREDITPOKOK'],
                        'DebetWajib' => $data['DEBETWAJIB'],
                        'KreditWajib' => $data['KREDITWAJIB'],
                        'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                        'UserName' => $cUser, //GET CONFIG
                        'DateTime' => Carbon::now()
                    ];
                    MutasiAnggota::create($mutAngData);
                    GetterSetter::setLastFaktur('MA');
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeMutasiSimpanan(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $dTglCutOff = $vaRequestData->tglCutOff;
        DB::beginTransaction();
        try {
            MutasiTabungan::where('Tgl', '<', $dTglCutOff)->delete();
            $mutasiTabunganArray = $vaRequestData->input('data', []);
            if (!empty($mutasiTabunganArray)) {
                foreach ($mutasiTabunganArray as $data) {
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $faktur = GetterSetter::getLastFaktur('TB', 7);
                    if (
                        $data['DEBET'] > 0
                    ) {
                        $dk = 'D';
                    }
                    if (
                        $data['KREDIT'] > 0
                    ) {
                        $dk = 'K';
                    }
                    $mutTabData = [
                        'Faktur' => $faktur,
                        'Rekening' => $data['REKSIMPANAN'],
                        'Tgl' => $tgl,
                        'Keterangan' => $data['KETERANGAN'],
                        'DK' => $dk,
                        'KodeTransaksi' => $data["KODETRANSAKSI"],
                        'Jumlah' => $data['JUMLAH'],
                        'Debet' => $data['DEBET'],
                        'Kredit' => $data['KREDIT'],
                        'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                        'UserName' => $cUser, //GET CONFIG
                        'DateTime' => Carbon::now()
                    ];
                    MutasiTabungan::create($mutTabData);
                    GetterSetter::setLastFaktur('TB');
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeMutasiSimpananBerjangka(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $dTglCutOff = $vaRequestData->tglCutOff;
        DB::beginTransaction();
        try {
            MutasiDeposito::where('Tgl', '<', $dTglCutOff)->delete();
            $mutasiDepoArray = $request->input('data', []);
            if (!empty($mutasiDepoArray)) {
                foreach ($mutasiDepoArray as $data) {
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $faktur = GetterSetter::getLastFaktur('DP', 7);
                    $mutDepoData = [
                        'Faktur' => $faktur,
                        'Rekening' => $data['REKDEPOSITO'],
                        'Tgl' =>  $tgl,
                        'SetoranPlafond' => $data['SETORANPLAFOND'],
                        'PencairanPlafond' => $data['PENCAIRANPLAFOND'],
                        'Bunga' => $data['BUNGA'],
                        'Pajak' => $data['PAJAK'],
                        'KoreksiBunga' => $data['KOREKSIBUNGA'],
                        'KoreksiPajak' => $data['KOREKSIPAJAK'],
                        'Pinalty' => $data['PINALTY'],
                        'DTitipan' => $data['DTITIPAN'],
                        'KTitipan' => $data['KTITIPAN'],
                        'Kas' => $data['KAS'],
                        'CabangEntry' => GetterSetter::getDBConfig('msKodeCabang'),
                        'UserName' => $cUser, //GET CONFIG
                        'DateTime' => Carbon::now()
                    ];
                    MutasiDeposito::create($mutDepoData);

                    if ($data['PENCAIRANPLAFOND'] > 0) {
                        Deposito::where('Rekening', $data['REKDEPOSITO'])
                            ->update([
                                'Status' => '1'
                            ]);
                    }
                    GetterSetter::setLastFaktur('DP');
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeMutasiPinjaman(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        $dTglCutOff = $vaRequestData->tglCutOff;
        DB::beginTransaction();
        try {
            ini_set('max_execution_time', 0);
            Angsuran::where('Tgl', '<', $request->tglCutOff)->where('Status', '=', '5')->delete();
            $angsuranArray = $request->input('data', []);
            if (!empty($angsuranArray)) {
                foreach ($angsuranArray as $data) {
                    $nama = "";
                    $query = DB::table('debitur as d')
                        ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                        ->select('r.Nama', 'd.Rekening as rekPinjaman')
                        ->where('d.RekeningLama', '=', $data['REKENING'])
                        ->first();
                    if ($query) {
                        $nama = $query->Nama;
                    }
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    for ($i = 1; $i <= $data['POKOKKE']; $i++) {
                        $fakturAngsuran = GetterSetter::getLastFaktur("AG", 7);
                        $pokokData = [
                            "Status" => "5",
                            "Faktur" => $fakturAngsuran,
                            "Tgl" => $tgl,
                            "Rekening" => $data['REKPINJAMAN'],
                            "Keterangan" => "Angsuran Pokok [ " . $data['REKPINJAMAN'] . ' ] a.n ' . $nama,
                            "KPokok" => $data['KPOKOK'],
                            "Kas" => "K",
                            "UserName" => $cUser, // GET CONFIG
                            "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                            "DateTime" => Carbon::now()
                        ];
                        Angsuran::create($pokokData);
                        GetterSetter::setLastFaktur('AG');
                    }

                    for ($i = 1; $i <= $data['BUNGAKE']; $i++) {
                        $fakturAngsuran = GetterSetter::getLastFaktur("AG", 7);
                        $bungaData = [
                            "Status" => "5",
                            "Faktur" => $fakturAngsuran,
                            "Tgl" => $tgl,
                            "Rekening" => $data['REKPINJAMAN'],
                            "Keterangan" => "Angsuran Bunga [ " .  $data['REKPINJAMAN'] . ' ] a.n ' . $nama,
                            "KBunga" => $data['KBUNGA'],
                            "Kas" => "K",
                            "UserName" => $cUser, // GET CONFIG
                            "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                            "DateTime" => Carbon::now()
                        ];
                        Angsuran::create($bungaData);
                        GetterSetter::setLastFaktur('AG');
                    }
                    if ($data['DENDA'] > 0) {
                        $fakturDenda = GetterSetter::getLastFaktur("AG", 7);
                        $denda = [
                            "Status" => "5",
                            "Faktur" => $fakturDenda,
                            "Tgl" => $tgl,
                            "Rekening" => $data['REKPINJAMAN'],
                            "Keterangan" => "Denda [ " . $data['REKPINJAMAN'] . ' ] a.n ' . $nama,
                            "Denda" => $data["DENDA"],
                            "Kas" => "K",
                            "UserName" => $cUser, //GET CONFIG
                            "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                            "DateTime" => Carbon::now()
                        ];
                        Angsuran::create($denda);
                        GetterSetter::setLastFaktur('AG');
                    }
                    if ($data['ADMINISTRASI'] > 0) {
                        $fakturAdministrasi = GetterSetter::getLastFaktur("AG", 7);
                        $administrasi = [
                            "Status" => "5",
                            "Faktur" => $fakturAdministrasi,
                            "Tgl" => $tgl,
                            "Rekening" => $data['REKPINJAMAN'],
                            "Keterangan" => "Administrasi [ " . $data['REKPINJAMAN'] . ' ] a.n ' . $nama,
                            "Administrasi" => $data["ADMINISTRASI"],
                            "Kas" => "K",
                            "UserName" => $cUser, //GET CONFIG
                            "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                            "DateTime" => Carbon::now()
                        ];
                        Angsuran::create($administrasi);
                        GetterSetter::setLastFaktur('AG');
                    }
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }
}
