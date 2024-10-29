<?php

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Helpers\Upd;
use App\Http\Controllers\api\auth\LoginController;
use App\Http\Controllers\api\laporanpinjaman\AngsuranPinjamanController;
use App\Http\Controllers\api\laporanpinjaman\PelunasanPinjamanController;
use App\Http\Controllers\api\laporansimpanan\BlokirSimpananController;
use App\Http\Controllers\api\laporansimpanan\PembukaanSimpananController;
use App\Http\Controllers\api\laporansimpanan\TutupSimpananController;
use App\Http\Controllers\api\laporansimpananberjangka\JadwalPencairanBungaSimpananBerjangkaController;
use App\Http\Controllers\api\laporansimpananberjangka\KartuMutasiSimpananBerjangkaController;
use App\Http\Controllers\api\laporansimpananberjangka\SimpananBerjangkaJatuhTempoController;
use App\Http\Controllers\api\master\AktivaController;
use App\Http\Controllers\api\master\PictureController;
use App\Http\Controllers\api\master\RekeningController;
use App\Http\Controllers\api\master\GolonganStockController;
use App\Http\Controllers\api\master\InstansiController;
use App\Http\Controllers\api\master\JenisIdentitasController;
use App\Http\Controllers\api\master\AgamaController;
use App\Http\Controllers\api\master\CustomController;
use App\Http\Controllers\api\master\AoController;
use App\Http\Controllers\api\master\GolonganPinjamanController;
use App\Http\Controllers\api\master\JenisPengikatanJaminanController;
use App\Http\Controllers\api\master\JaminanController;
use App\Http\Controllers\api\master\KasController;
use App\Http\Controllers\api\master\DaerahController;
use App\Http\Controllers\api\master\PasanganController;
use App\Http\Controllers\api\master\PekerjaanController;
use App\Http\Controllers\api\master\UangPecahanController;
use App\Http\Controllers\api\master\SukuBungaController;
use App\Http\Controllers\api\master\PerubahanSukuBungaController;
use App\Http\Controllers\api\master\GolonganSimpananController;
use App\Http\Controllers\api\master\GolonganSimpananBerjangkaController;
use App\Http\Controllers\api\master\ProvisiDanAdministrasiController;
use App\Http\Controllers\api\master\KodeTransaksiController;
use App\Http\Controllers\api\master\GolonganAktivaController;
use App\Http\Controllers\api\master\SuratKreditController;
use App\Http\Controllers\api\master\RegisterNasabahController;
use App\Http\Controllers\api\master\KasKeluarController;
use App\Http\Controllers\api\master\KasMasukController;
use App\Http\Controllers\api\master\VoltTellerController;
use App\Http\Controllers\api\pinjaman\PengikatanJaminanController;
use App\Http\Controllers\api\simpanan\BlokirRekeningSimpananController;
use App\Http\Controllers\api\simpanan\PembukaanRekeningSimpananController;
use App\Http\Controllers\api\simpanan\TransferAntarRekeningSimpananController;
use App\Http\Controllers\api\simpanan\TutupRekeningSimpananController;
use App\Http\Controllers\api\simpanan\CetakHeaderSimpananController;
use App\Http\Controllers\api\simpananberjangka\BlokirSimpananBerjangkaController;
use App\Http\Controllers\api\simpananberjangka\RegisterRekeningSimpananBerjangkaController;
use App\Http\Controllers\api\pinjaman\PinjamanController;
use App\Http\Controllers\api\pinjaman\AgunanController;
use App\Http\Controllers\api\pinjaman\AngsuranController;
use App\Http\Controllers\api\pinjaman\AsuransiController;
use App\Http\Controllers\api\pinjaman\HapusBukuPinjamanController;
use App\Http\Controllers\api\pinjaman\KoreksiJadwalAngsuranController;
use App\Http\Controllers\api\pinjaman\NotarisController;
use App\Http\Controllers\api\pinjaman\PembatalanPencairanPinjamanController;
use App\Http\Controllers\api\pinjaman\PengambilanJaminanController;
use App\Http\Controllers\api\pinjaman\PerubahanSukuBungaPinjamanController;
use App\Http\Controllers\api\simpananberjangka\PerubahanSukuBungaSimpananBerjangkaController;
use App\Http\Controllers\api\Teller\TellerController;
use App\Http\Controllers\api\Teller\MutasiAnggotaController;
use App\Http\Controllers\api\Teller\MutasiPembukaanDepositoController;
use App\Http\Controllers\api\Teller\MutasiPencairanDepositoController;
use App\Http\Controllers\api\Teller\MutasiSimpananController;
use App\Http\Controllers\api\tks\PostingSettingController;
use App\Http\Controllers\simpananberjangka\PerubahanSukuBunga;
use App\Http\Controllers\api\pemindahbukuan\PemindahbukuanController;
use App\Http\Controllers\api\jurnal\JurnalController;
use App\Http\Controllers\api\checklistaccounting\CheckListAccountingController;
use App\Http\Controllers\api\konfigurasi\SistemController;
use App\Http\Controllers\api\laporanakuntansi\NeracaController;
use App\Http\Controllers\api\laporanakuntansi\LabaRugiController;
use App\Http\Controllers\api\laporanmutasinonkas\MutasiNonKasController;
use App\Http\Controllers\api\laporanpinjaman\CetakKartuAngsuranController;
use App\Http\Controllers\api\laporanpinjaman\DaftarAgunanDanPengikatanController;
use App\Http\Controllers\api\laporanpinjaman\JadwalAngsuranController;
use App\Http\Controllers\api\laporanpinjaman\PinjamanHapusBukuController;
use App\Http\Controllers\api\laporanpinjaman\PinjamanJatuhTempoController;
use App\Http\Controllers\api\laporanpinjaman\PinjamanNominatifController;
use App\Http\Controllers\api\laporanpinjaman\RegisterJaminanController;
use App\Http\Controllers\api\laporansimpanan\BukuSimpananController;
use App\Http\Controllers\api\laporansimpanan\MutasiSimpananHarianController;
use App\Http\Controllers\api\laporansimpanan\NominatifSimpananController;
use App\Http\Controllers\api\laporansimpananberjangka\DaftarSaldoSimpananBerjangkaController;
use App\Http\Controllers\api\master\BilyetSimpananBerjangkaController;
use App\Http\Controllers\api\pinjaman\CetakSuratPinjamanController;
use App\Http\Controllers\api\pinjaman\PindahAOPinjamanController;
use App\Http\Controllers\api\simpanan\CetakBukuSimpananController;
use App\Http\Controllers\api\simpananberjangka\CetakBilyetSimpananBerjangkaController;
use App\Http\Controllers\api\utility\ProsesAwalHariController;
use App\Http\Controllers\api\konversi\KonversiDataController;
use App\Http\Controllers\api\konversi\KonversiMutasiController;
use App\Http\Controllers\api\konversi\KonversiAktivaController;
use App\Http\Controllers\api\konversi\KonversiNeracaController;
use App\Http\Controllers\api\laporanaktiva\LaporanPenyusutanAktivaController;
use App\Http\Controllers\api\laporanshu\SisaHasilUsahaController;
use App\Http\Controllers\api\master\CabangController;
use App\Http\Controllers\api\master\KonfigurasiSimpananController;
use App\Http\Controllers\api\master\KorwilController;
use App\Http\Controllers\api\master\NisbahController;
use App\Http\Controllers\api\posting\PostingController;
use App\Http\Controllers\api\posting\Posting2Controller;
use App\Http\Controllers\api\posting\PostingBungaController;
use App\Http\Controllers\api\posting\PostingPinjamanController;
use App\Http\Controllers\api\posting\PostingAktivaController;
use App\Http\Controllers\api\kolektibilitas\KolektibilitasController;
use App\Http\Controllers\api\daftartagihan\DaftarTagihanController;
use App\Http\Controllers\api\konfigurasi\KasTellerController;
use App\Http\Controllers\api\master\GolonganNasabahController;
use App\Http\Controllers\api\master\KeterkaitanController;
use App\Http\Controllers\api\master\UserNameController;
use App\Http\Controllers\DashboardController;
use App\Models\master\GolonganNasabah;
use App\Models\teller\MutasiAnggota;
use Carbon\Carbon;
use App\Http\Controllers\api\plugin\PluginController;
use App\Http\Controllers\api\tks\kualitasaset\TotalAsetYangTidakMenghasilkanController;
use App\Http\Controllers\api\tks\LaporanAnalisisController;
use App\Http\Controllers\api\tks\likuiditas\CadanganLikuiditasController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\InvestasiKeuanganController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\InvestasiLikuidController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\InvestasiNonKeuanganController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\ModalLembagaController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\ModalSahamAnggotaController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\PinjamanDariBK3DController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\PiutangBersihController;
use App\Http\Controllers\api\tks\strukturkeuanganyangefektif\SimpananNonSahamController;
use App\Http\Controllers\api\tks\tandatandapertumbuhan\PertumbuhanAnggotaController;
use App\Http\Controllers\api\tks\tandatandapertumbuhan\PertumbuhanAsetController;
use App\Http\Controllers\api\tks\tandatandapertumbuhan\PertumbuhanModalLembagaController;
use App\Http\Controllers\api\tks\tandatandapertumbuhan\PertumbuhanSimpananNonSahamController;
use App\Http\Controllers\api\tks\tandatandapertumbuhan\PertumbuhanSimpananSahamController;
use App\Http\Controllers\api\tks\tingkatpendapatandanbiaya\LabaBersihController;
use App\Http\Controllers\api\tks\tingkatpendapatandanbiaya\PendapatanBiayaLainLainController;
use App\Http\Controllers\api\tks\tingkatpendapatandanbiaya\TotalBiayaOperasionalController;
use App\Http\Controllers\api\tks\tingkatpendapatandanbiaya\TotalBiayaProvisiPinjamanLalaiController;
use App\Http\Controllers\api\tkskoperasi\ATMRController;
use App\Http\Controllers\api\tkskoperasi\SettingATMRController;
//
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// cek plugin ada
Route::post('plugin/checkpluginuser', [PluginController::class, 'checkUserHadPlugin']);

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['check.token', 'change.database'])->group(function () {
    // ----------------------------------------------------------------------------------------------------------------- DASHBOARD
    Route::post('insert/username', [DashboardController::class, 'insertUser']);
    Route::post('gabungan', [DashboardController::class, 'getJenisGabungan']);

    Route::post('count/anggota', [DashboardController::class, 'countAnggota']);
    Route::post('count/simpanan', [DashboardController::class, 'countSimpanan']);
    Route::post('count/simpananberjangka', [DashboardController::class, 'countSimpananBerjangka']);
    Route::post('count/pinjaman', [DashboardController::class, 'countPinjaman']);

    Route::post('saldokas', [DashboardController::class, 'saldoKas']);
    Route::post('aset', [DashboardController::class, 'aset']);
    Route::post('pendapatan', [DashboardController::class, 'pendapatan']);
    Route::post('biaya', [DashboardController::class, 'biaya']);

    Route::post('grafik/aset', [DashboardController::class, 'grafikAset']);
    Route::post('grafik/nasabah', [DashboardController::class, 'grafikNasabah']);

    //------------------------------------------------------------------------------------------------------------------ FUNCTION
    Route::post('get_golongan', function (Request $request) {
        $Rekening = $request->Rekening;
        $response = GetterSetter::getGolongan($Rekening);
        return response()->json($response);
    });

    Route::post('get_kode', function (Request $request) {
        $Rekening = $request->Rekening;
        $response = GetterSetter::getKode($Rekening);
        return response()->json($response);
    });


    Route::post('getlast_faktur', function (Request $request) {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        $response = GetterSetter::getLastFaktur($KODE, $LEN);
        return $response;
    });

    Route::post('get_tgl_transaksi', function () {
        $response = GetterSetter::getTglTransaksi();
        return response()->json($response);
    });

    Route::post('get_rekening', function (Request $request) {
        $KEY = $request->KEY;
        $LEN = $request->LEN;
        $response = GetterSetter::getRekening($KEY, $LEN);
        return response()->json($response);
    });

    Route::post('get_lama_pasif', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = GetterSetter::getLamaPasif($REKENING);
        return response()->json($response);
    });

    Route::post('get_pasif', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getPasif($REKENING, $TGL);
        return response()->json($response);
    });

    Route::post('get_nominal_depo', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = PerhitunganDeposito::getNominalDeposito($REKENING, $TGL);
        return response()->json($response);
    });

    Route::post('get_total_kekayaan', function (Request $request) {
        $KODEINDUK = $request->KodeInduk;
        $TGL = $request->Tgl;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getTotalKekayaan($KODEINDUK, $TGL, $REKENING);
        return response()->json($response);
    });

    Route::post('get_saldo_ratarata', function (Request $request) {
        $TGL = $request->Tgl;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getSaldoRataRata($REKENING, $TGL);
        return response()->json($response);
    });

    Route::post('get_saldo_simpanan_anggota', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = GetterSetter::getSaldoSimpananAnggota($REKENING);
        return response()->json($response);
    });

    Route::post('get_tabungan', function (Request $request) {
        $TGLAWAL = $request->TglAwal;
        $TGLAKHIR = $request->TglAkhir;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getTabungan($REKENING, $TGLAWAL, $TGLAKHIR);
        return response()->json($response);
    });
    Route::post('getSaldoKasTeller', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getSaldoKasTeller($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getRekeningAntarKantor', function (Request $request) {
        $CABANGENTRY = $request->CabangEntry;
        $CABANGNASABAH = $request->CabangNasabah;
        $response = GetterSetter::getRekeningAntarKantor($CABANGENTRY, $CABANGNASABAH);
        return response()->json($response);
    });
    Route::post('getRate', function (Request $request) {
        $TGL = $request->Tgl;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getRate($TGL, $REKENING);
        return response()->json($response);
    });
    Route::post('getGolonganDeposito', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = PerhitunganDeposito::getGolonganDeposito($REKENING);
        return response()->json($response);
    });
    Route::post('getLamaDeposito', function (Request $request) {
        $KODE = $request->Kode;
        $response = PerhitunganDeposito::getLamaDeposito($KODE);
        return response()->json($response);
    });
    Route::post('getTglJthTmpDeposito', function (Request $request) {
        $TGL = $request->Tgl;
        $REKENING = $request->Rekening;
        $response = PerhitunganDeposito::getTglJthTmpDeposito($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getTglMutasiRekening', function (Request $request) {
        $TGL = $request->Tgl;
        $TABLE = $request->Table;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getTglMutasiRekening($REKENING, $TABLE, $TGL);
        return response()->json($response);
    });
    Route::post('getNamaRegisterNasabah', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = GetterSetter::getNamaRegisterNasabah($REKENING);
        return response()->json($response);
    });
    Route::post('getAro', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = PerhitunganDeposito::getAro($REKENING);
        return response()->json($response);
    });
    Route::post('getTglBilyet', function (Request $request) {
        $REKENING = $request->Rekening;
        $response = PerhitunganDeposito::getTglBilyet($REKENING);
        return response()->json($response);
    });
    Route::post('getAdendum', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getAdendum($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getDebiturSukuBunga', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getDebiturSukuBunga($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getKe', function (Request $request) {
        $LAMA = $request->Lama;
        $TGLREALISASI = $request->TglRealisasi;
        $TGL = $request->Tgl;
        $response = GetterSetter::getKe($TGLREALISASI, $TGL, $LAMA);
        return response()->json($response);
    });
    Route::post('getMutasiTabungan', function (Request $request) {
        $TGL = $request->Tgl;
        $REKENING = $request->Rekening;
        $response = GetterSetter::getMutasiTabungan($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getDBConfig', function (Request $request) {
        $KEY = $request->Key;
        $response = GetterSetter::getDBConfig($KEY);
        return $response;
    });
    Route::post('setDBConfig', function (Request $request) {
        $key = $request->Key;
        $value = $request->Value;
        $response = GetterSetter::setDBConfig($key, $value);
        return $response;
    });
    Route::post('getSaldoTabungan', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        // $tglHarianKemarin = date("Y-m-d", strtotime($TGL) - (60 * 60 * 24));
        $response = PerhitunganTabungan::getSaldoTabungan($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getKewajibanBunga', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getKewajibanBunga($REKENING, $TGL);
        return response()->json($response);
    });
    Route::post('getTunggakan', function (Request $request) {
        $REKENING = $request->Rekening;
        $TGL = $request->Tgl;
        $response = GetterSetter::getTunggakan($REKENING, $TGL);
        return response()->json($response);
    });

    Route::post('getTunggakanHitung', function (Request $request) {
        $cRekening            = $request->Rekening;
        $dTgl                 = $request->Tgl;
        $dTglRealisasi        = $request->TglRealisasi;
        $cCaraPerhitungan     = $request->CaraPerhitungan;
        $nLama                = $request->Lama;
        $nPlafond             = $request->Plafond;
        $nPembayaranPokok     = $request->PembayaranPokok;
        $nSukuBunga           = $request->SukuBunga;
        $nPembayaranBunga     = $request->PembayaranBunga;
        $response = GetterSetter::GetTunggakanHitung($cRekening, $dTgl, $dTglRealisasi, $cCaraPerhitungan, $nLama, $nPlafond, $nPembayaranPokok, $nSukuBunga, $nPembayaranBunga);
        return response()->json($response);
    });

    // Route::post('postingKolektibilitas', function (Request $request) {
    //     $dTgl                 = $request->Tgl;
    //     $response = PostingController::postingKolektibilitas($dTgl);
    //     return response()->json($response);
    // });

    Route::post('postingKolektibilitas', [PostingController::class, 'postingKolektibilitas']);

    Route::post('updMutasiTabungan', function (Request $request) {
        $faktur = $request->Faktur;
        $tgl = $request->Tgl;
        $rekening = $request->Rekening;
        $keterangan = $request->Keterangan;
        $jumlah = $request->Jumlah;
        $kodeTr = $request->KodeTransaksi;
        $response = Upd::updMutasiTabungan($faktur, $tgl, $rekening, $kodeTr, $keterangan, $jumlah);
        return response()->json($response);
    });
    Route::post('getFaktur', function (Request $request) {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        $response = GetterSetter::getLastFaktur($KODE, $LEN);
        return $response;
    });
    Route::post('setFaktur', function (Request $request) {
        $KEY = $request->KEY;
        $response = GetterSetter::setLastFaktur($KEY);
        return $response;
    });
    Route::post('getTunggakan', function (Request $request) {
        $Rekening = $request->Rekening;
        $Tgl = GetterSetter::getTglTransaksi();
        $response = GetterSetter::getTunggakan($Rekening, $Tgl);
        return $response;
    });
    Route::post('getTotalPembayaranKredit', function (Request $request) {
        $Rekening = $request->Rekening;
        $Tgl = GetterSetter::getTglTransaksi();
        $response = GetterSetter::getTotalPembayaranKredit($Rekening, $Tgl);
        return $response;
    });
    Route::post('getDetailJaminan', function (Request $request) {
        $Rekening = $request->Rekening;
        $No = $request->No;
        $Jaminan = $request->Jaminan;
        $Tgl = $request->Tgl;
        $response = GetterSetter::getDetailJaminan($Rekening, $No, $Jaminan, $Tgl);
        return $response;
    });
    Route::post('getAdendum', function (Request $request) {
        $Rekening = $request->Rekening;
        $Tgl = $request->Tgl;
        $response = GetterSetter::getAdendum($Rekening, $Tgl);
        return $response;
    });
    Route::post('getAnuitas', function (Request $request) {
        $bunga = $request->SukuBunga / 12 / 100;
        $plafond = $request->Plafond;
        $lama = $request->Lama;
        $response = GetterSetter::getAnuitas($bunga, $plafond, $lama);
        return $response;
    });
    Route::post('getDebiturSukuBunga', function (Request $request) {
        $rekening = $request->Rekening;
        $tgl = $request->Tgl;
        $response = GetterSetter::getDebiturSukuBunga($rekening, $tgl);
        return $response;
    });
    Route::post('perhitunganBunga', function (Request $request) {
        $response = GetterSetter::getPerhitunganBunga();
        return $response;
    });
    Route::post('getJumlahHari', function (Request $request) {
        $tgl = $request->Tgl;
        $tglCarbon = Carbon::parse($tgl)->format('d-m-Y');
        $response = Func::getJumlahHari($tglCarbon);
        return $response;
    });
    Route::post('getKewajibanPokok', function (Request $request) {
        $tgl = $request->Tgl;
        $rekening = $request->Rekening;
        $response = GetterSetter::getKewajibanPokok($rekening, $tgl);
        return $response;
    });
    Route::post('getAngsuranKe', function (Request $request) {
        $tgl = $request->Tgl;
        $rekening = $request->Rekening;
        $select = 'KBunga';
        $response = GetterSetter::getAngsuranKe($rekening, $tgl, $select);
        return $response;
    });
    Route::post(
        'getKasTeller',
        function (Request $request) {
            $username = $request->UserName;
            $tanggal = $request->Tanggal;
            $response = GetterSetter::getKasTeller($username, $tanggal);
            return $response;
        }
    );
    Route::post(
        'updRekeningMutasiTabungan',
        function (Request $request) {
            $faktur = $request->Faktur;
            $response = Upd::updRekeningMutasiTabungan($faktur);
            return $response;
        }
    );
    Route::post(
        'getRekeningLawan',
        function (Request $request) {
            $field = $request->Field;
            $table = $request->Table;
            $where = $request->Where;
            $response = Func::getRekeningLawan($field, $table, $where);
            return $response;
        }
    );
    Route::post(
        'getKet',
        function (Request $request) {
            $dTglRealisasi = $request->TglRealisasi;
            $dTgl = $request->Tgl;
            $nLama = $request->Lama;
            $response = GetterSetter::getKe($dTglRealisasi, $dTgl, $nLama);
            return $response;
        }
    );
    Route::post(
        'getPencairanDeposito',
        function (Request $request) {
            $response = PerhitunganDeposito::getPencairanDeposito('10020000275', '2023-08-02');
            return $response;
        }
    );
    Route::post(
        'getSaldoAwal',
        function (Request $request) {
            $response = GetterSetter::getSaldoAwal('2024-01-19', '1.400.11.02', '', false, '100', true, false, 'C');
            return $response;
        }
    );
    Route::post(
        'get_keterangan',
        function (Request $request) {
            $cKode = $request->Kode;
            $cField = $request->Field;
            $cTable = $request->Table;
            $response = GetterSetter::getKeterangan($cKode, $cField, $cTable);
            return $response;
        }
    );
    Route::post(
        'get_where_jenisgab',
        function (Request $request) {
            $cJenisGabungan = $request->JenisGabungan;
            $cCabang = $request->Cabang;
            $response = GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang);
            return $response;
        }
    );
    Route::post('pick_gabungan', function (Request $request) {
        $response = Func::pickGabungan($request);
        return $response;
    });

    Route::post('get_username', function (Request $request) {
        $response = Func::getUserName($request);
        return response()->json($response);
    });

    Route::post('get_email', function (Request $request) {
        $response = Func::getEmail($request);
        return response()->json($response);
    });

    Route::post('get_bakidebet', function (Request $request) {
        $cRek = $request->Rekening;
        $dTgl = $request->Tgl;
        $response = GetterSetter::getBakiDebet($cRek, $dTgl);
        return $response;
        return response()->json($response);
    });


    // ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ROUTE MASTER

    //------------------------------------------------------------------------------- ROUTE AKTIVA
    Route::post('aktiva/get', [AktivaController::class, 'data']);
    Route::post('aktiva/store', [AktivaController::class, 'store']);
    Route::post('aktiva/getdata_edit', [AktivaController::class, 'getDataEdit']);
    Route::post('aktiva/delete', [AktivaController::class, 'delete']);
    Route::post('aktiva/update/{KODE}', [AktivaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE COA
    Route::post('/rekening/get', [RekeningController::class, 'data']);
    Route::post('/rekening/get-all', [RekeningController::class, 'allData']);
    Route::post('/rekening/store',  [RekeningController::class, 'store']);
    Route::post('/rekening/delete', [RekeningController::class, 'delete']);
    Route::post('/rekening/update/{KODE}', [RekeningController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE UANG PECAHAN
    Route::post('uangpecahan/get', [UangPecahanController::class, 'data']);
    Route::post('uangpecahan/get/all', [UangPecahanController::class, 'all']);
    Route::post('uangpecahan/store', [UangPecahanController::class, 'store']);
    Route::post('uangpecahan/delete', [UangPecahanController::class, 'delete']);
    Route::post('uangpecahan/update/{KODE}', [UangPecahanController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE INSTANSI
    Route::post('instansi/get', [InstansiController::class, 'data']);
    Route::post('instansi/store', [InstansiController::class, 'store']);
    Route::post('instansi/delete', [InstansiController::class, 'delete']);
    Route::post('instansi/update/{KODE}', [InstansiController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE DAERAH
    Route::post('daerah/get', [DaerahController::class, 'data']);
    Route::post('daerah/store', [DaerahController::class, 'store']);
    Route::post('daerah/delete', [DaerahController::class, 'delete']);
    Route::post('daerah/update', [DaerahController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE JENIS IDENTITAS
    Route::post('jenisidentitas/get', [JenisIdentitasController::class, 'data']);
    Route::post('jenisidentitas/store', [JenisIdentitasController::class, 'store']);
    Route::post('jenisidentitas/delete', [JenisIdentitasController::class, 'delete']);
    Route::post('jenisidentitas/update/{KODE}', [JenisIdentitasController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE PASANGAN
    Route::post('pasangan/get', [PasanganController::class, 'data']);
    Route::post('pasangan/store', [PasanganController::class, 'store']);
    Route::post('pasangan/delete', [PasanganController::class, 'delete']);
    Route::post('pasangan/update/{KODE}', [PasanganController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE AGAMA
    Route::post('agama/get', [AgamaController::class, 'data']);
    Route::post('agama/store', [AgamaController::class, 'store']);
    Route::post('agama/delete', [AgamaController::class, 'delete']);
    Route::post('agama/update/{KODE}', [AgamaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE REGISTER NASABAH
    Route::post('registernasabah/get', [RegisterNasabahController::class, 'data']);
    Route::post('registernasabah/store', [RegisterNasabahController::class, 'store']);
    Route::post('registernasabah/delete', [RegisterNasabahController::class, 'delete']);
    Route::post('registernasabah/getdata_edit', [RegisterNasabahController::class, 'getDataEdit']);
    Route::post('registernasabah/get_faktur', [RegisterNasabahController::class, 'getFaktur']);
    Route::post('registernasabah/update/{KODE}', [RegisterNasabahController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE GOLONGAN AKTIVA
    Route::post('golonganaktiva/get', [GolonganAktivaController::class, 'data']);
    Route::post('golonganaktiva/store', [GolonganAktivaController::class, 'store']);
    Route::post('golonganaktiva/delete', [GolonganAktivaController::class, 'delete']);
    Route::post('golonganaktiva/update/{KODE}', [GolonganAktivaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE SURAT PERJANJIAN KREDIT
    Route::post('suratkredit/get', [SuratKreditController::class, 'data']);
    Route::post('suratkredit/store', [SuratKreditController::class, 'store']);
    Route::post('suratkredit/delete', [SuratKreditController::class, 'delete']);
    Route::post('suratkredit/update/{KODE}', [SuratKreditController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE KODE TRANSAKSI SIMPANAN
    Route::post('kodetransaksi/get', [KodeTransaksiController::class, 'data']);
    Route::post('kodetransaksi/get/kode', [KodeTransaksiController::class, 'getKodeTransaksi']);
    Route::post('kodetransaksi/store', [KodeTransaksiController::class, 'store']);
    Route::post('kodetransaksi/delete', [KodeTransaksiController::class, 'delete']);
    Route::post('kodetransaksi/update/{KODE}', [KodeTransaksiController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE PERUBAHAN SUKU BUNGA
    Route::post('perubahansukubungasimpanan/get', [PerubahanSukuBungaController::class, 'data']);
    Route::post('perubahansukubungasimpanan/store', [PerubahanSukuBungaController::class, 'store']);
    Route::post('perubahansukubungasimpanan/delete', [PerubahanSukuBungaController::class, 'delete']);
    Route::post('perubahansukubungasimpanan/update/{KODE}', [PerubahanSukuBungaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE GOLONGAN SIMPANAN
    Route::post('golongansimpanan/get', [GolonganSimpananController::class, 'data']);
    Route::post('golongansimpanan/store', [GolonganSimpananController::class, 'store']);
    Route::post('golongansimpanan/delete', [GolonganSimpananController::class, 'delete']);
    Route::post('golongansimpanan/update/{KODE}', [GolonganSimpananController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE GOLONGAN SIMPANAN BERJANGKA
    Route::post('golongansimpananberjangka/get', [GolonganSimpananBerjangkaController::class, 'data']);
    Route::post('golongansimpananberjangka/store', [GolonganSimpananBerjangkaController::class, 'store']);
    Route::post('golongansimpananberjangka/delete', [GolonganSimpananBerjangkaController::class, 'delete']);
    Route::post('golongansimpananberjangka/update/{KODE}', [GolonganSimpananBerjangkaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE BILYET SIMPANAN BERJANGKA
    Route::post('bilyetsimpananberjangka/get', [BilyetSimpananBerjangkaController::class, 'data']);
    Route::post('bilyetsimpananberjangka/store', [BilyetSimpananBerjangkaController::class, 'store']);
    Route::post('bilyetsimpananberjangka/delete', [BilyetSimpananBerjangkaController::class, 'delete']);
    Route::post('bilyetsimpananberjangka/update', [BilyetSimpananBerjangkaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE PROVISI DAN ADMINISTRASI
    Route::post('provisidanadministrasi/get', [ProvisiDanAdministrasiController::class, 'data']);
    Route::post('provisidanadministrasi/store', [ProvisiDanAdministrasiController::class, 'store']);
    Route::post('provisidanadministrasi/delete', [ProvisiDanAdministrasiController::class, 'delete']);
    Route::post('provisidanadministrasi/update/{KODE}', [ProvisiDanAdministrasiController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE JENIS PENGIKATAN JAMINAN
    Route::post('jenispengikatanjaminan/get', [JenisPengikatanJaminanController::class, 'data']);
    Route::post('jenispengikatanjaminan/store', [JenisPengikatanJaminanController::class, 'store']);
    Route::post('jenispengikatanjaminan/delete', [JenisPengikatanJaminanController::class, 'delete']);
    Route::post('jenispengikatanjaminan/update/{KODE}', [JenisPengikatanJaminanController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE AO
    Route::post('ao/get', [AoController::class, 'data']);
    Route::post('ao/store', [AoController::class, 'store']);
    Route::post('ao/delete', [AoController::class, 'delete']);
    Route::post('ao/update/{KODE}', [AoController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE GOLONGAN PINJAMAN
    Route::post('golonganpinjaman/get', [GolonganPinjamanController::class, 'data']);
    Route::post('golonganpinjaman/getGolongan', [GolonganPinjamanController::class, 'dataGolongan']);
    Route::post('golonganpinjaman/store', [GolonganPinjamanController::class, 'store']);
    Route::post('golonganpinjaman/delete', [GolonganPinjamanController::class, 'delete']);
    Route::post('golonganpinjaman/update/{KODE}', [GolonganPinjamanController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE SUKU BUNGA
    Route::post('sukubunga/get', [SukuBungaController::class, 'data']);
    Route::post('sukubunga/store', [SukuBungaController::class, 'store']);
    Route::post('sukubunga/delete', [SukuBungaController::class, 'delete']);
    Route::post('sukubunga/update/{KODE}', [SukuBungaController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE JAMINAN
    Route::post('jaminan/get', [JaminanController::class, 'data']);
    Route::post('jaminan/store', [JaminanController::class, 'store']);
    Route::post('jaminan/delete', [JaminanController::class, 'delete']);
    Route::post('jaminan/update/{KODE}', [JaminanController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE PEKERJAAN
    Route::post('pekerjaan/get', [PekerjaanController::class, 'data']);
    Route::post('pekerjaan/store', [PekerjaanController::class, 'store']);
    Route::post('pekerjaan/delete', [PekerjaanController::class, 'delete']);
    Route::post('pekerjaan/update/{KODE}', [PekerjaanController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE PICTURE
    Route::post('picture/store', [PictureController::class, 'store']);
    Route::post('picture/get', [PictureController::class, 'data']);
    Route::post('picture/get-teller', [PictureController::class, 'dataKasir']);
    Route::post('picture/delete', [PictureController::class, 'delete']);
    //--------------------------------------------------------------------------------ROUTE CABANG
    Route::post('cabang/get', [CabangController::class, 'data']);
    Route::post('cabang/store', [CabangController::class, 'store']);
    Route::post('cabang/update/{KODE}', [CabangController::class, 'update']);
    Route::post('cabang/delete', [CabangController::class, 'delete']);
    //------------------------------------------------------------------------------- ROUTE PICTURE
    Route::post('teller/seek', [TellerController::class, 'seekRekening']);
    //--------------------------------------------------------------------------------ROUTE KONFIGURASI SIMPANAN
    Route::post('konfigurasi/simpanan/tabungan', [KonfigurasiSimpananController::class, 'getDataTabungan']);
    Route::post('konfigurasi/simpanan/deposito', [KonfigurasiSimpananController::class, 'getDataDeposito']);
    Route::post('konfigurasi/simpanan/kredit', [KonfigurasiSimpananController::class, 'getDataKredit']);
    Route::post('konfigurasi/simpanan/lainnya', [KonfigurasiSimpananController::class, 'getDataLainnya']);
    Route::post('konfigurasi/simpanan/store', [KonfigurasiSimpananController::class, 'store']);
    //-------------------------------------------------------------------------------------------ROUTE NISBAH
    Route::post('nisbah/get', [NisbahController::class, 'data']);
    Route::post('nisbah/store', [NisbahController::class, 'store']);
    Route::post('nisbah/update/{KODE}', [NisbahController::class, 'update']);
    Route::post('nisbah/delete', [NisbahController::class, 'delete']);
    //-----------------------------------------------------------------------------------------ROUTE KORWIL
    Route::post('korwil/get', [KorwilController::class, 'data']);
    //-----------------------------------------------------------------------------------------ROUTE SISTEM
    Route::post('sistem/general', [SistemController::class, 'getDataGeneral']);
    Route::post('sistem/produk', [SistemController::class, 'getDataProduk']);
    Route::post('sistem/kantor', [SistemController::class, 'getDataAntarKantor']);
    Route::post('sistem/store', [SistemController::class, 'store']);
    //-------------------------------------------------------------------------------------------ROUTE GOLONGAN NASABAH
    Route::post('golnasabah/get', [GolonganNasabahController::class, 'data']);
    // Route::post('golnasabah/store', [GolonganNasabahController::class, 'store']);
    // Route::post('golnasabah/update/{KODE}', [GolonganNasabahController::class, 'update']);
    // Route::post('golnasabah/delete', [GolonganNasabahController::class, 'delete']);
    //-----------------------------------------------------------------------------------------ROUTE KAS TELLER
    Route::post('kasteller/get', [KasTellerController::class, 'data']);
    Route::post('kasteller/get/data', [KasTellerController::class, 'getDataUsername']);
    Route::post('kasteller/update', [KasTellerController::class, 'update']);
    //----------------------------------------------------------------------------------------ROUTE USERNAME
    Route::post('username/get', [UserNameController::class, 'data']);
    //----------------------------------------------------------------------------------------ROUTE KETERKAITAN
    Route::post('keterkaitan/get', [KeterkaitanController::class, 'data']);


    //▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ROUTE TRANSAKSI KAS

    //------------------------------------------------------------------------------- ROUTE Kas Masuk
    Route::post('kasmasuk/get', [KasMasukController::class, 'data']);
    Route::post('kasmasuk/store', [KasMasukController::class, 'store']);
    Route::post('kasmasuk/delete', [KasMasukController::class, 'delete']);
    Route::post('kasmasuk/update/{KODE}', [KasMasukController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE Kas Keluar
    Route::post('kaskeluar/get', [KasKeluarController::class, 'data']);
    Route::post('kaskeluar/store', [KasKeluarController::class, 'store']);
    Route::post('kaskeluar/delete', [KasKeluarController::class, 'delete']);
    Route::post('kaskeluar/update', [KasKeluarController::class, 'update']);
    //------------------------------------------------------------------------------- ROUTE KAS
    Route::post('kas/get', [KasController::class, 'data2']);
    Route::post('kas/store', [KasController::class, 'store']);
    Route::post('kas/delete', [KasController::class, 'delete']);
    Route::post('kas/update/{KODE}', [KasController::class, 'update']);
    Route::post('kas/get_faktur', [KasController::class, 'getFaktur']);
    Route::post('kas/getDataByFakturDebet', [KasController::class, 'getDataByFakturDebet']);
    Route::post('kas/getDataByFakturKredit', [KasController::class, 'getDataByFakturKredit']);
    //------------------------------------------------------------------------------- ROUTE Volt Teller Pagi
    Route::post('voltteller/get', [VoltTellerController::class, 'data']);
    Route::post('voltteller/dataJurnal', [VoltTellerController::class, 'dataJurnal']);
    Route::post('voltteller/getDataEdit', [VoltTellerController::class, 'getDataEdit']);
    Route::post('voltteller/getDataJurnalKasirByFaktur', [VoltTellerController::class, 'getDataJurnalKasirByFaktur']);
    Route::post('voltteller/store', [VoltTellerController::class, 'store']);
    Route::post('voltteller/storeVolt', [VoltTellerController::class, 'storeVolt']);
    Route::post('voltteller/storeJurnalUangPecahan', [VoltTellerController::class, 'storeJurnalUangPecahan']);
    Route::post('voltteller/delete', [VoltTellerController::class, 'delete']);
    Route::post('voltteller/update/{KODE}', [VoltTellerController::class, 'update']);
    Route::post('uangpecahan/dataJurnalUangPecahan', [UangPecahanController::class, 'dataJurnalUangPecahan']);
    //------------------------------------------------------------------------------- ROUTE Volt Teller Pagi

    //-------------------------------------------------------------------------------- ROUTE Simpanan
    // PEMBUKAAN REKENING SIMPANAN
    Route::post('simpanan/get', [PembukaanRekeningSimpananController::class, 'data']);
    Route::post('simpanan/get-cif', [PembukaanRekeningSimpananController::class, 'getDataCIF']);
    Route::post('simpanan/store', [PembukaanRekeningSimpananController::class, 'store']);
    Route::post('simpanan/getdata_edit', [PembukaanRekeningSimpananController::class, 'getDataEdit']);
    Route::post('simpanan/update', [PembukaanRekeningSimpananController::class, 'update']);
    Route::post('simpanan/delete', [PembukaanRekeningSimpananController::class, 'delete']);
    // TRANSFER ANTAR REKENING SIMPANAN
    Route::post('transfer_simpanan/get_rekening', [TransferAntarRekeningSimpananController::class, 'getRekening']);
    Route::post('transfer_simpanan/getrekening_tujuan', [TransferAntarRekeningSimpananController::class, 'getRekeningTujuan']);
    Route::post('transfer_simpanan/store', [TransferAntarRekeningSimpananController::class, 'store']);
    // TUTUP REKENING SIMPANAN
    Route::post('tutup_reksimpanan/get', [TutupRekeningSimpananController::class, 'data']);
    Route::post('tutup_reksimpanan/get_rekening', [TutupRekeningSimpananController::class, 'getRekening']);
    Route::post('tutup_reksimpanan/store', [TutupRekeningSimpananController::class, 'store']);
    // BLOKIR REKENING SIMPANAN
    Route::post('blokir_rekening/get', [BlokirRekeningSimpananController::class, 'data']);
    Route::post('blokir_rekening/get_rekening', [BlokirRekeningSimpananController::class, 'getRekening']);
    Route::post('blokir_rekening/store', [BlokirRekeningSimpananController::class, 'store']);
    // CETAK HEADER BUKU SIMPANAN
    Route::post('simpanan/get-rekening', [CetakHeaderSimpananController::class, 'getDataEdit']);
    // CETAK BUKU SIMPANAN
    Route::post('cetak-buku-simpanan/get-rekening', [CetakBukuSimpananController::class, 'getRekening']);
    Route::post('cetak-buku-simpanan/get-data', [CetakBukuSimpananController::class, 'data']);
    Route::post('cetak-buku-simpanan/upd', [CetakBukuSimpananController::class, 'updBarisCetak']);
    // ----------------------------------------------------------------------------------------------- ROUTE Simpanan Berjangka
    // REGISTER REKENING SIMPANAN BERJANGKA
    Route::post('simpanan_berjangka/get', [RegisterRekeningSimpananBerjangkaController::class, 'data']);
    Route::post('simpanan_berjangka/get/all', [RegisterRekeningSimpananBerjangkaController::class, 'allData']);
    Route::post('simpanan_berjangka/get_anggota', [RegisterRekeningSimpananBerjangkaController::class, 'getAnggota']);
    Route::post('simpanan_berjangka/delete', [RegisterRekeningSimpananBerjangkaController::class, 'delete']);
    Route::post('simpanan_berjangka/getdata_tabungan', [RegisterRekeningSimpananBerjangkaController::class, 'getRekeningTabungan']);
    Route::post('simpanan_berjangka/store', [RegisterRekeningSimpananBerjangkaController::class, 'store']);
    // BLOKIR REKENING SIMPANAN BERJANGKA
    Route::post('blokirsimpananberjangka/get', [BlokirSimpananBerjangkaController::class, 'data']);
    Route::post('blokirsimpananberjangka/get_rekening', [BlokirSimpananBerjangkaController::class, 'getRekening']);
    Route::post('blokirsimpananberjangka/store', [BlokirSimpananBerjangkaController::class, 'store']);
    // PERUBAHAN SUKU BUNGA
    Route::post('perubahansukubunga/get', [PerubahanSukuBungaSimpananBerjangkaController::class, 'data']);
    Route::post('perubahansukubunga/get-rekening', [PerubahanSukuBungaSimpananBerjangkaController::class, 'getRekening']);
    Route::post('perubahansukubunga/store', [PerubahanSukuBungaSimpananBerjangkaController::class, 'store']);
    // CETAK BILYET SIMPANAN BERJANGKA
    Route::post('cetak_bilyet/get-rekening', [CetakBilyetSimpananBerjangkaController::class, 'getRekening']);


    // ----------------------------------------------------------------------------------------------- ROUTE Pinjaman
    Route::post('pinjaman/get', [PinjamanController::class, 'data']);
    Route::post('pinjaman/get-anggota', [PinjamanController::class, 'getAnggota']);
    Route::post('pinjaman/dataRealisasi', [PinjamanController::class, 'dataRealisasi']);
    Route::post('pinjaman/dataAdendum', [PinjamanController::class, 'dataAdendum']);
    Route::post('pinjaman/saveAdendum', [PinjamanController::class, 'saveAdendum']);
    Route::post('pinjaman/getRekeningKredit', [PinjamanController::class, 'getRekeningKredit']);
    Route::post('pinjaman/getDataTabungan', [PinjamanController::class, 'getDataTabungan']);
    Route::post('pinjaman/UpdRealisasiKredit', [PinjamanController::class, 'UpdRealisasiKredit']);
    Route::post('pinjaman/UpdAngsuranPencairan', [PinjamanController::class, 'UpdAngsuranPencairan']);
    Route::post('pinjaman/getFaktur', [PinjamanController::class, 'getFaktur']);
    Route::post('pinjaman/get-data-cetak', [PinjamanController::class, 'getDataCetakLaporan']);
    // ----------------------------------------------------------------------------------------------- ROUTE Angsuran
    Route::post('agunan/get', [AgunanController::class, 'data']);
    Route::post('notaris/get', [NotarisController::class, 'data']);
    Route::post('asuransi/get', [AsuransiController::class, 'data']);
    Route::post('agunan/getAgunanByCIF', [AgunanController::class, 'getAgunanByCIF']);
    Route::post('angsuran/updateAngsuran', [AngsuranController::class, 'updateAngsuran']);
    Route::post('angsuran/get', [AngsuranController::class, 'data']);
    Route::post('angsuran/store', [AngsuranController::class, 'store']);
    Route::post('angsuran/detail', [AngsuranController::class, 'getAngsuranData']);
    Route::post('angsuran/cetak-validasi', [AngsuranController::class, 'cetakValidasi']);
    Route::post('angsuran/cetak-slip', [AngsuranController::class, 'cetakSlip']);
    Route::post('perhitungankredit/demo', [AngsuranController::class, 'SandBox']);
    // ----------------------------------------------------------------------------------------------- ROUTE Pemindahbukuan
    Route::post('pb/get', [PemindahbukuanController::class, 'data']);
    Route::post('pb/delete', [PemindahbukuanController::class, 'delete']);
    Route::post('pb/store', [PemindahbukuanController::class, 'store']);
    Route::post('pb/jurnal', [PemindahbukuanController::class, 'storeJurnal']);
    // Route::post('pb/jurnallain', [PemindahbukuanController::class, 'storeJurnalLainLain']);
    Route::post('pb/tabungan', [PemindahbukuanController::class, 'storeMutasiTabungan']);
    Route::post('pb/deposito', [PemindahbukuanController::class, 'storeMutasiDeposito']);
    // ----------------------------------------------------------------------------------------------- ROUTE Jurnal lain-lain
    Route::post('jurnal/get', [JurnalController::class, 'data']);
    Route::post('jurnal/globalreport', [JurnalController::class, 'jurnalUmum']);
    Route::post('jurnal/rekap/harian', [JurnalController::class, 'rekapJurnal']);
    Route::post('jurnal/bukubesardetail', [JurnalController::class, 'bukuBesarDetail']);
    Route::post('jurnal/bukubesartotal', [JurnalController::class, 'bukuBesarTotal']);
    // ----------------------------------------------------------------------------------------------- ROUTE Laporan Akuntansi
    Route::post('neraca/get', [NeracaController::class, 'data']);
    Route::post('labarugi/get', [LabaRugiController::class, 'data']);
    Route::post('checklist-accounting/get', [CheckListAccountingController::class, 'data']);
    // ----------------------------------------------------------------------------------------------- ROUTE Pembatalan Pencairan Pinjaman
    Route::post('pembatalan-pencairan-pinjaman/get-rekening', [PembatalanPencairanPinjamanController::class, 'getRekening']);
    Route::post('pembatalan-pencairan-pinjaman/store', [PembatalanPencairanPinjamanController::class, 'store']);
    // ----------------------------------------------------------------------------------------------- ROUTE Pengambilan Jaminan
    Route::post('pengambilan-jaminan/get-rekening', [PengambilanJaminanController::class, 'getRekening']);
    Route::post('pengambilan-jaminan/store', [PengambilanJaminanController::class, 'store']);
    //------------------------------------------------------------------------------------------------ ROUTE Perubahan Suku Bunga Pinjaman
    Route::post('perubahan-sukubunga-pinjaman/get', [PerubahanSukuBungaPinjamanController::class, 'data']);
    Route::post('perubahan-sukubunga-pinjaman/get-rekening', [PerubahanSukuBungaPinjamanController::class, 'getRekening']);
    Route::post('perubahan-sukubunga-pinjaman/store', [PerubahanSukuBungaPinjamanController::class, 'store']);
    //------------------------------------------------------------------------------------------------ ROUTE Koreksi Jadwal Angsuran
    Route::post('koreksi-jadwal-angsuran/get-rekening', [KoreksiJadwalAngsuranController::class, 'getRekening']);
    Route::post('koreksi-jadwal-angsuran/store', [KoreksiJadwalAngsuranController::class, 'store']);
    //------------------------------------------------------------------------------------------------ ROUTE Cetak Surat Pinjaman
    Route::post('cetak-surat/get-rekening', [CetakSuratPinjamanController::class, 'getRekening']);

    //------------------------------------------------------------------------------------------------ ROUTE Hapus Buku Pinjaman
    Route::post('hapus-buku-pinjaman/get-rekening', [HapusBukuPinjamanController::class, 'getRekening']);
    Route::post('hapus-buku-pinjaman/store', [HapusBukuPinjamanController::class, 'store']);
    //------------------------------------------------------------------------------------------------ ROUTE Pindah AO Pinjaman
    Route::post('pindah-ao/get-rekening', [PindahAOPinjamanController::class, 'getRekening']);
    Route::post('pindah-ao/store', [PindahAOPinjamanController::class, 'store']);
    // ----------------------------------------------------------------------------------------------- ROUTE Teller
    Route::post('teller/getTipeTransaksi', [TellerController::class, 'getTipeTransaksi']); //seekJaminanDetail
    Route::post('teller/seekSimpanan', [TellerController::class, 'seekSimpanan']);
    Route::post('teller/seekSimpananBerjangka', [TellerController::class, 'seekSimpananBerjangka']);
    Route::post('teller/seekPinjaman', [TellerController::class, 'seekPinjaman']);
    Route::post('teller/seekJaminan', [TellerController::class, 'seekJaminan']);
    Route::post('teller/seekJaminanDetail', [TellerController::class, 'seekJaminanDetail']);

    // TELLER MUTASI ANGGOTA
    Route::post('teller/mutasiAnggota/getDataAnggota', [MutasiAnggotaController::class, 'getDataAnggota']);
    Route::post('teller/mutasiAnggota/getDataTable', [MutasiAnggotaController::class, 'getDataTable']);
    Route::post('teller/mutasiAnggota/store', [MutasiAnggotaController::class, 'store']);
    Route::post('teller/mutasiAnggota/cetak-validasi', [MutasiAnggotaController::class, 'cetakValidasi']);
    // TELLER MUTASI SIMPANAN
    Route::post('teller/mutasiSimpanan/getRekTabungan', [MutasiSimpananController::class, 'getRekTabungan']);
    Route::post('teller/mutasiSimpanan/getDataSimpanan', [MutasiSimpananController::class, 'getDataSimpanan']);
    Route::post('teller/mutasiSimpanan/getMutasi', [MutasiSimpananController::class, 'getMutasi']);
    Route::post('teller/mutasiSimpanan/getDataTable', [MutasiSimpananController::class, 'getDataTable']);
    Route::post('teller/mutasiSimpanan/store', [MutasiSimpananController::class, 'store']);
    Route::post('teller/mutasiSimpanan/cetak-validasi', [MutasiSimpananController::class, 'cetakValidasi']);
    // TELLER MUTASI PEMBUKAAN DEPOSITO
    Route::post('teller/mutasiPembukaanDeposito/getDataDeposito', [MutasiPembukaanDepositoController::class, 'getDataDeposito']);
    Route::post('teller/mutasiPembukaanDeposito/store', [MutasiPembukaanDepositoController::class, 'store']);
    Route::post('teller/mutasiPembukaanDeposito/cetak-validasi', [MutasiPembukaanDepositoController::class, 'cetakValidasi']);
    // TELLER MUTASI PENCAIRAN DEPOSITO
    Route::post('teller/mutasiPencairanDeposito/getDataPencairanDeposito', [MutasiPencairanDepositoController::class, 'getDataPencairanDeposito']);
    Route::post('teller/mutasiPencairanDeposito/getDataTable', [MutasiPencairanDepositoController::class, 'getDataTable']);
    Route::post('teller/mutasi/pencairan-deposito/get-pokok', [MutasiPencairanDepositoController::class, 'getPokok']);
    Route::post('teller/mutasi/pencairan-deposito/get-bunga', [MutasiPencairanDepositoController::class, 'getBunga']);
    Route::post('teller/mutasiPencairanDeposito/store', [MutasiPencairanDepositoController::class, 'store']);

    //▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓PINJAMAN
    // PENGIKATAN JAMINAN
    Route::post('pengikatan-jaminan/get-urut', [PengikatanJaminanController::class, 'getUrutPengikatanJaminan']);
    Route::post('pengikatan-jaminan/get-nomor', [PengikatanJaminanController::class, 'getNomor']);
    Route::post('pengikatan-jaminan/store-temp', [PengikatanJaminanController::class, 'storeTemp']);
    Route::post('pengikatan-jaminan/store', [PengikatanJaminanController::class, 'store']);
    Route::post('pengikatan-jaminan/get-table', [PengikatanJaminanController::class, 'getDataJaminanTabel']);
    Route::post('pengikatan-jaminan/get-data-edit', [PengikatanJaminanController::class, 'getDataEdit']);
    Route::post('pengikatan-jaminan/update-data-table', [PengikatanJaminanController::class, 'update']);
    // TEMPORARY
    Route::post('pengikatan-jaminan/delete-tmp', [PengikatanJaminanController::class, 'deleteTmp']);
    // DELETE SEMUA
    Route::post('pengikatan-jaminan/get-table-delete', [PengikatanJaminanController::class, 'getTableDelete']);
    Route::post('pengikatan-jaminan/delete', [PengikatanJaminanController::class, 'delete']);

    //▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓LAPORAN
    // LAPORAN SIMPANAN
    Route::post('lap-nominatif-simpanan', [NominatifSimpananController::class, 'data']);
    Route::post('lap-bukusimpanan', [BukuSimpananController::class, 'data']);
    Route::post('lap-tutupsimpanan', [TutupSimpananController::class, 'data']);
    Route::post('lap-mutasiharian-simpanan', [MutasiSimpananHarianController::class, 'data']);
    Route::post('lap-blokirsimpanan', [BlokirSimpananController::class, 'data']);
    // LAPORAN SIMPANAN BERJANGKA
    Route::post('lap-daftarsaldo-deposito', [DaftarSaldoSimpananBerjangkaController::class, 'data']);
    Route::post('lap-kartumutasi-deposito-get', [KartuMutasiSimpananBerjangkaController::class, 'getRekening']);
    Route::post('lap-kartumutasi-deposito', [KartuMutasiSimpananBerjangkaController::class, 'data']);
    Route::post('lap-jadwalpencairan-deposito', [JadwalPencairanBungaSimpananBerjangkaController::class, 'data']);
    Route::post('lap-deposito-jatuhtempo', [SimpananBerjangkaJatuhTempoController::class, 'data']);
    // LAPORAN PINJAMAN
    Route::post('lap-register-jaminan', [RegisterJaminanController::class, 'data']);
    Route::post('lap-agunan-pengikatan', [DaftarAgunanDanPengikatanController::class, 'data']);
    Route::post('lap-angsuran-pinjaman', [AngsuranPinjamanController::class, 'data']);
    Route::post('lap-jadwalangsuran-getRekening', [JadwalAngsuranController::class, 'getRekening']);
    Route::post('lap-jadwalangsuran-data', [JadwalAngsuranController::class, 'data']);
    Route::post('lap-cetakangsuran-getRekening', [CetakKartuAngsuranController::class, 'getRekening']);
    Route::post('lap-cetakangsuran-data', [CetakKartuAngsuranController::class, 'data']);
    Route::post('lap-pinjamannominatif-get', [PinjamanNominatifController::class, 'getRekening']);
    Route::post('lap-pinjamannominatif-data', [PinjamanNominatifController::class, 'data']);
    Route::post('lap-pelunasan-pinjaman', [PelunasanPinjamanController::class, 'data']);
    Route::post('lap-pinjaman-jatuhtempo', [PinjamanJatuhTempoController::class, 'data']);
    Route::post('lap-pinjaman-hapusbuku', [PinjamanHapusBukuController::class, 'data']);
    // LAPORAN MUTASI NON KAS
    Route::post('lap-mutasi-nonkas', [MutasiNonKasController::class, 'data']);
    // LAPORAN SISA HASIL USAHA
    Route::post('lap-shu/data', [SisaHasilUsahaController::class, 'data']);
    Route::post('lap-shu/preview', [SisaHasilUsahaController::class, 'preview']);
    Route::post('lap-shu/posting', [SisaHasilUsahaController::class, 'posting']);

    // PROSES AWAL HARI
    Route::post('tampil_tgl', [ProsesAwalHariController::class, 'tampilTgl']);
    Route::post('cek_status_tgl', [ProsesAwalHariController::class, 'cekStatusTgl']);
    Route::post('cek_tgl', [ProsesAwalHariController::class, 'cekTglTransaksi']);
    Route::post('save_tgl_transaksi', [ProsesAwalHariController::class, 'saveTglTransaksi']);
    Route::post('upd_tgl_transaksi', [ProsesAwalHariController::class, 'updTglTransaksi']);

    // LAPORAN KOLEKTIBILITAS
    Route::post('kolektibilitas/get', [KolektibilitasController::class, 'data']);

    // LAPORAN KOLEKTIBILITAS
    Route::post('daftartagihan/get', [DaftarTagihanController::class, 'data']);

    // KONVERSI
    // Konversi Data
    Route::post('konversi/data/register/store', [KonversiDataController::class, 'storeRegNas']);
    Route::post('konversi/data/simpanan/store', [KonversiDataController::class, 'storeSimpanan']);
    Route::post('konversi/data/simpanan-berjangka/store', [KonversiDataController::class, 'storeSimpananBerjangka']);
    Route::post('konversi/data/pinjaman/store', [KonversiDataController::class, 'storePinjaman']);
    // Konversi Mutasi
    Route::post('konversi/mutasi/register/store', [KonversiMutasiController::class, 'storeMutasiAnggota']);
    Route::post('konversi/mutasi/simpanan/store', [KonversiMutasiController::class, 'storeMutasiSimpanan']);
    Route::post('konversi/mutasi/simpanan-berjangka/store', [KonversiMutasiController::class, 'storeMutasiSimpananBerjangka']);
    Route::post('konversi/mutasi/pinjaman/store', [KonversiMutasiController::class, 'storeMutasiPinjaman']);
    // Konversi Aktiva
    Route::post('konversi/aktiva/store', [KonversiAktivaController::class, 'store']);
    // Konversi Neraca
    Route::post('konversi/neraca/store', [KonversiNeracaController::class, 'store']);
    //Posting
    Route::post('posting/posting-anggota', [PostingController::class, 'postingMutasiAnggota']);
    Route::post('posting/posting-simpanan', [PostingController::class, 'postingMutasiSimpanan']);
    Route::post('posting/posting-simpanan-berjangka', [PostingController::class, 'postingMutasiSimpananBerjangka']);
    //Posting Jurnal
    Route::post('posting/jurnal', [Posting2Controller::class, 'postingJurnal']);
    //Posting Kredit
    Route::post('posting_double', [PostingPinjamanController::class, 'PostingDataKredit']);
    // Posting Bunga Tabungan
    Route::post('posting/bunga', [PostingBungaController::class, 'prosesPostingTabungan']);
    // Posting Aktiva
    Route::post('posting/aktiva/data', [PostingAktivaController::class, 'data']);
    Route::post('posting/aktiva', [PostingAktivaController::class, 'posting']);
    // Get Data Bunga Deposito
    Route::post('posting/bunga/deposito/data', [PostingBungaController::class, 'getDataPostingBungaDeposito']);
    // Posting Bunga Deposito
    Route::post('posting/bunga/deposito', [PostingBungaController::class, 'prosesBungaDeposito']);
    // Laporan Penyusutan Aktiva
    Route::post('aktiva/lap/penyusutan', [LaporanPenyusutanAktivaController::class, 'data']);

    // TKS
    // Posting dan Setting
    Route::post('tks/posting', [PostingSettingController::class, 'postingTKS']);
    Route::post('tks/data', [PostingSettingController::class, 'data']);
    Route::post('tks/get/data', [PostingSettingController::class, 'getData']);
    Route::post('tks/update/data', [PostingSettingController::class, 'update']);
    Route::post('tks/delete/data', [PostingSettingController::class, 'delete']);
    // Laporan Analisis
    Route::post('tks/laporan-analisis', [LaporanAnalisisController::class, 'data']);
    // Struktur Keuangan yang Efektif
    Route::post('tks/struktur-keuangan/piutang-bersih', [PiutangBersihController::class, 'data']);
    Route::post('tks/struktur-keuangan/investasi-likuid', [InvestasiLikuidController::class, 'data']);
    Route::post('tks/struktur-keuangan/investasi-keuangan', [InvestasiKeuanganController::class, 'data']);
    Route::post('tks/struktur-keuangan/investasi-non-keuangan', [InvestasiNonKeuanganController::class, 'data']);
    Route::post('tks/struktur-keuangan/simpanan-non-saham', [SimpananNonSahamController::class, 'data']);
    Route::post('tks/struktur-keuangan/pinjaman-dari-bk3d', [PinjamanDariBK3DController::class, 'data']);
    Route::post('tks/struktur-keuangan/modal-saham-anggota', [ModalSahamAnggotaController::class, 'data']);
    Route::post('tks/struktur-keuangan/modal-lembaga', [ModalLembagaController::class, 'data']);
    // Kualitas Aset
    Route::post('tks/kualitas-aset/aset-tidak-menghasilkan', [TotalAsetYangTidakMenghasilkanController::class, 'data']);
    // Tingkat Pendapatan dan Biaya
    Route::post('tks/tingkat-pendapatan-biaya/total-biaya-operasional', [TotalBiayaOperasionalController::class, 'data']);
    Route::post('tks/tingkat-pendapatan-biaya/total-biaya-provisi', [TotalBiayaProvisiPinjamanLalaiController::class, 'data']);
    Route::post('tks/tingkat-pendapatan-biaya/pendapatan-biaya-lain', [PendapatanBiayaLainLainController::class, 'data']);
    Route::post('tks/tingkat-pendapatan-biaya/laba-bersih', [LabaBersihController::class, 'data']);
    // Likuiditas
    Route::post('tks/likuiditas/cadangan-likuiditas', [CadanganLikuiditasController::class, 'data']);
    // Tanda-Tanda Pertumbuhan
    Route::post('tks/tanda-pertumbuhan/pertumbuhan-simpanan-non-saham', [PertumbuhanSimpananNonSahamController::class, 'data']);
    Route::post('tks/tanda-pertumbuhan/pertumbuhan-simpanan-saham', [PertumbuhanSimpananSahamController::class, 'data']);
    Route::post('tks/tanda-pertumbuhan/pertumbuhan-modal-lembaga', [PertumbuhanModalLembagaController::class, 'data']);
    Route::post('tks/tanda-pertumbuhan/pertumbuhan-anggota', [PertumbuhanAnggotaController::class, 'data']);
    Route::post('tks/tanda-pertumbuhan/pertumbuhan-aset', [PertumbuhanAsetController::class, 'data']);
    // Setting ATMR
    Route::post('atmr/setting/data', [SettingATMRController::class, 'data']);
    Route::post('atmr/setting/store', [SettingATMRController::class, 'store']);
    Route::post('atmr/setting/update', [SettingATMRController::class, 'update']);
    Route::post('atmr/setting/delete', [SettingATMRController::class, 'delete']);
    // Laporan ATMR
    Route::post('atmr/laporan', [ATMRController::class, 'data']);
});

Route::middleware(['upload.token', 'check.token', 'change.database'])->group(function () {
    // Function
    // Route::post('get_rekening', function (Request $request) {
    //     $KEY = $request->KEY;
    //     $LEN = $request->LEN;
    //     $response = GetterSetter::getRekening($KEY, $LEN);
    //     return response()->json($response);
    // });
    // Konversi Data
    Route::post('konversi/data/get-kode', [KonversiDataController::class, 'getGenerateKode']);
    Route::post('konversi/data/get-tab', [KonversiDataController::class, 'getGenerateRekTab']);
    Route::post('konversi/data/get-depo', [KonversiDataController::class, 'getGenerateRekDepo']);
    Route::post('konversi/data/get-kredit', [KonversiDataController::class, 'getGenerateRekKredit']);
    // Konversi Mutasi
    Route::post('konversi/mutasi/get-kode', [KonversiMutasiController::class, 'getKodeAnggotaBaru']);
    Route::post('konversi/mutasi/get-tab', [KonversiMutasiController::class, 'getRekTabunganBaru']);
    Route::post('konversi/mutasi/get-depo', [KonversiMutasiController::class, 'getRekDepositoBaru']);
    Route::post('konversi/mutasi/get-kredit', [KonversiMutasiController::class, 'getRekKreditBaru']);
});
