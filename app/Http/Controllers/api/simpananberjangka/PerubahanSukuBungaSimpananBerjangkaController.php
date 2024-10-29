<?php

namespace App\Http\Controllers\api\simpananberjangka;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\simpananberjangka\Deposito;
use App\Models\simpananberjangka\DepositoSukuBunga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerubahanSukuBungaSimpananBerjangkaController extends Controller
{
    public function data(Request $request)
    {
        try {
            $limit = 10;
            $tglAwal = $request->TglAwal;
            $tglAkhir = $request->TglAkhir;
            $data = DB::table('deposito_sukubunga as s')
                ->select('s.tgl AS Tgl', 's.Rekening', 'r.Nama', 'd.SukuBunga', 's.SukuBunga AS SukuBungaBaru', 's.JthTmp', 's.Keterangan')
                ->leftJoin('deposito as d', 'd.Rekening', '=', 's.Rekening')
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode');
            if (!$tglAwal && !$tglAkhir) {
                return response()->json([]);
            }
            if ($tglAwal !== null && $tglAkhir !== null) {
                $data->whereBetween('s.Tgl', [$tglAwal, $tglAkhir]);
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
            return response($data);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }

    public function getRekening(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $data =
                DB::table('deposito AS d')
                ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
                ->leftJoin('golongandeposito AS g', 'g.Kode', '=', 'd.GolonganDeposito')
                ->where('d.Rekening', '=', $rekening)
                ->select('r.Nama', 'r.Alamat', 'r.Telepon', 'd.Tgl', 'd.JthTmp', 'g.Keterangan', 'd.Nominal', 'g.Lama', 'd.SukuBunga', 'd.StatusBlokir', 'd.GolonganDeposito')
                ->orderBy('d.Tgl')
                ->first();
            if ($data) {
                $sukuBunga = $data->SukuBunga;
                if ($data->StatusBlokir == 'Y') {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Rekening Simpanan Berjangka Di Blokir!']
                    );
                } else {
                    $exists = DepositoSukuBunga::where('Rekening', $rekening)
                        ->exists();
                    if ($exists) {
                        $data2 =
                            DB::table('deposito_sukubunga')
                            ->where('Rekening', $rekening)
                            ->select('SukuBunga')
                            ->orderByDesc('ID')
                            ->first();
                        if ($data2) {
                            $sukuBunga = $data2->SukuBunga;
                        }
                    }
                    $array = [
                        'NamaDeposan' => $data->Nama,
                        'Alamat' => $data->Alamat,
                        'Telepon' => $data->Telepon,
                        'TglValuta' => $data->Tgl,
                        'JatuhTempo' => $data->JthTmp,
                        // 'GolDeposan' => $data->GolonganDeposan,
                        // 'KetGolDeposan' => $data->goldeposan->Keterangan,
                        'GolDeposito' => $data->GolonganDeposito,
                        'KetGolDeposito' => $data->Keterangan,
                        'JangkaWaktu' => $data->Lama,
                        'NominalDeposito' => $data->Nominal,
                        'BungaLama' => $sukuBunga
                    ];
                }
                return response($array);
            } else {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Simpanan Berjangka Tidak Ditemukan!']
                );
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        try {
            // Cek rekening deposito
            
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $rekening = $request->Rekening;
            $tgl = GetterSetter::getTglTransaksi();
            $exists = DepositoSukuBunga::where('Rekening', $rekening)
                ->exists();
            if ($exists) {
                $data =
                    DB::table('deposito AS d')
                    ->where('d.rekening', $rekening)
                    ->select('d.Tgl', 'd.JthTmp', 'd.SukuBunga', 'd.NoBilyet')
                    ->first();
                if (!$data) {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Rekening Simpanan Berjangka Tidak Ditemukan!']
                    );
                } else {
                    $array = [
                        'TglTransaksi' => $tgl,
                        'tgl' => $data->Tgl,
                        'JTHTMP' => $data->JthTmp,
                        'Rekening' => $rekening,
                        'Sukubunga' => $request->BungaBaru,
                        'BungaLama' => $data->SukuBunga,
                        'Keterangan' => $request->Keterangan,
                        'UserName' => $cUser, // GET CONFIG
                        'DateTime' => Carbon::now()
                    ];
                    DepositoSukuBunga::where('Rekening', $rekening)
                        ->where('Tgl', $data->Tgl)
                        ->update($array);
                }
            } else {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Simpanan Berjangka Tidak Ditemukan!']
                );
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
