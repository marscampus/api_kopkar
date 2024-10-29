<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\Username;
use App\Models\pinjaman\Agunan;
use App\Models\pinjaman\Debitur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengambilanJaminanController extends Controller
{
    public function getRekening(Request $request)
    {
        $tgl = GetterSetter::getTglTransaksi();
        $rekening = $request->Rekening;
        $cHaving = "BakiDebet = 0 and TglLunas <= '$tgl'";
        $keteranganDetailJaminan = '';

        $data = DB::table('debitur as d')
            ->select([
                'd.Rekening',
                'd.RekeningLama',
                'd.Tgl',
                'r.Nama',
                'r.Alamat',
                DB::raw('SUM(a.dpokok - a.kpokok) as BakiDebet'),
                'd.Plafond',
                'd.Lama',
                DB::raw('(SELECT MAX(tgl) FROM angsuran a WHERE a.Rekening = d.rekening AND a.kpokok > 0) as TglLunas'),
                'd.AO',
                'd.TglAmbilJaminan',
                'd.UserNameAmbilJaminan',
                'o.Nama as NamaAO',
                'd.RekeningTabungan',
            ])
            ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.kode')
            ->leftJoin('ao as o', 'o.kode', '=', 'd.ao')
            ->leftJoin('cabang as c', 'c.kode', '=', 'd.cabangentry')
            ->leftJoin('angsuran as a', function ($join) use ($tgl) {
                $join->on('a.rekening', '=', 'd.rekening')
                    ->where('a.tgl', '<=', $tgl);
            })
            ->where('d.rekening', $rekening)
            ->where('d.tgl', '<=', $tgl)
            ->groupBy(
                'd.Rekening',
                'd.RekeningLama',
                'd.Tgl',
                'r.Nama',
                'r.Alamat',
                'd.Plafond',
                'd.Lama',
                'd.AO',
                'd.TglAmbilJaminan',
                'd.UserNameAmbilJaminan',
                'o.Nama',
                'd.RekeningTabungan'
            )
            ->havingRaw('BakiDebet = 0')
            ->havingRaw('(SELECT MAX(tgl) FROM angsuran a WHERE a.Rekening = d.rekening AND a.kpokok > 0) <= ?', ['2023-09-06'])
            ->orderBy('TglLunas')
            ->first();
        if ($data) {
            $data2 = Agunan::with('debitur')
                ->where('Rekening', $rekening)
                ->orderByDesc('No')
                ->get();
            foreach ($data2 as $d2) {
                $keteranganDetailJaminan = $d2->Rekening;
                $vaDetail = GetterSetter::getDetailJaminan($d2->rekening, $d2->No, $d2->Jaminan,$data->Tgl);
                $DetailJaminan = '';
                foreach ($vaDetail as $k => $va) {
                    foreach ($va as $key => $value) {
                        dd($va);
                        if (!empty($value)) {
                            $cKey = $key . " : " . $value;
                            $key = trim($key);
                            if (empty($key)) {
                                $cKey = $value;
                            }
                            $DetailJaminan .= $cKey . ", ";
                        }
                    }
                }

                $DetailJaminan = substr($DetailJaminan, 0, -2) . ".";
                $keteranganDetailJaminan .= "Nomor : " . $d2['No'] . "\n" . $DetailJaminan . "\n--------------------------------------------------------\n";
                // $DetailJaminan .= $keteranganDetailJaminan;
            }
            $tglAmbilJaminan = $data->TglAmbilJaminan;
            if ($tglAmbilJaminan <= $tgl) {
                return response()->json(
                    ['status' => 'error', 'message' => 'Jaminan Sudah Diambil pada Tanggal ' . $tglAmbilJaminan . '!']
                );
            }
        } else {
            $bakiDebet = GetterSetter::getBakiDebet($rekening, $tgl);
            if ($bakiDebet > 0) {
                return response()->json(
                    ['status' => 'error', 'message' => 'Kredit Belum Lunas, Jaminan Tidak Bisa Diambil!']
                );
            } else {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Terdaftar!']
                );
            }
        }
        $array = [
            'Nama' => $data->Nama,
            'Alamat' => $data->Alamat,
            'TglRealisasi' => $data->Tgl,
            'Plafond' => $data->Plafond,
            'TglLunas' => $data->TglLunas,
            'DetailJaminan' => $keteranganDetailJaminan
        ];
        return response()->json($array);
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $tgl = $request->Tgl;
            if (empty($cUser)) {
                return response()->json(
                    ['status' => 'error', 'message' => 'User Harus Diisi!']
                );
            } else {
                // $data = Username::where('UserName', $username)
                //     ->where('Plafond', '>', 1000000)
                //     ->first();
                // if ($data) {
                //     $cUserPassword = $data['UserPassword'];
                //     $cUserPasswordACC = substr($cUserPassword, 0, 10) . substr($cUserPassword, 14);
                //     $cPasswordACC = md5(strtoupper($request->PasswordACC));

                //     if ($cPasswordACC !== $cUserPasswordACC) {
                //         return response()->json(
                //             ['status' => 'error', 'message' => 'Password User Salah!'],
                //             404
                //         );
                //     }
                // }
            }
            $array = [
                'TglAmbilJaminan' => $tgl,
                'UserNameAmbilJaminan' => $cUser,
                'UserACCambilJaminan' => $cUser,
                'DateTimeAmbilJaminan' => Carbon::now(),
                'Keteranganambiljaminan' => $request->Keterangan
            ];
            Debitur::where('Rekening', $request->Rekening)->update($array);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
