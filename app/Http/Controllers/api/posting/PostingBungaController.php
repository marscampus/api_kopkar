<?php

namespace App\Http\Controllers\api\posting;

use App\Helpers\Func;
use App\Helpers\Func\Date;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\fun\MutasiTabungan;
use App\Models\jurnal\Jurnal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostingBungaController extends Controller
{
    // PROSES BUNGA TABUNGAN
    public function prosesPostingTabungan(Request $request)
    {
        try {
            ini_set('max_execution_time', '0');
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $nReqCount = count($vaRequestData);
            if ($nReqCount > 4 || $nReqCount < 4) {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "REQUEST TIDAK VALID"
                ];
                Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
                // return $vaRetVal;
                return response()->json(['status' => 'error']);
            }
            $dTglPosting = Func::Date2String($vaRequestData['TglPosting']);
            $dTglAwal = Func::Date2String($vaRequestData['TglAwal']);
            $dTglAkhir = Func::Date2String($vaRequestData['TglAkhir']);
            $cKode = $vaRequestData['Kode'];
            MutasiTabungan::where('Tgl', '=', $dTglPosting)->delete();
            if (in_array(1, $cKode)) {
                self::prosesBungaTabungan($dTglPosting, $dTglAwal, $dTglAkhir, $cUser);
            }
            if (in_array(2, $cKode)) {
                self::prosesAdministrasiTabungan($dTglPosting, $dTglAwal, $dTglAkhir, $cUser);
            }
            self::postingJurnalBungaTabungan($dTglPosting);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "success"
            ];
            Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function prosesBungaTabungan($dTglPosting, $dTglAwal, $dTglAkhir, $cUser)
    {
        try {
            $nBunga = 0;
            $nPajak = 0;
            $nPersenPajak = 20;
            $nUrut = 0;
            $vaData = DB::table('tabungan as t')
                ->select(
                    't.CabangEntry',
                    't.Rekening',
                    DB::raw('IFNULL(SUM(Kredit-Debet), 0) as Saldo'),
                    'g.Keterangan'
                )
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('mutasitabungan as m', 'm.rekening', '=', 't.rekening')
                ->where('m.Tgl', '<=', $dTglAkhir)
                ->where('g.SaldoMinimumDapatBunga', '>', 0)
                ->groupBy('t.Rekening')
                ->having('Saldo', '>', 0)
                ->get();
            foreach ($vaData as $d) {
                $cCabang = $d->CabangEntry;
                $vaTabungan = GetterSetter::getTabungan($d->Rekening, $dTglAwal, $dTglAkhir);
                if ($vaTabungan['Bunga'] > 0) {
                    $cKodeTransaksi = GetterSetter::getDBConfig("msKodeBungaTabungan");
                    $nUrut++;
                    $cKey = "BT" . $cCabang;
                    if (!isset($vaF[$cKey])) {
                        $vaF[$cKey] = 0;
                    }
                    $vaF[$cKey] += 1;
                    $nTime = Func::Tgl2Time($dTglPosting);
                    $cKey1 = $cKey . date('Ymd', $nTime);
                    $cKode = str_pad($vaF[$cKey], 20, "0", STR_PAD_LEFT);
                    $cFaktur = $cKey1 . substr($cKode, strlen($cKey1));

                    $nBunga = round($vaTabungan['Bunga'], 0);
                    Upd::updMutasiTabungan($cFaktur, $dTglPosting, $d->Rekening, $cKodeTransaksi, "Bunga Tabungan", $nBunga, $cCabang, false, $cUser);
                }

                if ($vaTabungan['Pajak'] > 0) {
                    $cFaktur = GetterSetter::getLastFaktur($cKey, true);
                    $cKodeTransaksi = GetterSetter::getDBConfig("msKodePajakBungaTabungan");
                    $nUrut++;
                    $cKey = "BT" . $cCabang;
                    if (!isset($vaF[$cKey])) {
                        $vaF[$cKey] = 0;
                    }
                    $vaF[$cKey] += 1;
                    $nTime = Func::Tgl2Time($dTglPosting);
                    $cKey1 = $cKey . date('Ymd', $nTime);
                    $cKode = str_pad($vaF[$cKey], 20, "0", STR_PAD_LEFT);
                    $cFaktur = $cKey1 . substr($cKode, strlen($cKey1));

                    $nPajak = round($vaTabungan['Pajak'], 0);
                    Upd::updMutasiTabungan($cFaktur, $dTglPosting, $d->Rekening, $cKodeTransaksi, "Pajak Bunga Tabungan", $nPajak, $cCabang, false, $cUser);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function prosesAdministrasiTabungan($dTglPosting, $dTglAwal, $dTglAkhir, $cUser)
    {
        try {
            $vaData = DB::table('tabungan as t')
                ->select(
                    't.Rekening',
                    DB::raw('IFNULL(SUM(m.Kredit-m.Debet),0) as SaldoAkhir'),
                    'r.Nama',
                    't.CabangEntry',
                    'g.AdministrasiBulanan as Biaya'
                )
                ->leftJoin('mutasitabungan as m', 'm.Rekening', '=', 't.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->where('m.Tgl', '<=', $dTglAkhir)
                ->where('AdministrasiBulanan', '>', 0)
                ->where('t.StatusAdministrasi', '=', 'Y')
                ->groupBy('t.Rekening')
                ->having('SaldoAkhir', '>', 0)
                ->orderBy('t.Rekening')
                ->get();
            $nUrut = 0;
            foreach ($vaData as $d) {
                $cRekening = $d->Rekening;
                $cNama = $d->Nama;
                $nAdministrasi = min($d->SaldoAkhir, $d->Biaya);
                $cCabang = $d->CabangEntry;
                $nUrut++;
                if ($nAdministrasi > 0) {
                    $cKey = "AT" . $cCabang;
                    if (!isset($vaF[$cKey])) {
                        $vaF[$cKey] = 0;
                    }
                    $vaF[$cKey] += 1;
                    $nTime = Func::Tgl2Time($dTglPosting);
                    $cKey1 = $cKey . date('Ymd', $nTime);
                    $cKode = str_pad($vaF[$cKey], 20, "0", STR_PAD_LEFT);
                    $cFaktur = $cKey1 . substr($cKode, strlen($cKey1));
                    $cKodeTransaksi = GetterSetter::getDBConfig('msKodeAdmBulanan');
                    $cKeterangan = Func::String2SQL("Adm Tab an. " . $cNama);
                    Upd::updMutasiTabungan($cFaktur, $dTglPosting, $cRekening, $cKodeTransaksi, $cKeterangan, $nAdministrasi, $cCabang, false, $cUser);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function postingJurnalBungaTabungan($dTgl)
    {
        try {
            Jurnal::where('Tgl', '=', $dTgl)
                ->where('Faktur', 'LIKE', 'AT%')
                ->orWhere('Faktur', 'LIKE', 'BT%')
                ->delete();

            // Bagian Pertama - Bunga Tabungan
            $cKodeTransaksiBunga = GetterSetter::getDBConfig("msKodeBungaTabungan");
            $vaDataBunga = DB::table('mutasitabungan as m')
                ->select(
                    'm.CabangEntry',
                    't.GolonganTabungan',
                    DB::raw('MAX(m.Faktur) as Faktur'),
                    'm.KodeTransaksi',
                    DB::raw('IFNULL(SUM(m.Debet), 0) as Debet'),
                    DB::raw('IFNULL(SUM(m.Kredit), 0) as Kredit'),
                    'g.Rekening',
                    'g.RekeningBunga',
                    'm.UserName',
                    'r.Keterangan as NamaBiayaBunga'
                )
                ->leftJoin('tabungan as t', 't.Rekening', '=', 'm.Rekening')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningBunga')
                ->whereRaw('LEFT(m.Faktur, 2) in ("BT", "AT")')
                ->where('m.Tgl', '=', $dTgl)
                ->where('m.KodeTransaksi', '=', $cKodeTransaksiBunga)
                ->groupBy('m.CabangEntry', 'm.KodeTransaksi', 't.GolonganTabungan')
                ->get();

            foreach ($vaDataBunga as $d) {
                $cFakturBunga = $d->Faktur;
                $cKetBunga = $d->NamaBiayaBunga;
                $nKreditBunga = $d->Kredit;
                $cCabangEntryBunga = $d->CabangEntry;

                Upd::updJurnalLainLain($dTgl, $cFakturBunga, $d->RekeningBunga, $cKetBunga, $nKreditBunga, 0, false, '', '', '', $cCabangEntryBunga, $d->UserName);
                Upd::updJurnalLainLain($dTgl, $cFakturBunga, $d->Rekening, $cKetBunga, 0, $nKreditBunga, false, '', '', '', $cCabangEntryBunga, $d->UserName);
            }

            // Bagian Kedua - Pajak Tabungan
            $cKodeTransaksiPajak = GetterSetter::getDBConfig('msKodePajakBungaTabungan');
            $vaDataPajak = DB::table('mutasitabungan as m')
                ->leftJoin('tabungan as t', 't.Rekening', '=', 'm.Rekening')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('kodetransaksi as k', 'k.Kode', '=', 'm.KodeTransaksi')
                ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningCadanganBunga')
                ->select(
                    'm.CabangEntry',
                    't.GolonganTabungan',
                    DB::raw('MAX(m.Faktur) as Faktur'),
                    'm.KodeTransaksi',
                    DB::raw('SUM(m.Debet) as Debet'),
                    DB::raw('SUM(m.Kredit) as Kredit'),
                    'g.Rekening',
                    'k.Rekening as RekeningPajak',
                    'm.UserName',
                    'r.Keterangan as NamaBiayaBunga',
                    'g.Keterangan as NamaTabungan'
                )
                ->whereRaw("LEFT(m.Faktur, 2) in ('BT','AT')")
                ->where('m.tgl', '=', $dTgl)
                ->where('k.Kode', '=', $cKodeTransaksiPajak)
                ->groupBy('m.CabangEntry', 'm.KodeTransaksi', 't.GolonganTabungan')
                ->get();

            foreach ($vaDataPajak as $d) {
                $cFaktur = $d->Faktur;
                $cKet = "Pajak Tab. " . $d->NamaTabungan;
                $nDebet = $d->Debet;
                $cCabangEntry = $d->CabangEntry;

                Upd::updJurnalLainLain($dTgl, $cFaktur, $d->Rekening, $cKet, $nDebet, 0, false, '', '', '', $cCabangEntry, $d->UserName);
                Upd::updJurnalLainLain($dTgl, $cFaktur, $d->RekeningPajak, $cKet, 0, $nDebet, false, '', '', '', $cCabangEntry, $d->UserName);
            }

            // Bagian Ketiga - Administrasi Tabungan
            $cKodeTransaksiAdm = GetterSetter::getDBConfig("msKodeAdmBulanan");
            $vaDataAdm = DB::table('mutasitabungan as m')
                ->leftJoin('tabungan as t', 't.Rekening', '=', 'm.Rekening')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('kodetransaksi as k', 'k.Kode', '=', 'm.KodeTransaksi')
                ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningCadanganBunga')
                ->select(
                    'm.CabangEntry',
                    't.GolonganTabungan',
                    DB::raw('MAX(m.Faktur) as Faktur'),
                    'm.KodeTransaksi',
                    DB::raw('SUM(m.Debet) as Debet'),
                    DB::raw('SUM(m.Kredit) as Kredit'),
                    'g.Rekening',
                    'k.Rekening as RekeningAdministrasi',
                    'm.UserName',
                    'r.Keterangan as NamaBiayaBunga',
                    'g.Keterangan as NamaTabungan'
                )
                ->whereRaw("LEFT(m.Faktur, 2) in ('BT','AT')")
                ->where('m.tgl', '=', $dTgl)
                ->where('k.Kode', '=', $cKodeTransaksiAdm)
                ->groupBy('m.CabangEntry', 'm.KodeTransaksi', 't.GolonganTabungan')
                ->get();

            foreach ($vaDataAdm as $d) {
                $cFaktur = $d->Faktur;
                $cKet = "Adm. Tab " . $d->NamaTabungan;
                $nDebet = $d->Debet;
                $cCabangEntry = $d->CabangEntry;

                Upd::updJurnalLainLain($dTgl, $cFaktur, $d->Rekening, $cKet, $nDebet, 0, false, '', '', '', $cCabangEntry, $d->UserName);
                Upd::updJurnalLainLain($dTgl, $cFaktur, $d->RekeningAdministrasi, $cKet, 0, $nDebet, false, '', '', '', $cCabangEntry, $d->UserName);
            }
        } catch (\Exception $e) {
        }
    }

    
    // PROSES BUNGA DEPOSITO
    public function getDataPostingBungaDeposito(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            // $nReqCount = count($vaRequestData);
            // if ($nReqCount < 2 || $nReqCount > 2) {
            //     $vaRetVal = [
            //         "status" => "99",
            //         "message" => "REQUEST TIDAK VALID"
            //     ];
            //     Func::writeLog('Posting Bunga', 'getDataPostingBungaDeposito', $vaRequestData, $vaRetVal, $cUser);
            //     // return $vaRetVal;
            //     return response()->json(['status' => 'error']);
            // }
            $dTglAwal = Func::Date2String($vaRequestData['TglAwal']);
            $dTglAkhir = Func::Date2String($vaRequestData['TglAkhir']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
            if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $nHariAwal = date('d', Func::Tgl2Time($dTglAwal));
            $nHariAkhir = date('d', Func::Tgl2Time($dTglAkhir));
            if ($dTglAkhir == Func::Date2String(Func::EOM($dTglAkhir))) {
                $nHariAkhir = 31;
            }
            $dTgl = Func::Date2String(GetterSetter::getTglTransaksi());
            $dTglTransaksi = $dTgl;
            $vaData = DB::table('deposito as d')
                ->select(
                    'g.Lama',
                    'd.Kode',
                    'd.Rekening',
                    'd.Tgl',
                    'r.Nama',
                    'd.NoBilyet',
                    'd.RekeningTabungan',
                    'd.GolonganDeposito',
                    'd.Nominal',
                    'd.ARO',
                    DB::raw('g.Keterangan as NamaGolonganDeposito'),
                    'd.Nominal',
                    'd.RekeningLama',
                    'd.CairBunga',
                    DB::raw('sum(m.setoranplafond) as Setoran'),
                    DB::raw('sum(m.pencairanplafond) as Pencairan'),
                    'm.Tgl as TglMutasi',
                    'd.RekeningFeeDeposito',
                    'd.FeeDeposito',
                    'd.CabangEntry',
                    DB::raw('sum(setoranplafond - pencairanplafond) as NominalDeposito'),
                    'd.RekeningPB'
                )
                ->leftJoin('golongandeposito as g', 'g.kode', '=', 'd.golongandeposito')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
                ->leftJoin('mutasideposito as m', 'm.rekening', '=', 'd.rekening')
                ->leftJoin('cabang as c', function ($join) use ($dTgl) {
                    $join->on('c.Kode', '=', DB::raw('IFNULL((SELECT CabangEntry FROM deposito_cabang WHERE Rekening = d.Rekening AND tgl <= \'' . $dTgl . '\' ORDER BY tgl DESC LIMIT 1), d.CabangEntry)'));
                })
                ->where('m.Tgl', '<', $dTglAwal)
                ->when(
                    $cJenisGabungan !== 'C',
                    function ($query) use ($cJenisGabungan, $cCabang) {
                        $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                    }
                )
                ->groupBy('d.rekening')
                ->havingRaw('NominalDeposito > 0')
                ->havingRaw("DAY(d.tgl) >= '$nHariAwal'")
                ->havingRaw("DAY(d.Tgl) <= '$nHariAkhir'")
                ->orderByRaw("RIGHT(d.tgl, 2), d.rekening")
                ->get();

            if ($vaData->count() > 0) {
                $nRow = 0;
                $nTotalBunga = 0;
                $nTotalPajak = 0;
                $nTotalNetto = 0;
                $nTotalAccrual = 0;
                $nTotalFee = 0;
                $nBunga = 0;
                $nPajak = 0;
                $nAccrual = 0;
                $cRekeningFee = 0;
                foreach ($vaData as $d) {
                    $cRekening = $d->Rekening;
                    $cCairBunga = '';
                    $dJthTmp = PerhitunganDeposito::getTglJthTmpDeposito($cRekening, $dTglAkhir);
                    $cRekTabungan = trim($d->RekeningTabungan);
                    $vaData2 = DB::table('tabungan')
                        ->select('Rekening')
                        ->where('Rekening', '=', $cRekTabungan)
                        ->first();
                    $cRekTabungan = '';
                    if ($vaData2) {
                        $cRekTabungan = $vaData2->Rekening;
                    }
                    if (empty($cRekTabungan)) {
                        $cRekPB = $d->RekeningPB;
                        $vaData3 = DB::table('rekening')
                            ->select('Kode')
                            ->where('Kode', '=', $cRekPB)
                            ->first();
                        $cRekPB = '';
                        if ($vaData3) {
                            $cRekPB = $vaData3->Kode;
                        }
                        if (empty($cRekPB)) {
                            $cRekTabungan = "Tunai";
                        } else {
                            $cRekTabungan = $cRekPB;
                        }
                        if ($d->CairBunga == 'C') {
                            $cRekTabungan = "Cadangan";
                        }
                    }
                    if ($d->CairBunga == 'C') {
                        $cRekTabungan = "Cadangan";
                    }
                    if ($d->CairBunga == 'K') {
                        $cRekTabungan = 'Tunai';
                    }
                    if ($d->ARO == 'P') {
                        $cRekTabungan = 'ARO P, B';
                        $cCairBunga = "Bunga Masuk ke Pokok";
                    }
                    $dTglPencairan = GetterSetter::GetTglIdentik($d->Tgl, $dTglAkhir);
                    $nLama = $d->Lama;

                    $nPencairan = $d->NominalDeposito;
                    if ($dTglPencairan < $dTglAkhir) {
                        $dTgl = $dTglPencairan;
                    }
                    $va = PerhitunganDeposito::getPencairanDeposito($cRekening, max($dTglTransaksi, $dTglAkhir));
                    $nBunga = $va['Bunga'];
                    $nAccrual = round($va['Accrual']);
                    $nPajak = $va['Pajak'];
                    $nNetto = $nBunga - $nPajak;
                    $nFee = $va['Fee'];

                    $nTotalBunga += $nBunga;
                    $nTotalPajak += $nPajak;
                    $nTotalAccrual += 0;
                    $nTotalNetto += $nNetto;
                    $nTotalFee += $nFee;
                    $bShow = true;
                    if ($cRekTabungan == 'Tunai') {
                        $bShow = true;
                        $nTimeTglCair = Func::Tgl2Time($dTglPencairan);
                        $bCadangan = Func::isHoliday($nTimeTglCair);
                    }
                    $vaResult[] = [
                        'Cek' => '1',
                        'No' => ++$nRow,
                        'Rekening' => $cRekening,
                        'JthTmp' => $dJthTmp,
                        'Nama' => $d->Nama,
                        'TglCair' => Func::String2Date($dTglPencairan),
                        'Nominal' => $d->NominalDeposito,
                        'Rate' => $va['Rate'],
                        'Bunga' => $nBunga,
                        'Accrual' => $nAccrual,
                        'Pajak' => $nPajak,
                        'Netto' => $nNetto,
                        'RekBunga' => $cRekTabungan,
                        'CairBunga' => $cCairBunga,
                        'Fee' => $nFee,
                        'RekFee' => $cRekeningFee,
                    ];
                }
                // JIKA REQUEST SUKSES
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Posting Bunga', 'getDataPostingBungaDeposito', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            }
            // else {
            //     $vaRetVal = [
            //         "status" => "03",
            //         "message" => "DATA TIDAK DITEMUKAN"
            //     ];
            //     Func::writeLog('Posting Bunga', 'getDataPostingBungaDeposito', $vaRequestData, $vaRetVal, $cUser);
            //     // return response()->json($vaRetVal);
            //     return response()->json(['status' => 'error']);
            // }
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
            Func::writeLog('Posting Bunga', 'getDataPostingBungaDeposito', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function prosesBungaDeposito(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTgl = GetterSetter::getTglTransaksi();
            $nBunga = 0;
            $nFee = 0;
            foreach ($vaRequestData as $data) {
                $cFaktur = GetterSetter::getLastFaktur('DP', 6);
                $cRekening = $data['Rekening'];
                $nBunga = $data['Bunga'];
                $nFee = $data['Fee'];
                $nPajak = $data['Pajak'];
                $dJthTmp = $data['JthTmp'];
                $nAccrual = $data['Accrual'];
                $cRekFee = $data['RekFee'];
                $vaData = DB::table('deposito AS d')
                    ->leftJoin('cabang AS c', function ($join) use ($dTgl) {
                        $join->on('c.Kode', '=', DB::raw('IFNULL((SELECT CabangEntry FROM deposito_cabang
                                            WHERE Rekening = d.Rekening AND tgl <= \'' . $dTgl . '\'
                                            ORDER BY tgl DESC LIMIT 1), d.CabangEntry)'))
                            ->where('d.Tgl', '<=', $dTgl);
                    })
                    ->select('c.Kode AS CabangEntry')
                    ->where('d.Rekening', '=', $cRekening)
                    ->get();
                foreach ($vaData as $d) {
                    $cCabangEntry = $d->CabangEntry;
                }
                $cPencairanBungaDeposito = GetterSetter::getDBConfig('msKodeBungaDeposito');
                $cNama = PerhitunganDeposito::getNamaDeposan($cRekening);
                $cRekTabungan = $data['RekBunga'];
                $cKeterangan = "Tabungan [ " . $cRekTabungan . " ] " . $cNama;
                $cRekPB = '';
                if ($cRekTabungan == "Tunai") {
                    $optCaraPencairan = "K";
                } else if ($cRekTabungan == "Cadangan") {
                    $optCaraPencairan = "C";
                } else if ($cRekTabungan == "ARO P, B") {
                    $optCaraPencairan = "A";
                } else {
                    $vaData2 = DB::table('rekening')
                        ->select('Kode')
                        ->where('Kode', '=', $cRekTabungan)
                        ->first();
                    if ($vaData2) {
                        $optCaraPencairan = "P";
                        $cRekPB = $cRekTabungan;
                    } else {
                        $optCaraPencairan = "T";
                    }
                }

                if ($nBunga > 0) {
                    Upd::updMutasiDeposito(false, 0, $cFaktur, $cRekening, $cCabangEntry, $dTgl, $dJthTmp, 0, 0, $nBunga, $nPajak, 0, 0, 0, $optCaraPencairan, $cKeterangan, true, $nAccrual, $nFee, $cRekPB, $cUser);
                }
                if ($nFee > 0) {
                    $cKeteranganFee = "Fee Deposito [ " . $cRekFee . " ] " . GetterSetter::getNamaRegisterNasabah($cRekTabungan);
                    Upd::updMutasiTabungan($cFaktur, $dTgl, $cRekFee, $cPencairanBungaDeposito, $cKeteranganFee, $nFee, GetterSetter::getDBConfig("msKodeCabang"), true, $cUser);
                }
                if ($optCaraPencairan == "T") {
                    echo ($cRekTabungan . ' -> ' . $optCaraPencairan . '<br>');
                    $cKodeBungaDeposito = GetterSetter::getDBConfig('msKodeBungaDeposito');
                    $cKodePajakDeposito = GetterSetter::getDBConfig('msKodePajakDeposito');
                    $cKeteranganBunga = "Bunga Deposito [ " . $cRekFee . " ] " . GetterSetter::getNamaRegisterNasabah($cRekTabungan);
                    Upd::updMutasiTabungan($cFaktur, $dTgl, $cRekTabungan, $cKodeBungaDeposito, $cKeteranganBunga, $nBunga - $nPajak, $cCabangEntry, true, $cUser);
                }
                GetterSetter::setLastFaktur('DP');
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "success"
            ];
            Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Posting Bunga', 'prosesPostingTabungan', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
