<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\Jaminan;
use App\Models\master\RegisterNasabah;
use App\Models\pinjaman\Agunan;
use App\Models\pinjaman\AgunanDetailTmp;
use App\Models\pinjaman\Debitur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengikatanJaminanController extends Controller
{
    public function getUrutPengikatanJaminan(Request $request)
    {
        // Cek registernasabah
        $registerNasabah = $request->RegisterNasabah;
        $data = RegisterNasabah::where('Kode', $registerNasabah)->first();
        if ($data) {
            $nama = $data->Nama;
        }
        // Tambah nilai frekuensi dengan menambah nilai 1 secara otomatis
        $data2 = DB::table('agunan')
            ->select(DB::raw('max(Rekening) as Rekening'))
            ->where('Rekening', 'LIKE', $registerNasabah . '%')
            ->first();
        if ($data2) {
            $rekening = $data2->Rekening;
            $frekuensi = intval(substr($rekening, 11, 4));
            $frekuensi = strval(intval($frekuensi) + 1);
            $frekuensi = str_pad($frekuensi, 4, '0', STR_PAD_LEFT);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'error'],
                404
            );
        }
        $array = [
            'Nama' => $nama,
            'Frekuensi' => $frekuensi
        ];
        return response()->json($array);
    }

    public function getNomor(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;
            $data = Agunan::where('Rekening', $rekAgunan)
                ->where('Status', 1)
                ->max('No') + 1;
            $data2 = AgunanDetailTmp::where('Rekening', $rekAgunan)
                ->where('Status', 1)
                ->max('No') + 1;
            $nomor = max($data, $data2);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(
                ['status' => 'error', 'message' => 'error'],
                404
            );
        }
        return response()->json($nomor);
    }

    public function storeTemp(Request $request)
    {
        try {
            $allIterationsSuccessful = false;
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;
            $jaminan = $request->Jaminan;
            $nomor = $request->Nomor;
            $array = [
                'Rekening' => $rekAgunan,
                'Tgl' => GetterSetter::getTglTransaksi(),
                'Kode' => $registerNasabah,
                'No' => $nomor,
                'Jaminan' => $jaminan,
                'NilaiJaminan' => $request->NilaiPasar,
                'NilaiYangDiPerhitungkan' => $request->NilaiYangDiagunkan
            ];
            if ($jaminan == '1' || $jaminan == '7' || $jaminan == '8') { // SBI, PERSEDIAAN BARANG, TANAH BANGUNAN
                foreach ($request->input('detail') as $d) {
                    $allIterationsSuccessful = true;
                    // dd('kesini');
                    $array['L_Note'] = $d['Keterangan'] ?? "";
                }
            } else if ($jaminan == '2' || $jaminan == '3') { // TABUNGAN DAN DEPOSITO PADA KOPERASI
                foreach ($request->input('detail') as $d) {
                    $allIterationsSuccessful = true;
                    $array['D_Jenis'] = $d['Jenis'] ?? "";
                    $array['D_Rekening'] = $d['NoRekening'] ?? "";
                    $array['D_Bilyet'] = $d['NoBilyet'] ?? "";
                    $array['D_Nominal'] = $d['Nominal'] ?? "";
                    $array['Nama'] = $d['AtasNama'] ?? "";
                    $array['Alamat'] = $d['Alamat'] ?? "";
                    $array['L_Note'] = $d['Keterangan'] ?? "";
                }
            } else if ($jaminan == '4') { // PERHIASAN EMAS DAN LOGAM MULIA
                foreach ($request->input('detail') as $d) {
                    $allIterationsSuccessful = true;
                    $array['P_Uraian'] = $d['Uraian'] ?? "";
                    $array['P_Jumlah'] = $d['Jumlah'] ?? "";
                    $array['P_Berat'] = $d['Berat'] ?? "";
                    $array['P_Kadar'] = $d['Kadar'] ?? "";
                    $array['Nama'] = $d['AtasNama'] ?? "";
                    $array['Alamat'] = $d['Alamat'] ?? "";
                }
            } else if ($jaminan == '5') { // KENDARAAN BERMOTOR
                foreach ($request->input('detail') as $d) {
                    $allIterationsSuccessful = true;
                    $array['Nama'] = $d['AtasNama'] ?? "";
                    $array['M_BPKB'] = $d['NoBPKB'] ?? "";
                    $array['Alamat'] = $d['AlamatJaminan'] ?? "";
                    $array['M_Alamat'] = $d['AlamatJaminan'] ?? "";
                    $array['M_NoRangka'] = $d['NoRangka'] ?? "";
                    $array['M_NoMesin'] = $d['NoMesin'] ?? "";
                    $array['M_NoPolisi'] = $d['NoPolisi'] ?? "";
                    $array['M_NoSTNK'] = $d['NoSTNK'];
                    $array['M_TglSTNK'] = !empty($d['M_TglSTNK']) ? Func::String2Date($d['M_TglSTNK']) : "1900-01-01";
                    $array['M_Merk'] = $d['Merk'] ?? "";
                    $array['M_Type'] = $d['Type'] ?? "";
                    $array['M_Model'] = $d['Model'] ?? "";
                    $array['M_Tahun'] = $d['Tahun'] ?? "";
                    $array['M_Warna'] = $d['Warna'] ?? "";
                    $array['NoRegBPKB'] = $d['NoRegBPKB'] ?? "";
                    $array['M_Silinder'] = $d['Silinder'] ?? "";
                    $array['L_Note'] = $d['KeteranganTambahan'] ?? "";
                }
            } else if ($jaminan == '6') { // TANAH DAN BANGUNAN
                foreach ($request->input('detail') as $d) {
                    $allIterationsSuccessful = true;
                    $array['Nama'] = $d['AtasNama'] ?? "";
                    $array['S_JenisPengikatan'] = $d['Pengikatan'] ?? "";
                    $array['S_Nomor'] = $d['NoSHM'] ?? "";
                    $array['S_Tgl'] = !empty($d['S_Tgl']) ? Func::String2Date($d['S_Tgl']) : "1900-01-01";
                    $array['S_Agraria'] = $d['Kabupaten'] ?? "";
                    $array['S_NoDWG'] = $d['NoGS'] ?? "";
                    $array['S_TglDWG'] = !empty($d['TglGS']) ? Func::String2Date($d['TglGS']) : "1900-01-01";
                    $array['Alamat'] = $d['AlamatJaminan'] ?? "";
                    $array['BatasBarat'] = $d['BatasBarat'] ?? "";
                    $array['S_NIB'] = $d['NIB'] ?? "";
                    $array['BatasSelatan'] = $d['BatasSelatan'] ?? "";
                    $array['BatasTimur'] = $d['BatasTimur'] ?? "";
                    $array['S_Kota'] = $d['Kabupaten'] ?? "";
                    $array['S_Luas'] = $d['Luas'] ?? "";
                    $array['BatasUtara'] = $d['BatasUtara'] ?? "";
                    $array['S_Keadaan'] = $d['KeadaanJaminan'] ?? "";
                    $array['S_Alamat'] = $d['AlamatJaminan'] ?? "";
                    $array['L_Note'] = $d['Keterangan'] ?? "";
                    $array['S_Provinsi'] = $d['Provinsi'] ?? "";
                    $array['S_Jenis'] = $d['Jenis'] ?? "";
                    $array['S_JenisSurat'] = $d['JenisSurat'] ?? "";
                }
            }
            $existAgunan = AgunanDetailTmp::where('Rekening', $rekAgunan)
                ->where('No', $nomor)
                ->exists();
            if ($existAgunan) {
                AgunanDetailTmp::where('Rekening', $rekAgunan)
                    ->where('No', $nomor)
                    ->update($array);
            } else {
                AgunanDetailTmp::create($array);
            }
            if ($allIterationsSuccessful) {
                return response()->json(['status' => 'success']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'One or more iterations failed']);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public function store(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;

            Agunan::where('Rekening', $rekAgunan)->delete();
            $data = AgunanDetailTmp::where('Rekening', $rekAgunan)->get();
            foreach ($data as $agunanTmp) {
                unset($agunanTmp->ID);
                $agunan = new Agunan();
                $agunan->fill($agunanTmp->toArray());
                $agunan->save();
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }

        return response()->json(['status' => 'success']);
    }

    public function getDataJaminanTabel(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;
            $exists = AgunanDetailTmp::where('Rekening', $rekAgunan)->exists();
            if ($exists) {
                $data = AgunanDetailTmp::with('jaminan')
                    ->where('Rekening', $rekAgunan)->get();

                $resultArray = []; // Array untuk menampung data yang akan dikirimkan

                foreach ($data as $d) {
                    $array = [
                        'No' => $d->No,
                        'Jaminan' => $d->jaminan->Kode,
                        'KeteranganJaminan' => $d->jaminan->Keterangan,
                        'NilaiPasar' => $d->NilaiJaminan,
                        'NilaiYangDiagunkan' => $d->NilaiYangDiPerhitungkan
                    ];
                    $resultArray[] = $array; // Tambahkan data ke dalam array
                }
                return response()->json($resultArray); // Kirimkan seluruh array
            } else {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Tidak Ditemukan!'],
                    404
                );
            }
        } catch (\Throwable $th) {
            return response()->json(
                ['status' => 'error', 'message' => 'error'],
                404
            );
        }
    }

    public function getDataEdit(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;
            $no = $request->Nomor;
            $data = AgunanDetailTmp::where('Rekening', $rekAgunan)
                ->where('No', $no)
                ->orderByDesc('No')
                ->first();
            if ($data) {
                if ($data->Jaminan == '1' || $data->Jaminan == '7' | $data->Jaminan == '8') { // SBI, PERSEDIAAN BARANG, TANPA AGUNAN
                    $array = [
                        'Keterangan' => $data->L_Note
                    ];
                } else if ($data->Jaminan == '2' || $data->Jaminan == '3') { // TABUNGAN DAN DEPOSITO PADA KOPERASI
                    $array = [
                        'Jenis' => $data->D_Jenis,
                        'NoRekening' => $data->D_Rekening,
                        'NoBilyet' => $data->D_Bilyet,
                        'Nominal' => $data->D_Nominal,
                        'AtasNama' => $data->Nama,
                        'Alamat' => $data->Alamat,
                        'Keterangan' => $data->L_Note
                    ];
                } else if ($data->Jaminan == '4') { // PERHIASAN EMAS DAN LOGAM MULIA
                    $array = [
                        'Uraian' => $data->P_Uraian,
                        'Jumlah' => $data->P_Jumlah,
                        'Berat' => $data->P_Berat,
                        'Kadar' => $data->P_Kadar,
                        'AtasNama' => $data->Nama,
                        'Alamat' => $data->Alamat
                    ];
                } else  if ($data->Jaminan == '5') { // KENDARAAN BERMOTOR
                    // dd($data);
                    $array = [
                        'Merk' => $data->M_Merk,
                        'Type' => $data->M_Type,
                        'Tahun' => $data->M_Tahun,
                        'NoRangka' => $data->M_NoRangka,
                        'NoPolisi' => $data->M_NoPolisi,
                        'NoMesin' => $data->M_NoMesin,
                        'NoBPKB' => $data->M_BPKB,
                        'NoSTNK' => $data->M_NoSTNK,
                        'Warna' => $data->M_Warna,
                        'AtasNama' => $data->Nama,
                        'AlamatJaminan' => $data->M_Alamat,
                        'MasaPajak' => $data->M_TglSTNK,
                        'NoRegBPKB' => $data->NoRegBPKB,
                        'Silinder' => $data->M_Silinder,
                        'KeteranganTambahan' => $data->L_Note
                    ];
                } else if ($data->Jaminan == '6') { // TANAH DAN BANGUNAN
                    $array = [
                        'NoSHM' => $data->S_Nomor,
                        'Jenis' => $data->S_Jenis,
                        'JenisSurat' => $data->S_JenisSurat,
                        'NoGS' => $data->S_NoDWG,
                        'TglGS' => $data->S_TglDWG,
                        'NoNIB' => $data->S_NIB,
                        'Luas' => $data->S_Luas,
                        'AlamatJaminan' => $data->Alamat,
                        'Kabupaten' => $data->S_Kota,
                        'Provinsi' => $data->S_Provinsi,
                        'AtasNama' => $data->Nama,
                        'KetJaminan' => $data->S_Keadaan,
                        'Keterangan' => $data->L_Note,
                        'BatasUtara' => $data->BatasUtara,
                        'BatasTimur' => $data->BatasTimur,
                        'BatasSelatan' => $data->BatasSelatan,
                        'BatasBarat' => $data->BatasBarat
                    ];
                }
            }
        } catch (\Throwable $th) {
            return response()->json(
                ['status' => 'error', 'message' => 'Data Tidak Ditemukan!'],
                404
            );
        }
        return response()->json($array);
    }

    public function update(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekAgunan = $registerNasabah . $frekuensi;
            $no = $request->Nomor;
            $jaminan = $request->Jaminan;
            if ($jaminan == '1' || $jaminan == '7' || $jaminan == '8') { // SBI, PERSEDIAAN BARANG, TANPA AGUNAN
                $array = [
                    'Jaminan' => $jaminan,
                    'NilaiJaminan' => $request->NilaiPasar,
                    'NilaiYangDiPerhitungkan' => $request->NilaiYangDiagunkan,
                    'L_Note' => $request->Keterangan
                ];
            } else if ($jaminan == '2' || $jaminan == '3') { // TABUNGAN DAN DEPOSITO PADA KOPERASI
                $array = [
                    'Jaminan' => $jaminan,
                    'NilaiJaminan' => $request->NilaiPasar,
                    'NilaiYangDiPerhitungkan' => $request->NilaiYangDiagunkan,
                    'D_Jenis' => $request->Jenis,
                    'D_Rekening' => $request->NoRekening,
                    'D_Bilyet' => $request->NoBilyet,
                    'D_Nominal' => $request->Nominal,
                    'Nama' => $request->AtasNama,
                    'Alamat' => $request->Alamat,
                    'L_Note' => $request->Keterangan
                ];
            } else if ($jaminan == '4') { // PERHIASAN EMAS DAN LOGAM MULIA
                $array = [
                    'P_Uraian' => $request->Uraian,
                    'P_Jumlah' => $request->Jumlah,
                    'P_Berat' => $request->Berat,
                    'P_Kadar' => $request->Kadar,
                    'Nama' => $request->AtasNama,
                    'Alamat' => $request->Alamat
                ];
            } else if ($jaminan == '5') { // KENDARAAN BERMOTOR
                $array = [
                    'Jaminan' => $jaminan,
                    'NilaiJaminan' => $request->NilaiPasar,
                    'NilaiYangDiPerhitungkan' => $request->NilaiYangDiagunkan,
                    'Nama' => $request->AtasNama,
                    'M_BPKB' => $request->NoBPKB,
                    'M_Alamat' => $request->AlamatJaminan,
                    'M_NoRangka' => $request->NoRangka,
                    'M_NoMesin' => $request->NoMesin,
                    'M_NoPolisi' => $request->NoPolisi,
                    'M_NoSTNK' => $request->NoSTNK,
                    'M_TglSTNK' => $request->MasaPajak,
                    'M_Merk' => $request->Merk,
                    'M_Type' => $request->Type,
                    'M_Model' => $request->Model,
                    'M_Tahun' => $request->Tahun,
                    'M_Warna' => $request->Warna,
                    'NoRegBPKB' => $request->NoRegBPKB,
                    'M_Silinder' => $request->Silinder,
                    'L_Note' => $request->KeteranganTambahan
                ];
            } else if ($jaminan == '6') { // TANAH DAN BANGUNAN
                $array = [
                    'Jaminan' => $jaminan,
                    'NilaiJaminan' => $request->NilaiPasar,
                    'NilaiYangDiPerhitungkan' => $request->NilaiYangDiagunkan,
                    'Nama' => $request->AtasNama,
                    'S_JenisPengikatan' => $request->Pengikatan,
                    'S_Nomor' => $request->NoSHM,
                    'S_Tgl' => $request->TglGS,
                    'S_Agraria' => $request->Kabupaten,
                    'S_NoDWG' => $request->NoGS,
                    'S_TglDWG' => $request->TglGS,
                    'Alamat' => $request->AlamatJaminan,
                    'BatasBarat' => $request->BatasBarat,
                    'S_NIB' => $request->NIB,
                    'BatasSelatan' => $request->BatasSelatan,
                    'BatasTimur' => $request->BatasTimur,
                    'S_Kota' => $request->Kabupaten,
                    'S_Luas' => $request->Luas,
                    'BatasUtara' => $request->BatasUtara,
                    'S_Keadaan' => $request->KeadaanJaminan,
                    'S_Alamat' => $request->AlamatJaminan,
                    'L_Note' => $request->Keterangan,
                    'S_Provinsi' => $request->Provinsi,
                    'S_Jenis' => $request->Jenis,
                    'S_JenisSurat' => $request->JenisSurat
                ];
            }
            AgunanDetailTmp::where('Rekening', $rekAgunan)
                ->where('No', $no)
                ->update($array);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function getTableDelete()
    {
        try {
            $data
                = DB::table('agunan')
                ->select('Rekening', DB::raw('MAX(Tgl) AS Tgl'))
                ->selectRaw('IFNULL(SUM(NilaiJaminan), 0) AS NilaiPasar')
                ->selectRaw('IFNULL(SUM(NilaiYangDiPerhitungkan), 0) AS NilaiYangDiAgunkan')
                ->groupBy('Rekening')
                ->orderByDesc(DB::raw('MAX(ID)'))
                ->limit(100)
                ->get();
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function deleteTmp(Request $request)
    {
        try {
            $registerNasabah = $request->RegisterNasabah;
            $frekuensi = $request->Frekuensi;
            $rekening = $registerNasabah . $frekuensi;
            $no = $request->Nomor;
            $agunanTmp = AgunanDetailTmp::where('Rekening', $rekening)
                ->where('No', $no)
                ->first();
            if ($agunanTmp) {
                $agunanTmp->delete();
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function delete(Request $request)
    {
        $existDebitur = Debitur::where('RekeningJaminan', $request->Rekening)
            ->exists();
        if ($existDebitur) {
            return response()->json(['status' => 'error']);
        }
        $agunan = Agunan::where('Rekening', $request->Rekening);
        $agunan->delete();
        $agunanTmp = AgunanDetailTmp::where('Rekening', $request->Rekening);
        $agunanTmp->delete();
        return response()->json(['status' => 'success']);
    }
}
