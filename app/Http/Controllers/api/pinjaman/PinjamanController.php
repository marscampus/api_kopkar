<?php

namespace App\Http\Controllers\api\pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\RegisterNasabah;
use App\Models\pinjaman\Pinjaman;
use App\Models\simpanan\Tabungan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use Psy\CodeCleaner\ReturnTypePass;
use Illuminate\Support\Facades\DB;

class PinjamanController extends Controller
{
    function data(Request $request)
    {
        try {
            $limit = 999999999999;
            $tglAwal = $request->TglAwal;
            $tglAkhir = $request->TglAkhir;
            $filters = $request->filters;

            $Pinjaman = Pinjaman::select('debitur.Rekening', 'debitur.Kode', 'debitur.Tgl', 'debitur.StatusPencairan', 'registernasabah.Nama', 'registernasabah.Alamat')
                ->leftJoin('registernasabah', 'debitur.Kode', '=', 'registernasabah.Kode')
                ->whereBetween('debitur.Tgl', [$tglAwal, $tglAkhir])
                ->orderByDesc('debitur.Tgl');

            // Apply filters if provided
            if (null !== ($filters)) {
                foreach ($filters as $k => $v) {
                    $Pinjaman->where(function ($query) use ($k, $v) {
                        $query->where('debitur.' . $k, 'LIKE', '%' . $v . '%')
                            ->orWhere('registernasabah.' . $k, 'LIKE', '%' . $v . '%');
                    });
                }
            }
            // if ($request->page == null) {
            //     $Pinjaman = $Pinjaman->get();
            // } else {
                $Pinjaman = $Pinjaman->paginate($limit);
            // }

            return response()->json($Pinjaman);
        } catch (\Throwable $th) {
            return $th;
            return response()->json(['status' => 'error']);
        }
    }

