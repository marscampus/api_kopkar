<?php

namespace App\Http\Controllers\api\simpananberjangka;

use App\Http\Controllers\Controller;
use App\Models\master\GolonganSimpananBerjangka;
use App\Models\master\RegisterNasabah;
use App\Models\simpananberjangka\Deposito;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlokirSimpananBerjangkaController extends Controller
{
    public function data(Request $request)
    {
        try {
            $limit = 10;
            $tgl = $request->Tgl;
            $data = DB::table('deposito as d')
                ->select('d.Rekening', 'd.Kode', 'r.Nama', 'd.TglBlokir', 'd.Tgl', 'd.KeteranganBlokir')
                ->join('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->where('d.StatusBlokir', 'Y');
            if (!$tgl) {
                return response()->json([]);
            }
            if ($tgl !== null) {
                $data->where('d.TglBlokir', '<=', $tgl);
                $data->orderByDesc('d.TglBlokir');
            }
            if (!empty($request->filters)) {
                foreach ($request->filters as $filterField => $filterValue) {
                    $data->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            if ($request->page == null) {
                $data = $data->get();
            } else {
                $data = $data->paginate($limit);
            }
            return response()->json($data);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    function getRekeningLama(Request $request)
    {
        $rekLama = $request->RekeningLama;
        $query = Deposito::where('RekeningLama', $rekLama)->first();
        if ($query) {
            $rekening = $query->Rekening;
        }
        return response()->json(["Rekening" => $rekening]);
    }

    function getRekening(Request $request)
    {
        $rekening = $request->Rekening;
        $query = DB::table('deposito as d')
            ->select('d.StatusBlokir', 'd.Kode', 'd.RekeningLama', 'd.Tgl', 'd.JthTmp', 'd.GolonganDeposito', 'd.Nominal')
            ->where('d.Rekening', $rekening)->first();
        if ($query) {
            // Validasi Rekening
            $statusBlokir = $query->StatusBlokir;
            $kode = $query->Kode;
            $queryRegNasabah = DB::table('registernasabah as r')
                ->select('r.Nama', 'r.Alamat', 'r.Telepon')
                ->where('r.Kode', $kode)->first();
            if ($queryRegNasabah) {
                $nama = $queryRegNasabah->Nama;
                $alamat = $queryRegNasabah->Alamat;
                $telepon = $queryRegNasabah->Telepon;
            }
            if ($statusBlokir == 'Y') {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Sudah Diblokir!']
                );
            }
            $rekLama = $query->RekeningLama;
            $tglValuta = $query->Tgl;
            $jthTmp = $query->JthTmp;
            $golDeposit = $query->GolonganDeposito;
            $nominal = $query->Nominal;
            $queryGolDepo = DB::table('golongandeposito as d')
                ->select('d.Lama', 'd.Keterangan')
                ->where('d.Kode', $golDeposit)->first();
            if ($queryGolDepo) {
                $jangkaWaktu = $queryGolDepo->Lama;
                $ketGolDepo = $queryGolDepo->Keterangan;
            }
            $result = [
                "RekeningLama" => $rekLama,
                "Rekening" => $rekening,
                "NamaDeposan" => $nama,
                "Alamat" => $alamat,
                "Telepon" => $telepon,
                "TglValuta" => $tglValuta,
                "JthTmp" => $jthTmp,
                "GolDeposit" => $golDeposit,
                "JangkaWaktu" => $jangkaWaktu,
                "KetGolDepo" => $ketGolDepo,
                "Nominal" => $nominal
            ];
            return response()->json($result);
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'Rekening Tidak Terdaftar!']
            );
        }
    }

    public function store(Request $request)
    {
        $rekening = $request->Rekening;
        $query = Deposito::where('Rekening', $rekening)->first();
        if ($query) {
            $data = [
                'TglBlokir' => Carbon::now()->format('Y-m-d'),
                'StatusBlokir' => $request->StatusBlokir,
                'KeteranganBlokir' => $request->KeteranganBlokir
            ];
            Deposito::where('Rekening', $rekening)->update($data);
            return response()->json(
                ['status' => 'success', 'message' => 'No. Rekening Deposito Berhasil Diblokir!']
            );
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'No. Rekening Deposito Tidak Valid!']
            );
        }
    }
}
