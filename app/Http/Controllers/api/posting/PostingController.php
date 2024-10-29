<?php

namespace App\Http\Controllers\api\posting;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostingController extends Controller
{

    public static function postingMutasiAnggota(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $username = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $tglTransaksi = $vaRequestData->Tgl;
            $data = DB::table('mutasianggota as m')
                ->select(
                    'm.*',
                    'm.CabangEntry',
                    'm.Faktur',
                    'm.DK',
                    'm.Keterangan',
                    'g.RekeningSimpanan',
                    'm.Tgl',
                    'm.Jumlah',
                    'm.DebetPokok',
                    'm.KreditPokok',
                    'm.DebetWajib',
                    'm.KreditWajib',
                    'm.UserName',
                    'm.Kas',
                    DB::raw('LEFT(r.kode, 3) AS CabangNasabah')
                )
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'm.kode')
                ->leftJoin('golongansimpanan as g', 'g.Kode', '=', 'm.GolonganAnggota')
                ->where('m.Tgl', $tglTransaksi)
                ->get();
            foreach ($data as $d) {
                $faktur = $d->Faktur;
                $tgl = $d->Tgl;
                $cabangEntry = $d->CabangEntry;
                $cabangNasabah = $d->CabangNasabah;
                $vaCab = GetterSetter::getRekeningAntarKantor($cabangEntry, $cabangNasabah);
                $rekeningAKP = $vaCab['RekeningAKP'];
                $rekeningAKA = $vaCab['RekeningAKA'];
                $rekeningAKEntry = $vaCab['RekeningAKEntry'];
                $rekeningAKLawan = $vaCab['RekeningAKLawan'];
                $rekSimPokok = Func::getRekeningLawan('RekeningSimpanan', 'golongansimpanan', "Kode = '01'");
                $rekSimWajib = Func::getRekeningLawan('RekeningSimpanan', 'golongansimpanan', "Kode = '02'");
                $rekKas = GetterSetter::getKasTeller($username, $tgl);
                if ($d->Kas == 'P') {
                    $rekKas = $d->RekeningPB;
                    if (empty($rekKas)) {
                        $rekKas = GetterSetter::getDBConfig('msRekeningPB');
                    }
                }

                $rekDebetPokok = "";
                $rekKreditPokok = "";
                $rekDebetWajib = "";
                $rekKreditWajib = "";
                $cabangDebetP = GetterSetter::getDBConfig('msKodeCabang');
                $cabangKreditP = GetterSetter::getDBConfig('msKodeCabang');
                $cabangDebetW = GetterSetter::getDBConfig('msKodeCabang');
                $cabangKreditW = GetterSetter::getDBConfig('msKodeCabang');
                $jumlahPokok = 0;
                $jumlahWajib = 0;
                $debetPokok = $d->DebetPokok;
                $kreditPokok = $d->KreditPokok;
                $debetWajib = $d->DebetWajib;
                $kreditWajib = $d->KreditWajib;
                if ($debetPokok > 0) {
                    $rekDebetPokok = $rekSimPokok;
                    $rekKreditPokok = $rekKas;
                    $jumlahPokok = $debetPokok;
                    $cabangDebetP = $cabangNasabah;
                    $cabangKreditP = $cabangEntry;
                    $rekeningAKP = $vaCab['RekeningAKA'];
                    $rekeningAKA = $vaCab['RekeningAKP'];
                }

                if ($kreditPokok > 0) {
                    $rekDebetPokok = $rekKas;
                    $rekKreditPokok = $rekSimPokok;
                    $jumlahPokok = $kreditPokok;
                    $cabangDebetP = $cabangEntry;
                    $cabangKreditP = $cabangNasabah;
                }

                if ($debetWajib > 0) {
                    $rekDebetWajib = $rekSimWajib;
                    $rekKreditWajib = $rekKas;
                    $jumlahWajib = $debetWajib;
                    $cabangDebetW = $cabangNasabah;
                    $cabangKreditW = $cabangEntry;
                    $rekeningAKP = $vaCab['RekeningAKA'];
                    $rekeningAKA = $vaCab['RekeningAKP'];
                }

                if ($kreditWajib > 0) {
                    $rekDebetWajib = $rekKas;
                    $rekKreditWajib = $rekSimWajib;
                    $jumlahWajib = $kreditWajib;
                    $cabangDebetW = $cabangEntry;
                    $cabangKreditW = $cabangNasabah;
                }
                $exists = BukuBesar::where('Faktur', $faktur)->exists();
                if ($exists) {
                    Upd::deleteBukuBesar('20', $faktur);
                    // Simpanan Pokok
                    Upd::updBukuBesar('20', $faktur, $cabangDebetP, $tgl, $rekDebetPokok, $d->Keterangan, $jumlahPokok, 0, $d->UserName, $d->KAS);
                    Upd::updBukuBesar('20', $faktur, $cabangKreditP, $tgl, $rekKreditPokok, $d->Keterangan, 0, $jumlahPokok, $d->UserName, $d->KAS);
                    // Simpanan Wajib
                    Upd::updBukuBesar('20', $faktur, $cabangDebetW, $tgl, $rekDebetWajib, $d->Keterangan, $jumlahWajib, 0, $d->UserName, $d->KAS);
                    Upd::updBukuBesar('20', $faktur, $cabangKreditW, $tgl, $rekKreditWajib, $d->Keterangan, 0, $jumlahWajib, $d->UserName, $d->KAS);
                } else {
                    // Simpanan Pokok
                    Upd::updBukuBesar('20', $faktur, $cabangDebetP, $tgl, $rekDebetPokok, $d->Keterangan, $jumlahPokok, 0, $d->UserName, $d->KAS);
                    Upd::updBukuBesar('20', $faktur, $cabangKreditP, $tgl, $rekKreditPokok, $d->Keterangan, 0, $jumlahPokok, $d->UserName, $d->KAS);
                    // Simpanan Wajib
                    Upd::updBukuBesar('20', $faktur, $cabangDebetW, $tgl, $rekDebetWajib, $d->Keterangan, $jumlahWajib, 0, $d->UserName, $d->KAS);
                    Upd::updBukuBesar('20', $faktur, $cabangKreditW, $tgl, $rekKreditWajib, $d->Keterangan, 0, $jumlahWajib, $d->UserName, $d->KAS);
                }
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
            throw $th;
        }
    }

    public static function postingMutasiSimpanan(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $tglTransaksi = $request->Tgl;
            $data = DB::table('mutasitabungan as m')
                ->select(
                    't.CabangEntry as CabangNasabah',
                    'm.Faktur',
                    'm.DK',
                    'm.Keterangan',
                    'g.Rekening as RekeningTabungan',
                    'g.RekeningBunga',
                    'm.CabangEntry',
                    'm.Tgl',
                    'm.Jumlah',
                    'g.RekeningCadanganPajak',
                    'k.Rekening as RekeningKodeTransaksi',
                    'k.DK',
                    'k.Kas',
                    'm.KodeTransaksi',
                    'm.UserName'
                )
                ->leftJoin('tabungan as t', 't.Rekening', '=', 'm.Rekening')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->leftJoin('kodetransaksi as k', 'k.Kode', '=', 'm.KodeTransaksi')
                ->where('m.Tgl', $tglTransaksi)
                ->addSelect(DB::raw("IFNULL((select CabangEntry from tabungan_cabang where Rekening = t.Rekening and tgl <= m.Tgl order by tgl desc limit 1 ), t.CabangEntry) as CabangNasabah"))
                ->get();
            foreach ($data as $d) {
                $faktur = $d->Faktur;
                $fakturAwalan = substr($faktur, 0, 2);
                if ($fakturAwalan == "BT" || $fakturAwalan == "AT" || $fakturAwalan == "AP") {
                    continue; // Lewatkan iterasi berikutnya jika faktur memenuhi kriteria
                }
                $tgl = $d->Tgl;
                $cabangNasabah = $d->CabangNasabah;
                // $cabangEntry = $d->CabangEntry;
                $cabangEntry = '101';
                $cabangDebet = $cabangEntry;
                $cabangKredit = $cabangNasabah;
                $lawan = "Debet";
                $vaRekening['Kredit'] = $d->RekeningTabungan;
                $vaRekening['Debet'] = '';
                if ($d->DK == "D") {
                    $cabangDebet = $cabangNasabah;
                    $cabangKredit = $cabangEntry;
                    $lawan = "Kredit";
                    $vaRekening['Debet'] = $d->RekeningTabungan;
                }
                $vaRekening[$lawan] = $d->RekeningKodeTransaksi;
                if ($d->Kas == 'K') {
                    $vaRekening[$lawan] = GetterSetter::getKasTeller($d->UserName, $d->Tgl);
                }
                if ($d->KodeTransaksi == GetterSetter::getDBConfig("msKodeBungaTabungan")) {
                    $vaRekening[$lawan] = $d->RekeningBunga;
                }
                if (substr($faktur, 0, 2) == "PB") {
                    $vaRekening[$lawan] = GetterSetter::getDBConfig("msRekeningPB");
                }
                $exists = BukuBesar::where('Faktur', $faktur)->exists();
                if ($exists) {
                    Upd::deleteBukuBesar('1', $faktur);
                    Upd::updBukuBesar('1', $faktur, $cabangDebet, $tgl, $vaRekening['Debet'], $d->Keterangan, $d->Jumlah, 0, $d->UserName, $d->Kas);
                    Upd::updBukuBesar('1', $faktur, $cabangKredit, $tgl, $vaRekening['Kredit'], $d->Keterangan, 0, $d->Jumlah, $d->UserName, $d->Kas);
                } else {
                    Upd::updBukuBesar('1', $faktur, $cabangDebet, $tgl, $vaRekening['Debet'], $d->Keterangan, $d->Jumlah, 0, $d->UserName, $d->Kas);
                    Upd::updBukuBesar('1', $faktur, $cabangKredit, $tgl, $vaRekening['Kredit'], $d->Keterangan, 0, $d->Jumlah, $d->UserName, $d->Kas);
                }
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public static function postingMutasiSimpananBerjangka(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $tglTransaksi = $request->Tgl;
            $data = DB::table('mutasideposito as m')
                ->select('m.Faktur', 'm.Rekening', 'm.Kas', 'm.ID', 'm.SetoranPlafond', 'm.PencairanPlafond', 'm.Bunga', 'm.Pajak', 'm.KoreksiBunga', 'm.Pinalty', 'm.Fee', 'm.DTitipan', 'm.KTitipan', 'm.Tgl', 'r.Nama', 'd.RekeningTabungan', 'm.UserName', 'm.CabangEntry', 'm.Accrual', 'g.RekeningBunga', 'g.RekeningPajakBunga', 'g.CadanganBunga', 'g.RekeningPinalti', 'g.RekeningAkuntansi', 'g.RekeningAccrual', 'g.RekeningFeeDeposito', 'm.rekeningakuntansi as RekeningPemindahBukuan')
                ->leftJoin('deposito as d', 'd.rekening', '=', 'm.Rekening')
                ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
                ->leftJoin('golongandeposito as g', function ($join) {
                    $join->on('g.Kode', '=', DB::raw('IFNULL((select golongandeposito from deposito_perubahangolongan where Rekening = d.Rekening and tgl <= m.Tgl order by tgl desc limit 1), d.GolonganDeposito)'));
                })
                ->where('m.Tgl', $tglTransaksi)
                ->addSelect(DB::raw("IFNULL((select CabangEntry from deposito_cabang where Rekening = d.Rekening and tgl <= m.Tgl order by tgl desc limit 1), d.CabangEntry) as CabangNasabah"))
                ->get();
            foreach ($data as $d) {
                $faktur = $d->Faktur;
                $rekening = $d->Rekening;
                $rekeningKas = '';
                if ($d->Kas == 'K') {
                    $rekeningKas = GetterSetter::getKasTeller($d->UserName, $d->Tgl);
                } else if ($d->Kas == 'P') {
                    $rekeningKas = GetterSetter::getDBConfig("msRekeningPB");
                    if ($d->RekeningPemindahBukuan <> '') {
                        $rekeningKas = $d->RekeningPemindahBukuan;
                    }
                } else if ($d->Kas == 'C') {
                    $rekeningKas = $d->CadanganBunga;
                } else if ($d->Kas == 'A') {
                    $rekeningKas = $d->RekeningAkuntansi;
                } else {
                    $rekeningKas = GetterSetter::getDBConfig("msRekeningPB");
                }
                $total = $d->SetoranPlafond - $d->PencairanPlafond - $d->Bunga + $d->Pajak + $d->Pinalty;
                $DKas = $total > 0 ? $total : 0;
                $KKas = $total < 0 ? $total * -1 : 0;
                $cabangEntry = $d->CabangEntry;
                $cabangNasabah = $d->CabangNasabah;
                $d->Accrual = 0;
                $bunga = $d->Bunga - $d->Accrual;
                $exists = BukuBesar::where('Faktur', $faktur)->exists();
                if ($exists) {
                    Upd::deleteBukuBesar('0', $faktur);
                }
                if ($DKas > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangEntry, $d->Tgl, $rekeningKas, 'Setoran Dep. [' . $rekening . '] ' . $d->Nama, $DKas, 0, $d->UserName, $d->Kas);
                }
                if ($d->SetoranPlafond > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningAkuntansi, 'Setoran Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->SetoranPlafond, $d->UserName, $d->Kas);
                }
                if ($d->PencairanPlafond > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningAkuntansi, 'Pencairan Dep. [' . $rekening . '] ' . $d->Nama, $d->PencairanPlafond, 0, $d->UserName, $d->Kas);
                }
                if ($KKas > 0 && $d->PencairanPlafond > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangEntry, $d->Tgl, $rekeningKas, 'Pencairan Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->PencairanPlafond, $d->UserName, $d->Kas);
                }
                if ($bunga > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningBunga, 'Bunga Dep. [' . $rekening . '] ' . $d->Nama, $bunga, 0, $d->UserName, $d->Kas);
                    Upd::updBukuBesar('0', $faktur, $cabangEntry, $d->Tgl, $rekeningKas, 'Bunga Dep. [' . $rekening . '] ' . $d->Nama, 0, $bunga, $d->UserName, $d->Kas);
                }
                if ($d->Accrual > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningAccrual, 'Accrual Dep. [' . $rekening . '] ' . $d->Nama, $d->Accrual, 0, $d->UserName, $d->Kas);
                }
                if ($d->Pajak > 0) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningPajakBunga, 'Pajak Bunga Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->Pajak, $d->UserName, $d->Kas);
                }
                if ($d->Pinalty) {
                    Upd::updBukuBesar('0', $faktur, $cabangNasabah, $d->Tgl, $d->RekeningPinalti, 'Pinalty Dep. [' . $rekening . '] ' . $d->Nama, 0, $d->Pinalty, $d->UserName, $d->Kas);
                }
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 'error']);
        }
    }

    public static function postingKolektibilitas(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);
            $vaRequestData = $request->json()->all();
            $username = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $tglTransaksi = $vaRequestData['Tgl'];

            DB::table('debitur_kol_harian')->where('Periode', $tglTransaksi)->delete();

            $data = DB::table('debitur as d')
                ->select(
                    'd.RekeningLama',
                    'd.Rekening',
                    'd.Tgl',
                    'r.Nama',
                    'r.Alamat',
                    'r.Telepon',
                    'd.SektorEkonomi',
                    'd.GolonganDebitur',
                    'd.Lama',
                    'd.NoSPK',
                    'd.Plafond',
                    DB::raw('IFNULL(SUM(a.DPokok - a.KPokok), 0) as SaldoPokok'),
                    'd.AO',
                    DB::raw('IFNULL(SUM(a.KPokok), 0) as PembayaranPokok'),
                    DB::raw('IFNULL(SUM(a.KBunga), 0) as PembayaranBunga'),
                    'd.SukuBunga',
                    'd.CaraPerhitungan',
                    'd.GolonganKredit',
                    DB::raw('MAX(a.tgl) as TglAkhir'),
                    'd.JenisPinjaman'
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('angsuran as a', function($join) use ($tglTransaksi) {
                    $join->on('a.Rekening', '=', 'd.Rekening')
                         ->where('a.Tgl', '<=', $tglTransaksi);
                })
                ->leftJoin('ao as o', 'o.Kode', '=', 'd.AO')
                ->leftJoin('cabang as c', DB::raw('left(d.Rekening, 3)'), '=', 'c.Kode')
                ->where('d.Tgl', '<=', $tglTransaksi)
                ->groupBy('d.Rekening')
                ->having(DB::raw('SaldoPokok'), '>', 0)
                ->get();

                foreach ($data as $d) {
                    //echo $d->Rekening. "\n";
                    $result = GetterSetter::GetTunggakanHitung(
                        $d->Rekening,
                        $tglTransaksi,
                        $d->Tgl,
                        $d->CaraPerhitungan,
                        $d->Lama,
                        $d->Plafond,
                        $d->PembayaranPokok,
                        $d->SukuBunga,
                        $d->PembayaranBunga
                    );
                    // echo $d->Rekening . "\n";

                    $JTHTMP = Carbon::parse($d->Tgl)->addMonths($d->Lama)->format('Y-m-d');

                    // Dapatkan DateTime saat ini
                    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');

                    $insertData = [
                        'Periode' => $tglTransaksi,
                        'Rekening' => $d->Rekening,
                        'TglRealisasi' => $d->Tgl,
                        'Kol' => $result['Kol'],
                        'Plafond' => $d->Plafond,
                        'BakiDebet' => $result['BakiDebet'],
                        'TPokok' => $result['T.Pokok'],
                        'TBunga' => $result['T.Bunga'],
                        'FR' => $result['FR'],
                        'FRPokok' => $result['FRPokok'],
                        'FRBunga' => $result['FRBunga'],
                        'FRTunggakan' => $result['FR'],
                        'HariTelat' => $result['HariTerlambat'],
                        'HariTelatPokok' => $result['HariTerlambatPokok'],
                        'HariTelatBunga' => $result['HariTerlambatBunga'],
                        'Denda' => $result['Denda'],
                        'PPAP' => $result['PPAP'],
                        'ProsentaseProyeksi' => '0',
                        'TotalJaminan' => $result['NilaiJaminanNJOP'],
                        'CaraAngsuran' => $d->CaraPerhitungan,
                        'JTHTMP' => $JTHTMP,
                        'UserName' => 'Godong.id',
                        'DateTime' => $currentDateTime 
                    ];
                
                    DB::table('debitur_kol_harian')->insert($insertData);
                }
                
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->$th;
            throw $th;
        }
    }
}