    public function getAnggota(Request $request)
    {
        $kode = $request->Kode;
        $queryRegAnggota =
            DB::table('registernasabah as r')
            ->leftJoin('pekerjaan as p', 'p.Kode', '=', 'r.Pekerjaan')
            ->leftJoin('keterkaitan as k', 'k.Kode', '=', 'r.keterkaitan')
            ->where('r.Kode', '=', $kode)
            ->select(
                'r.Nama',
                'r.Alamat',
                'r.Telepon',
                'r.Pekerjaan',
                'p.Keterangan as NamaPekerjaan',
                'r.Keterkaitan',
                'k.Keterangan as NamaKeterkaitan',
                'r.KTP',
                'r.TempatLahir',
                'r.TglLahir',
                'r.KodePos',
                'r.KodyaKeterangan',
                'r.Agama'
            )
            ->first();
        if ($queryRegAnggota) {
            $cabang = '101'; // GET CONFIG
            $produk = "2";
            $urutRekDeposito = GetterSetter::getRekening('', 2, $cabang);
            $rekening = $cabang . $produk . $urutRekDeposito;
            $nama = $queryRegAnggota->Nama;
            $alamat = $queryRegAnggota->Alamat;
            $telepon = $queryRegAnggota->Telepon;
            $keterikatan = "2";
            $next = "1";
            $result = [
                'Kode' => $kode,
                'Nama' => $queryRegAnggota->Nama ? $queryRegAnggota->Nama : '',
                'Alamat' => $queryRegAnggota->Alamat ? $queryRegAnggota->Alamat : '',
                'KTP' => $queryRegAnggota->KTP ? $queryRegAnggota->KTP : '',
                'TempatLahir' => $queryRegAnggota->TempatLahir ? $queryRegAnggota->TempatLahir : '',
                'TglLahir' => $queryRegAnggota->TglLahir ? $queryRegAnggota->TglLahir : '',
                'KodePos' => $queryRegAnggota->KodePos ? $queryRegAnggota->KodePos : '',
                'KodyaKeterangan' => $queryRegAnggota->KodyaKeterangan ? $queryRegAnggota->KodyaKeterangan : '',
                'Agama' => $queryRegAnggota->Agama ? $queryRegAnggota->Agama : '',
                'Tanggal' => GetterSetter::getTglTransaksi(),
                'Telepon' => $queryRegAnggota->Telepon ? $queryRegAnggota->Telepon : '',
                'Pekerjaan' => $queryRegAnggota->Pekerjaan ? $queryRegAnggota->Pekerjaan : '',
                'NamaPekerjaan' => $queryRegAnggota->NamaPekerjaan ? $queryRegAnggota->NamaPekerjaan : '',
                'Keterikatan' => $keterikatan,
                'Next' => $next
            ];
            return response()->json($result);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'No. Anggota Tidak Valid!']
            );
        }
    }

    // function dataRealisasi(Request $request)
    // {
    //     $Rekening = $request->Rekening;
    //     $Pinjaman = Pinjaman::select('debitur.Rekening', 'debitur.*', 'debitur.Kode', 'debitur.Tgl', 'debitur.StatusPencairan', 'registernasabah.Nama', 'registernasabah.Alamat')
    //         ->leftJoin('registernasabah', 'debitur.Kode', '=', 'registernasabah.Kode')
    //         ->where('debitur.Rekening', "=", $Rekening)->first();
    //     // $Pinjaman = $Pinjaman->paginate(10);

    //     $resp = GetterSetter::getTunggakan($Pinjaman->Rekening,$Pinjaman->Tgl);
    //     // return response()->json($resp);
    //     return 0;
    //     // return response()->json($Pinjaman);
    // }

    function dataAdendum(Request $request)
    {
        $Rekening = $request->Rekening;
        $Pinjaman = Pinjaman::select('debitur.Rekening', 'debitur.*', 'debitur.Kode', 'debitur.Tgl', 'debitur.StatusPencairan', 'registernasabah.Nama', 'registernasabah.Alamat')
            ->leftJoin('registernasabah', 'debitur.Kode', '=', 'registernasabah.Kode')
            ->where('debitur.Rekening', "=", $Rekening)->first();
        // $Pinjaman = $Pinjaman->paginate(10);
        return response()->json($Pinjaman);
    }

    function getRekeningKredit(Request $request, Connection $connection)
    {
        $cabang = $request->input('cabang');
        if (null !== ($cabang)) {
            try {
                // Gunakan connection untuk mengeksekusi query kustom
                $results = $connection->select("select (right(max(rekening), 7)) as lastrekening from debitur where rekening like '" . $cabang . "%' order by lastrekening desc limit 1");

                // Ambil angka dari hasil query
                $lastRekening = $results[0]->lastrekening;

                // Tambahkan 1 ke angka dan format ulang
                $newRekening = sprintf('%07d', intval($lastRekening) + 1);
                $newRekening = $cabang . "3" . $newRekening;

                return response()->json(['rekening' => $newRekening]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Query execution failed']);
            }
        }
    }

    function getDataTabungan(Request $request)
    {
        $Kode = $request->Kode;
        $query = DB::table('tabungan as t')
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
            ->select('r.Nama', 'r.Alamat')
            ->where('Rekening', $Kode)
            ->first();
        if ($query) {
            if ($query->Close == '1') {
                return response()->json(
                    ['status' => 'error', 'message' => 'Tabungan Sudah Ditutup!']
                );
            }
            $result = [
                'Nama' => $query->Nama,
                'Alamat' => $query->Alamat
            ];
            return response()->json($result);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'No. Rekening Tabungan Tidak Valid!']
            );
        }
    }

    function delete(Request $request)
    {
        try {
            $Pinjaman = Pinjaman::findOrFail($request->Rekening);
            $Pinjaman->delete();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function UpdRealisasiKredit(Request $request)
    {
        $cKode = $request->Kode;
        $cFaktur = $request->Faktur;
        $cRekening = $request->Rekening;
        $cRekeningLama = $request->RekeningLama;
        $dTgl = $request->Tgl;
        $cNoSPK = $request->NoSPK;
        $cGolonganKredit = $request->GolonganKredit;
        $cSifatKredit = $request->SifatKredit;
        $cJenisPenggunaan = $request->JenisPenggunaan;
        $cGolonganDebitur = $request->GolonganDebitur;
        $cSektorEkonomi = $request->Sektorekonomi;
        $cWilayah = $request->Wilayah;
        $cAO = $request->AO;
        $cGolonganPenjamin = $request->GolonganPenjamin;
        $cBagianYangDijamin = $request->BagianYangDijamin;
        $cCaraPerhitungan = $request->CaraPerhitungan;
        $cRekeningTabungan = $request->RekeningTabungan;
        $nPlafond = $request->Plafond;
        $nSukuBunga = $request->SukuBunga;
        $nLama = $request->Lama;
        $nMusiman = $request->Musiman;
        $nGracePeriod = $request->GracePeriod;
        $nGracePeriodPokokAwal = $request->GracePeriodPokokAwal;
        $nGracePeriodBungaAwal = $request->GracePeriodBungaAwal;
        $nAdministrasi = $request->Administrasi;
        $nNotaris = $request->Notaris;
        $nMaterai = $request->Materai;
        $nAsuransi = $request->Asuransi;
        $nProvisi = $request->Provisi;
        $PersenProvisi = $request->PersenProvisi;
        $cInstansi = $request->Instansi;
        $cNoPengajuan = $request->NoPengajuan;
        $cCaraPencairan = $request->CaraPencairan;
        $nBiayaTaksasi = $request->BiayaTaksasi;
        $cCassie = $request->Cassie;
        $cKodeNotaris = $request->KodeNotaris;
        $cKodeAsuransi = $request->KodeAsuransi;
        $cKolektor = $request->JuruBayar;
        $nAngsuran1 = $request->Angsuran1;
        $cSektorEkonomiOJK = $request->SektorEkonomiOJK;
        $cUserName = $request->UserName;
        $cCabangEntry = $request->CabangEntry;
        $nBiayaLainnya = $request->BiayaLainnya;
        $nBiayaTransaksi = $request->BiayaTransaksi;
        $nOtorisasi = $request->StatusOtorisasi;
        $cKeterkaitan = $request->Keterkaitan;
        $cPeriodePembayaran = $request->PeriodePembayaran;
        $cSumberDanaPelunasan = $request->SumberDanaPelunasan;
        $cTujuanPenggunaan = $request->TujuanPenggunaan;
        $cWilayahAo = $request->WilayahAo;
        $nPinjamanKe = $request->pinjamanke;
        $dTglPermohonan = $request->TglPermohonan;
        $cRekeningSebelumnya = $request->RekeningSebelumnya;
        $nBakiDebetSebelumnya = $request->BakiDebetSebelumnya;
        $nTBungaSebelumnya = $request->TBungaSebelumnya;
        $cNoPK = $request->NoPK;
        $cNoSPPK = $request->NoSPPK;
        $dTglPersetujuan = $request->TglPersetujuan;
        $cKategoriDebitur = $request->KategoriDebitur;
        $cRekeningJaminan = $request->RekeningJaminan;
        $cStatusPencairan = $request->StatusPencairan;

        $updateData = [];

        if (null !== ($cFaktur)) $updateData['Faktur'] = $cFaktur;
        if (null !== ($cRekening)) $updateData['Rekening'] = $cRekening;
        if (null !== ($dTgl)) $updateData['Tgl'] = $dTgl;
        if (null !== ($cNoSPK)) $updateData['NoSPK'] = $cNoSPK;
        if (null !== ($cGolonganKredit)) $updateData['GolonganKredit'] = $cGolonganKredit;
        if (null !== ($cSifatKredit)) $updateData['SifatKredit'] = $cSifatKredit;
        if (null !== ($cJenisPenggunaan)) $updateData['JenisPenggunaan'] = $cJenisPenggunaan;
        if (null !== ($cGolonganDebitur)) $updateData['GolonganDebitur'] = $cGolonganDebitur;
        if (null !== ($cSektorEkonomi)) $updateData['SektorEkonomi'] = $cSektorEkonomi;
        if (null !== ($cWilayah)) $updateData['Wilayah'] = $cWilayah;
        if (null !== ($cAO)) $updateData['AO'] = $cAO;
        if (null !== ($cGolonganPenjamin)) $updateData['GolonganPenjamin'] = $cGolonganPenjamin;
        if (null !== ($cBagianYangDijamin)) $updateData['BagianYangDijamin'] = $cBagianYangDijamin;
        if (null !== ($cCaraPerhitungan)) $updateData['CaraPerhitungan'] = $cCaraPerhitungan;
        if (null !== ($cRekeningTabungan)) $updateData['RekeningTabungan'] = $cRekeningTabungan;
        if (null !== ($nPlafond)) $updateData['Plafond'] = $nPlafond;
        if (null !== ($nSukuBunga)) $updateData['SukuBunga'] = $nSukuBunga;
        if (null !== ($nLama)) $updateData['Lama'] = $nLama;
        if (null !== ($nMusiman)) $updateData['Musiman'] = $nMusiman;
        if (null !== ($nGracePeriod)) $updateData['GracePeriod'] = $nGracePeriod;
        if (null !== ($nGracePeriodPokokAwal)) $updateData['GracePeriodPokokAwal'] = $nGracePeriodPokokAwal;
        if (null !== ($nGracePeriodBungaAwal)) $updateData['GracePeriodBungaAwal'] = $nGracePeriodBungaAwal;
        if (null !== ($nAdministrasi)) $updateData['Administrasi'] = $nAdministrasi;
        if (null !== ($nNotaris)) $updateData['Notaris'] = $nNotaris;
        if (null !== ($nMaterai)) $updateData['Materai'] = $nMaterai;
        if (null !== ($nAsuransi)) $updateData['Asuransi'] = $nAsuransi;
        if (null !== ($nProvisi)) $updateData['Provisi'] = $nProvisi;
        if (null !== ($PersenProvisi)) $updateData['PersenProvisi'] = $PersenProvisi;
        if (null !== ($cInstansi)) $updateData['Instansi'] = $cInstansi;
        if (null !== ($cNoPengajuan)) $updateData['NoPengajuan'] = $cNoPengajuan;
        if (null !== ($cCaraPencairan)) $updateData['CaraPencairan'] = $cCaraPencairan;
        if (null !== ($nBiayaTaksasi)) $updateData['BiayaTaksasi'] = $nBiayaTaksasi;
        if (null !== ($cCassie)) $updateData['Cassie'] = $cCassie;
        if (null !== ($cKodeNotaris)) $updateData['KodeNotaris'] = $cKodeNotaris;
        if (null !== ($cKodeAsuransi)) $updateData['KodeAsuransi'] = $cKodeAsuransi;
        if (null !== ($cKolektor)) $updateData['Kolektor'] = $cKolektor;
        if (null !== ($nAngsuran1)) $updateData['Angsuran1'] = $nAngsuran1;
        if (null !== ($cSektorEkonomiOJK)) $updateData['SektorEkonomiOJK'] = $cSektorEkonomiOJK;
        if (null !== ($cUserName)) $updateData['UserName'] = $cUserName;
        if (null !== ($cCabangEntry)) $updateData['CabangEntry'] = $cCabangEntry;
        if (null !== ($nBiayaLainnya)) $updateData['BiayaLainnya'] = $nBiayaLainnya;
        if (null !== ($nBiayaTransaksi)) $updateData['BiayaTransaksi'] = $nBiayaTransaksi;
        if (null !== ($nOtorisasi)) $updateData['Otorisasi'] = $nOtorisasi;
        if (null !== ($cKeterkaitan)) $updateData['Keterkaitan'] = $cKeterkaitan;
        if (null !== ($cPeriodePembayaran)) $updateData['PeriodePembayaran'] = $cPeriodePembayaran;
        if (null !== ($cSumberDanaPelunasan)) $updateData['SumberDanaPelunasan'] = $cSumberDanaPelunasan;
        if (null !== ($cTujuanPenggunaan)) $updateData['TujuanPenggunaan'] = $cTujuanPenggunaan;
        if (null !== ($cWilayahAo)) $updateData['WilayahAo'] = $cWilayahAo;
        if (null !== ($nPinjamanKe)) $updateData['PinjamanKe'] = $nPinjamanKe;
        if (null !== ($dTglPermohonan)) $updateData['TglPermohonan'] = $dTglPermohonan;
        if (null !== ($cRekeningSebelumnya)) $updateData['RekeningSebelumnya'] = $cRekeningSebelumnya;
        if (null !== ($nBakiDebetSebelumnya)) $updateData['BakiDebetSebelumnya'] = $nBakiDebetSebelumnya;
        if (null !== ($nTBungaSebelumnya)) $updateData['TBungaSebelumnya'] = $nTBungaSebelumnya;
        if (null !== ($cNoPK)) $updateData['NoPK'] = $cNoPK;
        if (null !== ($cNoSPPK)) $updateData['NoSPPK'] = $cNoSPPK;
        if (null !== ($dTglPersetujuan)) $updateData['TglPersetujuan'] = $dTglPersetujuan;
        if (null !== ($cKategoriDebitur)) $updateData['KategoriDebitur'] = $cKategoriDebitur;
        if (null !== ($cRekeningJaminan)) $updateData['RekeningJaminan'] = $cRekeningJaminan;
        if (null !== ($cStatusPencairan)) $updateData['StatusPencairan'] = $cStatusPencairan;

        $va = [
            "Faktur" => $cFaktur,
            "Rekening" => $cRekening,
            "RekeningLama" => $cRekeningLama,
            "Nomor" => NULL,
            "Tgl" => $dTgl,
            "StatusPencairan" => $cStatusPencairan,
            "AutoDebet" => "T",
            "CaraPencairan" => $cCaraPencairan,
            "CaraPerhitungan" => $cCaraPerhitungan,
            "NoPengajuan" => $cNoPengajuan,
            "RekeningJaminan" => $cRekeningJaminan,
            "Jaminan" =>  $cNoPengajuan,
            "Wilayah" => $cWilayah,
            "Kode" => $cKode,
            "GolonganKredit" => $cGolonganKredit,
            "JenisPinjaman" => NULL,
            "GolonganDebitur" => $cGolonganDebitur,
            "SektorEkonomi" => $cSektorEkonomi,
            "SektorEkonomiOJK" => $cSektorEkonomiOJK,
            "SubSektorEkonomi" => NULL,
            "SifatKredit" => $cSifatKredit,
            "JenisPenggunaan" => $cJenisPenggunaan,
            "KelompokDebitur" => NULL,
            "GolonganPenjamin" => $cGolonganPenjamin,
            "BagianYangDijamin" => $cBagianYangDijamin,
            "instansi" =>  $cInstansi,
            "NoPK" => $cNoPK,
            "NoSPK" => $cNoSPK,
            "NoSPPK" => $cNoSPPK,
            "SukuBunga" => $nSukuBunga,
            "Plafond" => $nPlafond,
            "Lama" => $nLama,
            "GracePeriod" =>  $nGracePeriod,
            "GracePeriodPokokAwal" => $nGracePeriodPokokAwal,
            "GracePeriodBungaAwal" =>  $nGracePeriodBungaAwal,
            "Musiman" => $nMusiman,
            "AO" => $cAO,
            "RekeningTabungan" => $cRekeningTabungan,
            "Administrasi" =>  $nAdministrasi,
            "Notaris" =>  $nNotaris,
            "Materai" => $nMaterai,
            "PersenProvisi" => $PersenProvisi,
            "Provisi" =>  $nProvisi,
            "Asuransi" =>  $nAsuransi,
            "Angsuran1" => $nAngsuran1,
            "BiayaTaksasi" => $nBiayaTaksasi,
            "BiayaLainnya" => $nBiayaLainnya,
            "Cassie" => $cCassie,
            "KodeNotaris" => $cKodeNotaris,
            "KodeAsuransi" => $cKodeAsuransi,
            "JuruBayar" => $cKolektor,
            "UserName" => $cUserName,
            "CabangEntry" => $cCabangEntry,
            "StatusOtorisasi" => $nOtorisasi,
            "BiayaTransaksi" => $nBiayaTransaksi,
            "Keterkaitan" => $cKeterkaitan,
            "PeriodePembayaran" => $cPeriodePembayaran,
            "SumberDanaPelunasan" => $cSumberDanaPelunasan,
            "TujuanPenggunaan" => $cTujuanPenggunaan,
            "WilayahAO" => $cWilayahAo,
            "PinjamanKe" => $nPinjamanKe,
            "TglPermohonan" => $dTglPermohonan,
            "TglPersetujuan" => $dTglPersetujuan,
            "RekeningSebelumnya" => $cRekeningSebelumnya,
            "BakiDebetSebelumnya" => $nBakiDebetSebelumnya,
            "TBungaSebelumnya" => $nTBungaSebelumnya,
            "KategoriDebitur" => $cKategoriDebitur,
            "TglAmbilJaminan" => "9999-99-99",
            "TglLunas" => "9999-12-12",
        ];
        /* try {
            $dbData = DB::table('debitur')->where('Rekening', $cRekening)->first();
            if ($dbData) {
                DB::table('debitur')->where('Rekening', $cRekening)->update($va); // Jika data sudah ada, maka lakukan update
            } else {
                GetterSetter::setLastFaktur('R');
                DB::table('debitur')->insert($va); // Jika data belum ada, maka lakukan insert
            }

            // Update tabel pengajuan kredit
            $vaValuePengajuan = ['statuspengajuan' => '1'];
            DB::table('pengajuankredit')->where('rekening', $cNoPengajuan)->update($vaValuePengajuan);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
         }*/
        try {
            $dbData = DB::table('debitur')->where('Rekening', $cRekening)->first();
            if ($dbData) {   // -------------------------------------------------------- Jika data sudah ada, dan ada field yang ingin diupdate, maka lakukan update
                if (null !== ($updateData)) {
                    if (null !== ($cFaktur)) {
                        GetterSetter::setLastFaktur('R');
                    }
                    DB::table('debitur')->where('Rekening', $cRekening)->update($updateData);
                }
            } else {    // ------------------------------------------------------------- Jika data belum ada, maka lakukan insert
                DB::table('debitur')->insert($va);
            }

            // Update tabel pengajuan kredit
            $vaValuePengajuan = ['statuspengajuan' => '1'];
            DB::table('pengajuankredit')->where('rekening', $cNoPengajuan)->update($vaValuePengajuan);
            GetterSetter::setRekening('3');
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public function UpdAngsuranPencairan(Request $request)
    {
        $CabangEntry = $request->CabangEntry;
        $Status = $request->Status;
        $Faktur = $request->Faktur;
        $TGL = $request->TGL;
        $Rekening = $request->Rekening;
        $Keterangan = $request->Keterangan;
        $DPokok = $request->DPokok;
        $KPokok = $request->KPokok;
        $DBunga = $request->DBunga;
        $KBunga = $request->KBunga;
        $DBungaRK = $request->DBungaRK;
        $KBungaRK = $request->KBungaRK;
        $PotonganBunga = $request->PotonganBunga;
        $Denda = $request->Denda;
        $Tabungan = $request->Tabungan;
        $DTitipan = $request->DTitipan;
        $KTitipan = $request->KTitipan;
        $Administrasi = $request->Administrasi;
        $Kas = $request->Kas;
        $RekeningPB = $request->RekeningPB;
        $StatusPrinter = $request->StatusPrinter;
        $DateTime = $request->DateTime;
        $UserName = $request->UserName;
        $BungaPinalty = $request->BungaPinalty;
        $SimpananWajib = $request->SimpananWajib;
        $RRA = $request->RRA;
        $BungaTunggakan = $request->BungaTunggakan;
        $StatusPrinterRealisasi = $request->StatusPrinterRealisasi;
        $StatusPrinterSlip = $request->StatusPrinterSlip;
        $StatusPrinterKwitansi = $request->StatusPrinterKwitansi;
        $StatusAngsuran = $request->StatusAngsuran;
        $Rekonsiliasi = $request->Rekonsiliasi;
        $DRRA = $request->DRRA;
        $KRRA = $request->KRRA;
        $PPAP = $request->PPAP;
        $IPTW = $request->IPTW;

        $updateData = [];
        if (!empty($CabangEntry)) $updateData['CabangEntry'] = $CabangEntry;
        if (!empty($Status)) $updateData['Status'] = $Status;
        if (!empty($Faktur)) $updateData['Faktur'] = $Faktur;
        if (!empty($TGL)) $updateData['TGL'] = $TGL;
        if (!empty($Rekening)) $updateData['Rekening'] = $Rekening;
        if (!empty($Keterangan)) $updateData['Keterangan'] = $Keterangan;
        if (!empty($DPokok)) $updateData['DPokok'] = $DPokok;
        if (!empty($KPokok)) $updateData['KPokok'] = $KPokok;
        if (!empty($DBunga)) $updateData['DBunga'] = $DBunga;
        if (!empty($KBunga)) $updateData['KBunga'] = $KBunga;
        if (!empty($DBungaRK)) $updateData['DBungaRK'] = $DBungaRK;
        if (!empty($KBungaRK)) $updateData['KBungaRK'] = $KBungaRK;
        if (!empty($PotonganBunga)) $updateData['PotonganBunga'] = $PotonganBunga;
        if (!empty($Denda)) $updateData['Denda'] = $Denda;
        if (!empty($Tabungan)) $updateData['Tabungan'] = $Tabungan;
        if (!empty($DTitipan)) $updateData['DTitipan'] = $DTitipan;
        if (!empty($KTitipan)) $updateData['KTitipan'] = $KTitipan;
        if (!empty($Administrasi)) $updateData['Administrasi'] = $Administrasi;
        if (!empty($Kas)) $updateData['Kas'] = $Kas;
        if (!empty($RekeningPB)) $updateData['RekeningPB'] = $RekeningPB;
        if (!empty($StatusPrinter)) $updateData['StatusPrinter'] = $StatusPrinter;
        if (!empty($DateTime)) $updateData['DateTime'] = $DateTime;
        if (!empty($UserName)) $updateData['UserName'] = $UserName;
        if (!empty($BungaPinalty)) $updateData['BungaPinalty'] = $BungaPinalty;
        if (!empty($SimpananWajib)) $updateData['SimpananWajib'] = $SimpananWajib;
        if (!empty($RRA)) $updateData['RRA'] = $RRA;
        if (!empty($BungaTunggakan)) $updateData['BungaTunggakan'] = $BungaTunggakan;
        if (!empty($StatusPrinterRealisasi)) $updateData['StatusPrinterRealisasi'] = $StatusPrinterRealisasi;
        if (!empty($StatusPrinterSlip)) $updateData['StatusPrinterSlip'] = $StatusPrinterSlip;
        if (!empty($StatusPrinterKwitansi)) $updateData['StatusPrinterKwitansi'] = $StatusPrinterKwitansi;
        if (!empty($StatusAngsuran)) $updateData['StatusAngsuran'] = $StatusAngsuran;
        if (!empty($Rekonsiliasi)) $updateData['Rekonsiliasi'] = $Rekonsiliasi;
        if (!empty($DRRA)) $updateData['DRRA'] = $DRRA;
        if (!empty($KRRA)) $updateData['KRRA'] = $KRRA;
        if (!empty($PPAP)) $updateData['PPAP'] = $PPAP;
        if (!empty($IPTW)) $updateData['IPTW'] = $IPTW;

        $va = [
            "CabangEntry" => $CabangEntry,
            "Status" => $Status,
            "Faktur" => $Faktur,
            "TGL" => $TGL,
            "Rekening" => $Rekening,
            "Keterangan" => $Keterangan,
            "DPokok" => $DPokok,
            "KPokok" => $KPokok,
            "DBunga" => $DBunga,
            "KBunga" => $KBunga,
            "DBungaRK" => $DBungaRK,
            "KBungaRK" => $KBungaRK,
            "PotonganBunga" => $PotonganBunga,
            "Denda" => $Denda,
            "Tabungan" => $Tabungan,
            "DTitipan" => $DTitipan,
            "KTitipan" => $KTitipan,
            "Administrasi" => $Administrasi,
            "Kas" => $Kas,
            "RekeningPB" => $RekeningPB,
            "StatusPrinter" => $StatusPrinter,
            "DateTime" => $DateTime,
            "UserName" => $UserName,
            "BungaPinalty" => $BungaPinalty,
            "SimpananWajib" => $SimpananWajib,
            "RRA" => $RRA,
            "BungaTunggakan" => $BungaTunggakan,
            "StatusPrinterRealisasi" => $StatusPrinterRealisasi,
            "StatusPrinterSlip" => $StatusPrinterSlip,
            "StatusPrinterKwitansi" => $StatusPrinterKwitansi,
            "StatusAngsuran" => $StatusAngsuran,
            "Rekonsiliasi" => $Rekonsiliasi,
            "DRRA" => $DRRA,
            "KRRA" => $KRRA,
            "PPAP" => $PPAP,
            "IPTW" => $IPTW,
        ];
        try {
            DB::table('angsuran')->insert($va);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }
    //

    //--//--//--//--//--//--//--//--//--//--//--//

    public function getFaktur(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        $response = GetterSetter::getLastFaktur($KODE, $LEN);
        return $response;
    }

    public function getDataCetakLaporan(Request $request)
    {
        $rekening = $request->Rekening;
        $data = DB::table('debitur as d')
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongandebitur as g', 'g.Kode', '=', 'd.GolonganDebitur')
            ->leftJoin('agama as a', 'a.Kode', '=', 'r.Agama')
            ->leftJoin('ao as al', 'al.Kode', '=', 'd.AO')
            ->leftJoin('golongankredit as gl', 'gl.Kode', '=', 'd.GolonganKredit')
            ->leftJoin('kodya as k', 'k.Kode', '=', 'r.Kodya')
            ->leftJoin('sifatkredit as sk', 'sk.Kode', '=', 'd.SifatKredit')
            ->leftJoin('slik_sektorekonomi as se', 'se.Kode', '=', 'd.SektorEkonomiOjk')
            // ->leftJoin('jenispenggunaan as jp', 'jp.Kode', '=', 'd.JenisPenggunan')
            ->leftJoin('wilayah as w', 'w.Kode', '=', 'd.Wilayah')
            ->leftJoin('golonganpenjamin as gp', 'gp.Kode', '=', 'd.GolonganPenjamin')
            ->leftJoin('notaris as no', 'no.Kode', '=', 'd.KodeNotaris')
            ->leftJoin('asuransi as asu', 'asu.Kode', '=', 'd.KodeAsuransi')
            ->select(
                'd.Kode',
                'd.GolonganKredit',
                'gl.Keterangan as KetGolKredit',
                'd.Rekening',
                'r.Nama',
                'r.KTP',
                'r.TempatLahir',
                'r.TglLahir',
                'r.Alamat',
                'r.Kodya',
                'k.Keterangan as KetKodya',
                'r.KodePos',
                'r.Telepon',
                'd.Lama',
                'd.Plafond',
                'd.SukuBunga',
                'd.Provisi',
                'd.Administrasi',
                'd.Materai',
                'd.Notaris',
                'd.Asuransi',
                'd.Lainnya',
                'd.SimpananPokok',
                'd.SimpananWajib',
                'd.AO',
                'al.Nama as NamaAO',
                'd.Tgl'
            )
            ->where('d.Rekening', '=', $rekening)
            ->first();
        if ($data) {
            $data2 = DB::table('agunan as a')
                ->leftJoin('debitur as d', 'd.RekeningJaminan', '=', 'a.Rekening')
                ->select(
                    'a.Rekening',
                    'a.No',
                    'a.Jaminan'
                )
                ->where('d.Rekening', '=', $rekening)
                ->orderBy('a.No', 'ASC')
                ->get();
            foreach ($data2 as $d2) {
                $vaDetail = GetterSetter::getDetailJaminan($d2->Rekening, $d2->Jaminan, $d2->No, $data->Tgl);
                $detailJaminan = '';
                foreach ($vaDetail as $k => $va) {
                    foreach ($va as $key => $value) {
                        if (!empty($value)) {
                            $cKey = $key . ' : ' . $value;
                            $key = trim($key);
                            if (empty($key)) $cKey = $value;
                            $detailJaminan .= $cKey . ', ';
                        }
                    }
                }
                $detailJaminan = substr($detailJaminan, 0, -2) . '.';
            }
            $tgl = $data->Tgl;
            $lama = intval($data->Lama);
            $jthTmp = Carbon::parse($tgl)->addMonths($lama)->format('d-m-Y');
            $array = [
                'CIF' => $data->Kode,
                'Aplikasi' => $data->GolonganKredit . ' - ' . $data->KetGolKredit,
                'NoRekening' => $data->Rekening,
                'Nama' => $data->Nama,
                'KTP' => $data->KTP,
                'TmpTglLahir' => $data->TempatLahir . ', ' . Carbon::parse($data->TglLahir)->format('d-m-Y'),
                'Alamat' => $data->Alamat,
                'Kota' => $data->Kodya . ' - ' . $data->KetKodya,
                'KodePos' => $data->KodePos,
                'Telepon' => $data->Telepon,
                'TglAwal' => Carbon::parse($tgl)->format('d-m-Y'),
                'TglAkhir' => $jthTmp,
                'Plafond' => $data->Plafond,
                'SukuBunga' => $data->SukuBunga,
                'Provisi' => $data->Provisi,
                'Administrasi' => $data->Administrasi,
                'Materai' => $data->Materai,
                'Notaris' => $data->Notaris,
                'Asuransi' => $data->Asuransi,
                'Lainnya' => $data->Lainnya,
                'SimpananPokok' => $data->SimpananPokok,
                'SimpananWajib' => $data->SimpananWajib,
                'AO' => $data->AO . ' - ' . $data->NamaAO,
                'Lama' => $lama,
                'No' => $d2->No,
                'Isi' => $detailJaminan
            ];
            return response()->json($array);
        }
    }
}
