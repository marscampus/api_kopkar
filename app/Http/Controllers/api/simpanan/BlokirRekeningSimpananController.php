<?php

namespace App\Http\Controllers\api\simpanan;

use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlokirRekeningSimpananController extends Controller
{
    public function data(Request $request)
    {
        $limit = 10;
        $startDate = $request->TglAwal;
        $endDate = $request->TglAkhir;
        $no = 0;
        $data =
            DB::table('tabungan as t')
            ->select(
                't.Rekening',
                'r.Nama',
                'r.Alamat',
                'r.Kode',
                't.Tgl',
                'g.Keterangan as NamaGolongan',
                't.GolonganTabungan',
                't.StatusBlokir',
                't.JumlahBlokir',
                't.TglBlokir',
            )
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('cabang as c', 'c.kode', '=', 't.cabangentry')
            ->where('t.statusblokir', '!=', 0);
        if (!$startDate && !$endDate) {
            return response()->json([]);
        }
        if ($startDate !== null && $endDate !== null) {
            $data->whereBetween('t.TglBlokir', [$startDate, $endDate]);
            $data->orderByDesc('t.TglBlokir');
        }
        // $data->limit(10);
        $data = $data->get();

        $responseArray = [];

        foreach ($data as $d) {
            $no++;
            $rekening = $d->Rekening;
            $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($rekening, $d->Tgl);
            $array = [
                'No' => $no,
                'CIF' => $d->Kode,
                'Rekening' => $rekening,
                'Nama' => $d->Nama,
                'Alamat' => $d->Alamat,
                'GolTabungan' => $d->NamaGolongan ?? '', // Gunakan null coalescing operator
                // 'TglBuka' => $d->TglBuka,
                'TglBlokir' => $d->TglBlokir,
                'JumlahBlokir' => $d->JumlahBlokir,
                'SaldoTabungan' => $saldoTabungan
            ];
            $responseArray[] = $array;
        }
        $totalData = count($responseArray);
        if ($totalData > 0) {
            // Jika ada data, tampilkan respons JSON dengan data
            return response()->json(['data' => $responseArray, 'jmlData' => $totalData]);
        } else {
            // Jika tidak ada data, tampilkan pesan error
            return response()->json(['status' => 'error', 'message' => 'Data Kosong'], 404);
        }
    }

    public function getRekeningLama(Request $request)
    {
        try {
            $rekLama = $request->RekeningLama;
            $data = Tabungan::where('RekeningLama', $rekLama)->first();
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function getRekening(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $data = DB::table('tabungan as t')
                ->select('t.GolonganTabungan', 't.SaldoAkhir', 't.StatusBlokir', 'r.Nama', 'r.Alamat', 'r.Telepon', 'g.Keterangan')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 't.Kode')
                ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
                ->where('t.Rekening', $rekening)
                ->first();
            if ($data) {
                $statusBlokir = $data->StatusBlokir;
                $nama = $data->Nama;
                $alamat = $data->Alamat;
                $telepon = $data->Telepon;
                $golTabungan = $data->GolonganTabungan;
                $namaGolTabungan = $data->Keterangan;
                $saldoAwal = $data->SaldoAkhir;
                $keterangan = 'Blokir Tab. ' . '[' . $rekening . '] ' . $nama;
                if ($statusBlokir == '1') {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Rekening Tabungan Sudah Diblokir Sebagian!']
                    );
                }
                if ($statusBlokir == '2') {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Rekening Tabungan Sudah Diblokir Semua!']
                    );
                }
                $result = [
                    "Nama" => $nama,
                    "Alamat" => $alamat,
                    "Telepon" => $telepon,
                    "GolonganTabungan" => $golTabungan,
                    "NamaGolonganTabungan" => $namaGolTabungan,
                    "SaldoAwal" => $saldoAwal,
                    "KeteranganBlokir" => $keterangan
                ];
            }
            return response()->json($result);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        $rekening = $request->Rekening;
        $jumlahBlokir = $request->JumlahBlokir;
        $data = Tabungan::where('Rekening', '=', $rekening)->first();
        if ($data) {
            $result = [
                "StatusBlokir" => ($jumlahBlokir === 0) ? 0 : $request->StatusBlokir,
                "JumlahBlokir" => $jumlahBlokir,
                "KeteranganBlokir" => $request->KeteranganBlokir,
                "TglBlokir" => GetterSetter::getTglTransaksi()
            ];
            Tabungan::where('Rekening', '=', $rekening)->update($result);
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'No. Rekening Tidak Valid!'],
                404
            );
        }
    }
}
