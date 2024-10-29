<?php

namespace App\Http\Controllers\api\tkskoperasi;

use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTKSKoperasi;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ATMRController extends Controller
{
    public function data1(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dYear = $vaRequestData['Periode'];
            $dTglAwal = Carbon::createFromDate($dYear, 12, 31);
            $dTglAwalFormatter = $dTglAwal->format('Y-m-d');
            $dTglAkhir = Carbon::createFromDate($dYear, 12, 31);
            $dTglAkhirFormatter = $dTglAkhir->format('Y-m-d');
            $vaArray = [
                [
                    'Komponen' => 'Simpanan Pokok',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananPokokATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSimpananPokokATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananPokokATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSimpananPokokATMR')) / 100
                ],
                [
                    'Komponen' => 'Simpanan Wajib',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananWajibATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSimpananWajibATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananWajibATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSimpananWajibATMR')) / 100
                ],
                [
                    'Komponen' => 'Modal Penyetaraan',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msModalPenyetaraanATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotModalPenyetaraanATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msModalPenyetaraanATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotModalPenyetaraanATMR')) / 100
                ],
                [
                    'Komponen' => 'Modal Sumbangan / Hibah',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msModalSumbanganHibahATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotModalSumbanganHibahATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msModalSumbanganHibahATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotModalSumbanganHibahATMR')) / 100
                ],
                [
                    'Komponen' => 'Cadangan Umum',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msCadanganUmumATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotCadanganUmumATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msCadanganUmumATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotCadanganUmumATMR')) / 100
                ],
                [
                    'Komponen' => 'Cadangan Tujuan Resiko (Penyisihan Piutang Tak Tertagih)',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msCadanganTujuanResikoATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotCadanganTujuanResikoATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msCadanganTujuanResikoATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotCadanganTujuanResikoATMR')) / 100
                ],
                [
                    'Komponen' => 'Jumlah SHU Belum Dibagi',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msJumlahSHUBelumDibagiATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotJumlahSHUBelumDibagiATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msJumlahSHUBelumDibagiATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotJumlahSHUBelumDibagiATMR')) / 100
                ],
                [
                    'Komponen' => 'Kas',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msKasATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotKasATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msKasATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotKasATMR')) / 100
                ],
                [
                    'Komponen' => 'Bank',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msBankATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotBankATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msBankATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotBankATMR')) / 100
                ],
                [
                    'Komponen' => 'Simpanan Berjangka (Deposito)',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananBerjangkaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSimpananBerjangkaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananBerjangkaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSimpananBerjangkaATMR')) / 100
                ],
                [
                    'Komponen' => 'Simpanan Sukarela pada Koperasi Lain',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananSukarelaKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSimpananSukarelaKopLainATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananSukarelaKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSimpananSukarelaKopLainATMR')) / 100
                ],
                [
                    'Komponen' => 'Simpanan Berjangka pada Koperasi Lain',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananBerjangkaKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSimpananBerjangkaKopLainATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananBerjangkaKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSimpananBerjangkaKopLainATMR')) / 100
                ],
                [
                    'Komponen' => 'Surat Berharga',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSuratBerhargaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotSuratBerhargaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSuratBerhargaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotSuratBerhargaATMR')) / 100
                ],
                [
                    'Komponen' => 'Piutang Pinjaman Anggota',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanAnggotaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPiutangPinjamanAnggotaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanAnggotaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPiutangPinjamanAnggotaATMR')) / 100
                ],
                [
                    'Komponen' => 'Piutang Pinjaman Non Anggota / Calon ANggota',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanNonAnggotaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPiutangPinjamanNonAnggotaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanNonAnggotaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPiutangPinjamanNonAnggotaATMR')) / 100
                ],
                [
                    'Komponen' => 'Piutang Pinjaman pada Koperasi Lain',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPiutangPinjamanKopLainATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPiutangPinjamanKopLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPiutangPinjamanKopLainATMR')) / 100
                ],
                [
                    'Komponen' => 'Penyisihan Piutang Tak Tertagih',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPenyisihanPiutangTakTertagihATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPenyisihanPiutangTakTertagihATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPenyisihanPiutangTakTertagihATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPenyisihanPiutangTakTertagihATMR')) / 100
                ],
                [
                    'Komponen' => 'Beban Dibayar Dimuka',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msBebanDibayarDimukaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotBebanDibayarDimukaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msBebanDibayarDimukaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotBebanDibayarDimukaATMR')) / 100
                ],
                [
                    'Komponen' => 'Pendapatan Akan Diterima',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPendapatanAkanDiterimaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPendapatanAkanDiterimaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPendapatanAkanDiterimaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPendapatanAkanDiterimaATMR')) / 100
                ],
                [
                    'Komponen' => 'Aktiva Lancar Lainnya',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAktivaLancarLainnyaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotAktivaLancarLainnyaATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAktivaLancarLainnyaATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotAktivaLancarLainnyaATMR')) / 100
                ],
                [
                    'Komponen' => 'Penyertaan Pada Koperasi Sekundair / Lainnya',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPenyertaanKopSekunderATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotPenyertaanKopSekunderATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msPenyertaanKopSekunderATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotPenyertaanKopSekunderATMR')) / 100
                ],
                [
                    'Komponen' => 'Investasi Saham / Obligasi Jangka Panjang',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msInvestasiSahamATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotInvestasiSahamATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msInvestasiSahamATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotInvestasiSahamATMR')) / 100
                ],
                [
                    'Komponen' => 'Investasi Jangka Panjang Lain',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msInvestasiJangkaPanjangLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotInvestasiJangkaPanjangLainATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msSimpananPokokATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotInvestasiJangkaPanjangLainATMR')) / 100
                ],
                [
                    'Komponen' => 'Harta Tetap',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msHartaTetapATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotHartaTetapATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msHartaTetapATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotHartaTetapATMR')) / 100
                ],
                [
                    'Komponen' => 'Akumulasi Penyusutan Harta Tetap',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAkumulasiPenyusutanHartaTetapATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotAkumulasiPenyusutanHartaTetapATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAkumulasiPenyusutanHartaTetapATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotAkumulasiPenyusutanHartaTetapATMR')) / 100
                ],
                [
                    'Komponen' => 'Aktiva Lain Lain',
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAktivaLainLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => GetterSetter::getDBConfig('msBobotAktivaLainLainATMR') . "%",
                    'Modal' => intval(PerhitunganTKSKoperasi::getNilaiCOA(GetterSetter::getDBConfig("msAktivaLainLainATMR"), $dTglAwalFormatter, $dTglAkhirFormatter)) * intval(GetterSetter::getDBConfig('msBobotAktivaLainLainATMR')) / 100
                ],
            ];
            return $vaArray;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function data(Request $request)
    {
        try {
            // Mengubah request JSON ke array PHP
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);

            // Ambil nama user dari 'auth'
            $cUser = $vaRequestData['auth']['name'];

            // Hapus data 'auth' dan 'page' dari request
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);

            // Mendapatkan tahun dari data request
            $dYear = $vaRequestData['Periode'];

            // Membuat tanggal awal dan akhir (31 Desember)
            $dTglAwal = Carbon::createFromDate($dYear, 12, 31);
            $dTglAwalFormatter = $dTglAwal->format('Y-m-d');
            $dTglAkhir = Carbon::createFromDate($dYear, 12, 31);
            $dTglAkhirFormatter = $dTglAkhir->format('Y-m-d');

            // Mengambil data dari tabel 'tks_atmr'
            $vaData = DB::table('tks_atmr')
                ->select('Kode', 'Keterangan', 'Persen', 'Rekening')
                ->orderBy('Urut', 'asc')
                ->get();

            // Membuat array untuk menampung hasil
            $nTotalJumlahModalTertimbang = 0;
            $vaResult = [];

            // Looping untuk memproses setiap data
            foreach ($vaData as $data) {
                $cRek = $data->Rekening;
                $nPersen = intval($data->Persen) / 100;
                if (substr($cRek, 0, 1) === '3') {
                    $nTotalJumlahModalTertimbang += PerhitunganTKSKoperasi::getNilaiCOA($cRek, $dTglAwalFormatter, $dTglAkhirFormatter);
                }

                // Menambahkan hasil per item ke dalam array
                $vaResult[] = [
                    'Komponen' => $data->Keterangan,
                    'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA($cRek, $dTglAwalFormatter, $dTglAkhirFormatter),
                    'Bobot' => $data->Persen . "%",
                    'Modal' => PerhitunganTKSKoperasi::getNilaiCOA($cRek, $dTglAwalFormatter, $dTglAkhirFormatter) * $nPersen
                ];
            }
            // $recordEndStartsWith3 = [
            //    'Komponen' => $data->Keterangan,
            //         'Nilai' => PerhitunganTKSKoperasi::getNilaiCOA($cRek, $dTglAwalFormatter, $dTglAkhirFormatter),
            //         'Bobot' => $data->Persen . "%",
            //         'Modal' => PerhitunganTKSKoperasi::getNilaiCOA($cRek, $dTglAwalFormatter, $dTglAkhirFormatter) * $nPersen
            // ];
            // array_push($arrayStartsWith1, $recordEndStartsWith3);
            // Mengembalikan array hasil
            return $vaResult;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
