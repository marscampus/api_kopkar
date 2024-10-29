<?php
/*
 * Copyright (C) Godong
 *http://www.marstech.co.id
 *Email. info@marstech.co.id
 *Telp. 0811-3636-09
 *Office        : Jl. Margatama Asri IV, Kanigoro, Kec. Kartoharjo, Kota Madiun, Jawa Timur 63118
 *Branch Office : Perum Griya Gadang Sejahtera Kav. 14 Gadang - Sukun - Kota Malang - Jawa Timur
 *
 *Godong
 *Adalah merek dagang dari PT. Marstech Global
 *
 *License Agreement
 *Software komputer atau perangkat lunak komputer ini telah diakui sebagai salah satu aset perusahaan yang bernilai.
 *Di Indonesia secara khusus,
 *software telah dianggap seperti benda-benda berwujud lainnya yang memiliki kekuatan hukum.
 *Oleh karena itu pemilik software berhak untuk memberi ijin atau tidak memberi ijin orang lain untuk menggunakan softwarenya.
 *Dalam hal ini ada aturan hukum yang berlaku di Indonesia yang secara khusus melindungi para programmer dari pembajakan software yang mereka buat,
 *yaitu diatur dalam hukum hak kekayaan intelektual (HAKI).
 *
 *********************************************************************************************************
 *Pasal 72 ayat 3 UU Hak Cipta berbunyi,
 *' Barangsiapa dengan sengaja dan tanpa hak memperbanyak penggunaan untuk kepentingan komersial '
 *' suatu program komputer dipidana dengan pidana penjara paling lama 5 (lima) tahun dan/atau '
 *' denda paling banyak Rp. 500.000.000,00 (lima ratus juta rupiah) '
 *********************************************************************************************************
 *
 *Proprietary Software
 *Adalah software berpemilik, sehingga seseorang harus meminta izin serta dilarang untuk mengedarkan,
 *menggunakan atau memodifikasi software tersebut.
 *
 *Commercial software
 *Adalah software yang dibuat dan dikembangkan oleh perusahaan dengan konsep bisnis,
 *dibutuhkan proses pembelian atau sewa untuk bisa menggunakan software tersebut.
 *Detail Licensi yang dianut di software https://en.wikipedia.org/wiki/Proprietary_software
 *EULA https://en.wikipedia.org/wiki/End-user_license_agreement
 *
 *Lisensi Perangkat Lunak https://id.wikipedia.org/wiki/Lisensi_perangkat_lunak
 *EULA https://id.wikipedia.org/wiki/EULA
 *
 * Created on Mon Feb 19 2024 - 03:49:39
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterJaminanController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            ini_set('max_execution_time', '0');
            $cUser =  $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $cJenisGabungan = $vaRequestData['JenisGabungan'];
            $cCabang = null;
             if ($cJenisGabungan !== "C") {
                $cCabang = $vaRequestData['Cabang'];
            }
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cJaminan = $vaRequestData['Jaminan'];
            $cStatusDebitur = $vaRequestData['StatusDebitur'];
            if ($cStatusDebitur == 'AKTIF') {
                $cHavingBakiDebet = "BakiDebet > 0";
            } elseif ($cStatusDebitur == 'LUNAS') {
                $cHavingBakiDebet = "BakiDebet = 0";
            } else {
                $cHavingBakiDebet = "BakiDebet >= 0";
            }
            $cNoSPK = null;
            if ($cNoSPK !== null) {
                $cNoSPK = $vaRequestData['NoSpk'];
            }
            $cType = $vaRequestData['Tipe'];
            $vaResult = [];
            $nRow = 0;
            if ($cJaminan == 5) {
                $vaData = DB::table('debitur AS d')
                    ->select(
                        'r.Nama',
                        'd.Rekening AS RekeningKredit',
                        'd.AO',
                        'd.RekeningLama',
                        'a.ID',
                        'a.Kode',
                        'a.No',
                        'a.Tgl',
                        'a.Rekening',
                        'a.M_BPKB',
                        'a.Nama AS AtasNama',
                        'a.M_Alamat',
                        'a.M_NoRangka',
                        'a.M_NoMesin',
                        'a.M_NoPolisi',
                        'a.M_Merk',
                        'a.M_Model',
                        'a.M_Tahun',
                        'a.M_Warna',
                        'a.NilaiJaminan',
                        DB::raw('SUM(ag.dpokok - ag.kpokok) AS BakiDebet')
                    )
                    ->leftJoin('angsuran AS ag', function ($join) use ($dTglAkhir) {
                        $join->on('ag.rekening', '=', 'd.rekening')
                            ->where('ag.tgl', '<=', $dTglAkhir);
                    })
                    ->leftJoin('agunan AS a', 'a.rekening', '=', 'd.rekeningjaminan')
                    ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
                    ->leftJoin('cabang AS c', 'c.kode', '=', 'd.CabangEntry')
                    ->leftJoin('jaminan AS j', 'j.Kode', '=', 'a.Jaminan')
                    ->when($cNoSPK, function ($query) use ($cNoSPK) {
                        return $query->where('d.RekeningLama', 'LIKE', $cNoSPK . '%');
                    })
                    ->when(
                        $cJenisGabungan !== 'C',
                        function ($query) use ($cJenisGabungan, $cCabang) {
                            $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                        }
                    )
                    ->whereBetween('d.Tgl', [$dTglAwal, $dTglAkhir])
                    ->where('a.Jaminan', '=', $cJaminan)
                    ->groupBy(
                        'd.Rekening'
                    )
                    ->havingRaw($cHavingBakiDebet)
                    ->orderBy('d.tgl')
                    ->orderBy('d.rekening')
                    ->orderBy('a.rekening')
                    ->orderBy('a.no')
                    ->get();
                foreach ($vaData as $d) {
                    if ($d->M_Model == "Kendaraan Roda 2") {
                        $cModel = "Roda 2";
                    } else {
                        $cModel = "Roda 4";
                    }
                    if ($cType === 'T') {
                        $vaArray = [
                            'NO.' => ++$nRow,
                            'TGL' => date('d-m-Y', strtotime($d->Tgl)),
                            'NO. JAMINAN' => $d->Rekening,
                            'REK. PINJAMAN' => $d->RekeningKredit,
                            'NAMA NASABAH' => $d->Nama,
                            'ATAS NAMA' => $d->AtasNama,
                            'ALAMAT' => $d->M_Alamat,
                            'NO. RANGKA' => $d->M_NoRangka,
                            'NO. MESIN' => $d->M_NoMesin,
                            'NO. POLISI' => $d->M_NoPolisi,
                            'MERK' => $d->M_Merk,
                            'MODEL' => $cModel,
                            'TAHUN' => $d->M_Tahun,
                            'WARNA' => $d->M_Warna,
                            'NILAI' => Func::getZFormat($d->NilaiJaminan),
                            'AO' => $d->AO
                        ];
                    } else if ($cType === 'P') {
                        $vaArray = [
                            'No' => ++$nRow,
                            'Tgl' => $d->Tgl,
                            'NoJaminan' => $d->Rekening,
                            'RekPinjaman' => $d->RekeningKredit,
                            'NamaNasabah' => $d->Nama,
                            'AtasNama' => $d->AtasNama,
                            'Alamat' => str_replace(',', ' ', $d->M_Alamat),
                            'NoRangka' => $d->M_NoRangka,
                            'NoMesin' => $d->M_NoMesin,
                            'NoPolisi' => $d->M_NoPolisi,
                            'Merk' => $d->M_Merk,
                            'Model' => $cModel,
                            'Tahun' => $d->M_Tahun,
                            'Warna' => $d->M_Warna,
                            'Nilai' => $d->NilaiJaminan,
                            'AO' => $d->AO
                        ];
                    }
                    $vaResult[] = $vaArray;
                }
            } else if ($cJaminan == 6) {
                $vaData = DB::table('debitur AS d')
                    ->select(
                        'd.Rekening AS RekeningKredit',
                        'r.Nama',
                        'a.Tgl',
                        'a.Kode',
                        'a.Rekening',
                        'a.S_Nomor',
                        'a.S_Tgl',
                        'a.S_Agraria',
                        'a.S_NoDWG',
                        'a.S_TglDWG',
                        'a.S_Alamat',
                        'a.Nama AS AtasNama',
                        'a.S_Desa',
                        'a.S_Kecamatan',
                        'a.S_Kota',
                        'a.S_Luas',
                        'a.S_Keadaan',
                        'a.NilaiJaminan',
                        'a.No',
                        'a.ID',
                        'd.AO',
                        DB::raw('SUM(ag.dpokok - ag.kpokok) AS BakiDebet')
                    )
                    ->when($cNoSPK, function ($query) use ($cNoSPK) {
                        return $query->where('d.RekeningLama', 'LIKE', $cNoSPK . '%');
                    })
                    ->leftJoin('angsuran AS ag', function ($join) use ($dTglAkhir) {
                        $join->on('ag.rekening', '=', 'd.rekening')
                            ->where('ag.tgl', '<=', $dTglAkhir);
                    })
                    ->leftJoin('agunan AS a', 'a.rekening', '=', 'd.rekeningjaminan')
                    ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
                    ->leftJoin('cabang AS c', 'c.kode', '=', 'd.CabangEntry')
                    ->whereBetween('d.Tgl', [$dTglAwal, $dTglAkhir])
                    ->when(
                        $cJenisGabungan !== 'C',
                        function ($query) use ($cJenisGabungan, $cCabang) {
                            $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                        }
                    )
                    ->where('a.Jaminan', '=', $cJaminan)
                    ->groupBy(
                        'd.Rekening'
                    )
                    ->havingRaw($cHavingBakiDebet)
                    ->orderBy('d.tgl')
                    ->orderBy('d.rekening')
                    ->orderBy('a.rekening')
                    ->orderBy('a.no')
                    ->get();
                foreach ($vaData as $d) {
                    $cLetakTanah = $d->S_Alamat . ' ' . $d->S_Desa . ' ' . $d->S_Kecamatan . ' ' . $d->S_Kota;
                    if ($cType === 'T') {
                        $vaArray = [
                            'NO' => ++$nRow,
                            'TGL' => date('d-m-Y', strtotime($d->Tgl)),
                            'NO. JAMINAN' => $d->Rekening,
                            'REK. PINJAMAN' => $d->RekeningKredit,
                            'NAMA' => $d->Nama,
                            'NO. SHM' => $d->S_Nomor,
                            'TGL SHM' => date('d-m-Y', strtotime($d->S_Tgl)),
                            'LETAK TANAH' => $cLetakTanah,
                            'NO. GAMBAR' => $d->S_NoDWG,
                            'TGL GAMBAR' => date('d-m-Y', strtotime($d->S_TglDWG)),
                            'LUAS TANAH' => $d->S_Luas,
                            'PEMEGANG HAK' => $d->AtasNama,
                            'NOMINAL' => Func::getZFormat($d->NilaiJaminan),
                            'AO' => $d->AO
                        ];
                    } else if ($cType === 'P') {
                        $vaArray = [
                            'No' => ++$nRow,
                            'Tgl' => $d->Tgl,
                            'NoJaminan' => $d->Rekening,
                            'RekPinjaman' => $d->RekeningKredit,
                            'Nama' => $d->Nama,
                            'NoShm' => $d->S_Nomor,
                            'TglShm' => $d->S_Tgl,
                            'LetakTanah' => $cLetakTanah,
                            'NoGambar' => $d->S_NoDWG,
                            'TglGambar' => $d->S_TglDWG,
                            'LuasTanah' => $d->S_Luas,
                            'PemegangHak' => $d->AtasNama,
                            'Nominal' => $d->NilaiJaminan,
                            'AO' => $d->AO
                        ];
                    }
                    $vaResult[] = $vaArray;
                }
            } else {
                $vaData = DB::table('debitur as d')
                    ->select(
                        'a.ID',
                        'r.Nama',
                        'd.NoSPK',
                        'd.Rekening as RekeningKredit',
                        'd.AO',
                        'a.Tgl',
                        'a.Kode',
                        'a.Rekening',
                        'a.Nama as AtasNama',
                        'a.NilaiJaminan',
                        'a.D_Rekening',
                        'a.L_Note'
                    )
                    ->leftJoin('agunan as a', 'a.Rekening', '=', 'd.RekeningJaminan')
                    ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                    ->leftJoin('cabang as c', 'c.Kode', '=', 'd.CabangEntry')
                    ->whereBetween('d.Tgl', [$dTglAwal, $dTglAkhir])
                    ->where('a.Jaminan', '=', $cJaminan)
                    ->when(
                        $cJenisGabungan !== 'C',
                        function ($query) use ($cJenisGabungan, $cCabang) {
                            $query->whereRaw(GetterSetter::getWhereJenisGabungan($cJenisGabungan, $cCabang));
                        }
                    )
                    ->when($cNoSPK, function ($query) use ($cNoSPK) {
                        return $query->where('d.RekeningLama', 'LIKE', $cNoSPK . '%');
                    })
                    ->get();
                foreach ($vaData as $d) {
                    if ($cType === 'T') {
                        $vaArray = [
                            'NO' => ++$nRow,
                            'TGL' => date('d-m-Y', strtotime($d->Tgl)),
                            'NO. JAMINAN' => $d->Rekening,
                            'NO. SPK' => $d->NoSPK,
                            'NAMA' => $d->Nama,
                            'REK. PINJAMAN' => $d->RekeningKredit,
                            'NILAI JAMINAN' => Func::getZFormat($d->NilaiJaminan),
                            'KETERANGAN' => $d->L_Note,
                            'AO' => $d->AO
                        ];
                    } else if ($cType === 'P') {
                        $vaArray = [
                            'No' => ++$nRow,
                            'Tgl' => $d->Tgl,
                            'NoJaminan' => $d->Rekening,
                            'NoSPK' => $d->NoSPK,
                            'Nama' => $d->Nama,
                            'RekPinjaman' => $d->RekeningKredit,
                            'NilaiJaminan' => $d->NilaiJaminan,
                            'Keterangan' => $d->L_Note,
                            'AO' => $d->AO
                        ];
                    }
                    $vaResult[] = $vaArray;
                }
            }
            $vaResults = [
                'data' => $vaResult,
                'total_data' => count($vaResult)
            ];
            if ($vaResults) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResults
                ];
                Func::writeLog('Register Jaminan', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResults);
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

            Func::writeLog('Register Jaminan', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
