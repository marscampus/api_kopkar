<?php

namespace App\Http\Controllers\api\konversi;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateKodeJob;
use App\Models\fun\Angsuran;
use App\Models\master\RegisterNasabah;
use App\Models\pinjaman\Agunan;
use App\Models\pinjaman\Debitur;
use App\Models\simpanan\Tabungan;
use App\Models\simpananberjangka\Deposito;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Async\Pool;
use Symfony\Component\Process\Process;

class KonversiDataController extends Controller
{
    public function getGenerateKode(Request $request)
    {

        try {
            ini_set('max_execution_time', 0);
            $result = [];
            $excelFile = $request->file('excel_file');
            $filename = $excelFile->getClientOriginalName();

            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $worksheetRegNas = $spreadsheet->getSheetByName('REGISTER NASABAH');
                if ($worksheetRegNas) {
                    $highestRow = $worksheetRegNas->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $noAnggota = GetterSetter::getRekening('0', 7);
                        $result[] = ['Anggota' => $noAnggota];
                        GetterSetter::setRekening('0');
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Sheet not found']);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Excel file not provided']);
            }
            return response()->json($result);
            // return response()->json($filename);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function getGenerateRekTab(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            // Ambil file Excel dari request (pastikan file sudah diunggah ke server)
            $excelFile = $request->file('excel_file');
            // Dapatkan nama file
            $filename = $excelFile->getClientOriginalName();
            $kode = '';
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $registerColumn = 'A';
                $worksheetSimpanan = $spreadsheet->getSheetByName('SIMPANAN');
                if ($worksheetSimpanan) {
                    $highestRow = $worksheetSimpanan->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $registerValue = $worksheetSimpanan->getCell($registerColumn . $row)->getValue();
                        $data = DB::table('registernasabah as r')
                            ->select('r.Kode')
                            ->where('r.KodeLama', '=', $registerValue)
                            ->get();
                        foreach ($data as $d) {
                            $kode = $d->Kode;
                        }
                        $rekTabungan = GetterSetter::getRekening('1', 7);
                        $result[] = ['Tabungan' => $rekTabungan, 'KodeAnggotaBaru' => $kode];
                        GetterSetter::setRekening('1');
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

    public function getGenerateRekDepo(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            // Ambil file Excel dari request (pastikan file sudah diunggah ke server)
            $excelFile = $request->file('excel_file');
            // Dapatkan nama file
            $filename = $excelFile->getClientOriginalName();
            $kode = '';
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $registerColumn = 'A';
                $tglColumn = 'F';
                $lamaColumn = 'G';
                $worksheetSimpananBerjangka = $spreadsheet->getSheetByName('SIMPANAN BERJANGKA');
                if ($worksheetSimpananBerjangka) {
                    $highestRow = $worksheetSimpananBerjangka->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $registerValue = $worksheetSimpananBerjangka->getCell($registerColumn . $row)->getValue();
                        $tglValue = $worksheetSimpananBerjangka->getCell($tglColumn . $row)->getValue();
                        $lamaValue = $worksheetSimpananBerjangka->getCell($lamaColumn . $row)->getValue();
                        $data = DB::table('registernasabah as r')
                            ->select('r.Kode')
                            ->where('r.KodeLama', '=', $registerValue)
                            ->get();
                        foreach ($data as $d) {
                            $kode = $d->Kode;
                        }
                        $tglCarbon = Carbon::createFromFormat('d/m/Y', $tglValue);
                        $lamaInt = intval($lamaValue);
                        $jthTmp = $tglCarbon->addMonth($lamaInt)->format('d/m/Y');
                        $rekDeposito = GetterSetter::getRekening('2', 7);
                        $result[] = [
                            'Deposito' => $rekDeposito,
                            'KodeAnggotaBaru' => $kode,
                            'JthTmp' => $jthTmp
                        ];
                        GetterSetter::setRekening('2');
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

    public function getGenerateRekKredit(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $result = [];
            // Ambil file Excel dari request (pastikan file sudah diunggah ke server)
            $excelFile = $request->file('excel_file');
            // Dapatkan nama file
            $filename = $excelFile->getClientOriginalName();
            $kode = '';
            if ($excelFile) {
                $spreadsheet = IOFactory::load($excelFile->getPathname());
                $registerColumn = 'A';
                $tglColumn = 'F';
                $lamaColumn = 'G';
                $worksheetPinjaman = $spreadsheet->getSheetByName('PINJAMAN');
                if ($worksheetPinjaman) {
                    $highestRow = $worksheetPinjaman->getHighestRow();
                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $registerValue = $worksheetPinjaman->getCell($registerColumn . $row)->getValue();
                        $tglValue = $worksheetPinjaman->getCell($tglColumn . $row)->getValue();
                        $lamaValue = $worksheetPinjaman->getCell($lamaColumn . $row)->getValue();
                        $data = DB::table('registernasabah as r')
                            ->select('r.Kode')
                            ->where('r.KodeLama', '=', $registerValue)
                            ->get();
                        foreach ($data as $d) {
                            $kode = $d->Kode;
                        }
                        $tglCarbon = Carbon::createFromFormat('d/m/Y', $tglValue);
                        $lamaInt = intval($lamaValue);
                        $jthTmp = $tglCarbon->addMonth($lamaInt)->format('d/m/Y');
                        $rekTabungan = GetterSetter::getRekening('3', 7);
                        $result[] = [
                            'Debitur' => $rekTabungan,
                            'KodeAnggotaBaru' => $kode,
                            'JthTmp' => $jthTmp
                        ];
                        GetterSetter::setRekening('3');
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

    public function storeRegNas(Request $request)
    {
        ini_set('max_execution_time', 0);
        DB::beginTransaction();
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff = $vaRequestData->tglCutOff;
            // Menghapus data yang memiliki tanggal kurang dari tglCutOff
            RegisterNasabah::where('Tgl', '<', $dTglCutOff)->chunk(200, function ($regnas) {
                foreach ($regnas as $record) {
                    $record->delete();
                }
            });
            $regNasArray = $vaRequestData->input('data', []);

            if (!empty($regNasArray)) {
                foreach ($regNasArray as $data) {
                    // Konversi format tanggal ke objek Carbon
                    $tglMasuk = Carbon::createFromFormat('d/m/Y', $data['TGL_MASUK']);
                    $tglLahir = Carbon::createFromFormat('d/m/Y', $data['TGL_LAHIR']);

                    $registerNasabahData = [
                        "Kode" => $data['NOANGGOTA'],
                        "KodeLama" => $data['REGISTER'],
                        "Nama" => $data['NAMA'],
                        "Alamat" => $data['ALAMAT'],
                        "Tgl" => $tglMasuk,
                        "TempatLahir" => $data['TEMPAT_LAHIR'],
                        "TglLahir" => $tglLahir,
                        "KTP" => $data['KTP'],
                        "Telepon" => $data['NO_HP'],
                        "UserName" => $cUser,
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang')
                    ];

                    RegisterNasabah::create($registerNasabahData);
                }
                DB::commit();
                // Kembalikan array dengan nilai tglMasuk
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeSimpanan(Request $request)
    {
        ini_set('max_execution_time', 0);
        DB::beginTransaction();
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff = $vaRequestData->TglCutOff;
            Tabungan::where('Tgl', '<', $dTglCutOff)->chunk(200, function ($tabungan) {
                foreach ($tabungan as $record) {
                    $record->delete();
                }
            });
            $simpananArray = $vaRequestData->input('data', []);
            if (!empty($simpananArray)) {
                foreach ($simpananArray as $data) {
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $simpananData = [
                        "Rekening" => $data['REKSIMPANAN'],
                        "RekeningLama" => $data['REKENING'],
                        "Kode" => $data['KODEANGBARU'],
                        "NamaNasabah" => $data['NAMA'],
                        "GolonganTabungan" => $data['GOLONGAN'],
                        "Tgl" => $tgl,
                        "SaldoAkhir" => $data['SALDOAKHIR'],
                        "AO" => $data['AO'],
                        "UserName" => $cUser,
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang')
                    ];
                    Tabungan::create($simpananData);
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storeSimpananBerjangka(Request $request)
    {
        ini_set('max_execution_time', 0);
        DB::beginTransaction();
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff = $vaRequestData->tglCutOff;
            Deposito::where('Tgl', '<', $dTglCutOff)->chunk(200, function ($depo) {
                foreach ($depo as $record) {
                    $record->delete();
                }
            });
            $simpananBerjangkaArray = $vaRequestData->input('data', []);
            if (!empty($simpananBerjangkaArray)) {
                foreach ($simpananBerjangkaArray as $data) {
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $jthTmp = Carbon::createFromFormat('d/m/Y', $data['JTHTMP']);
                    $rekTabungan = '';
                    $vaData = DB::table('tabungan')
                        ->select('Rekening')
                        ->where('RekeningLama', '=', $data['REKENINGTABUNGAN'])
                        ->first();
                    if ($vaData) {
                        $rekTabungan = $vaData->Rekening;
                    }
                    $depositoData = [
                        "Rekening" => $data['REKDEPOSITO'],
                        "RekeningLama" => $data['REKENING'],
                        "Kode" => $data['KODEANGBARU'],
                        "Tgl" => $tgl,
                        "Jthtmp" => $jthTmp,
                        "GolonganDeposito" => $data['GOLONGAN'],
                        "SukuBunga" => $data['SUKUBUNGA'],
                        "RekeningTabungan" => $rekTabungan,
                        "TempNominal" => $data['NOMINAL'],
                        "AO" => $data['AO'],
                        "UserName" => $cUser,
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                        "DateTime" => Carbon::now()
                    ];
                    Deposito::create($depositoData);
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    public function storePinjaman(Request $request)
    {
        DB::beginTransaction();
        try {
            ini_set('max_execution_time', 0);
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTglCutOff = $vaRequestData->tglCutOff;
            Debitur::where('Tgl', '<', $dTglCutOff)->chunk(200, function ($debitur) {
                foreach ($debitur as $record) {
                    $record->delete();
                }
            });
            Angsuran::where('Tgl', '<', $dTglCutOff)->where('Status', '=', '2')->chunk(200, function ($angsuran) {
                foreach ($angsuran as $record) {
                    $record->delete();
                }
            });
            Agunan::where('Tgl', '<', $dTglCutOff)->chunk(200, function ($agunan) {
                foreach ($agunan as $record) {
                    $record->delete();
                }
            });
            $pinjamanArray = $vaRequestData->input('data', []);
            $rekJaminanArray = [];
            if (!empty($pinjamanArray)) {
                foreach ($pinjamanArray as $data) {
                    $fakturAwal = GetterSetter::getLastFaktur("R0", 7);
                    $kode = $data['KODEANGBARU'];
                    $frekuensi = intval(substr($kode, 11, 4));
                    $frekuensi = strval(intval($frekuensi) + 1);
                    $frekuensi = str_pad($frekuensi, 4, '0', STR_PAD_LEFT);
                    $rekJaminan = $kode . $frekuensi;
                    $tgl = Carbon::createFromFormat('d/m/Y', $data['TGL']);
                    $rekJaminanArray[] = $rekJaminan;
                    $nama = '';
                    $debiturData = [
                        "Faktur" => $fakturAwal,
                        "Rekening" => $data['REKPINJAMAN'],
                        "RekeningLama" => $data['REKENING'],
                        "RekeningJaminan" => $rekJaminan,
                        "Tgl" => $tgl,
                        "StatusPencairan" => '1',
                        "CaraPencairan" => 'K',
                        "CaraPerhitungan" => $data['CARAPERHITUNGAN'],
                        "Kode" => $kode,
                        "GolonganKredit" => $data['GOLONGAN'],
                        "SukuBunga" => $data['SUKUBUNGA'],
                        "Plafond" => $data['PLAFOND'],
                        "Lama" => $data['LAMA'],
                        "AO" => $data['AO'],
                        "Musiman" => $data['BUNGA_PER'],
                        "GracePeriod" => $data['POKOK_PER'],
                        "GracePeriodePokokAwal" => $data['GRACEPERIOD_POKOKAWAL'],
                        "GracePeriodeBungaAwal" => $data['GRACEPERIOD_BUNGAAWAL'],
                        "UserName" => $cUser, // GET CONFIG
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                        "DateTime" => Carbon::now()
                    ];
                    Debitur::create($debiturData);


                    $query = DB::table('debitur as d')
                        ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                        ->select('r.Nama')
                        ->where('d.RekeningLama', '=', $data['REKENING'])
                        ->first();
                    if ($query) {
                        $nama = $query->Nama;
                    }
                    $angsuranData = [
                        "Status" => "2",
                        "Faktur" => $fakturAwal,
                        "Tgl" => $tgl,
                        "Rekening" => $data['REKPINJAMAN'],
                        "Keterangan" => "Pencairan Pinjaman [ " . $data['REKPINJAMAN'] . ' ] a.n ' . $nama,
                        "DPokok" => $data['PLAFOND'],
                        "Kas" => "K",
                        "UserName" => $cUser, // GET CONFIG
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                        "DateTime" => Carbon::now()
                    ];
                    Angsuran::create($angsuranData);
                    GetterSetter::setLastFaktur('R0');
                    $agunanData = [
                        "Status" => "1",
                        "No" => "1",
                        "Rekening" => $rekJaminan,
                        "Kode" => $kode,
                        "Tgl" => $tgl,
                        "S_JenisPengikatan" => "6",
                        "Jaminan" => "8",
                        "NilaiJaminan" => $data['NILAIJAMINAN'],
                        "NilaiYangDiPerhitungkan" => $data['NILAIJAMINAN'],
                        "L_Note" => $data['DETAILJAMINAN'],
                        "CabangEntry" => GetterSetter::getDBConfig('msKodeCabang'),
                    ];
                    Agunan::create($agunanData);
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()]);
        }
    }
}
