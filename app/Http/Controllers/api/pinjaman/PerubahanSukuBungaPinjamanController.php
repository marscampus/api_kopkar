<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\TglTransaksi;
use App\Models\pinjaman\Debitur;
use App\Models\pinjaman\DebiturSukuBunga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerubahanSukuBungaPinjamanController extends Controller
{
    public function data(Request $request)
    {
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $data = DebiturSukuBunga::where('tgl', '>=', $tglAwal)
            ->where('tgl', '<=', $tglAkhir)
            ->get();
        return response()->json($data);
    }
    public function getRekening(Request $request)
    {
        $rekening = $request->Rekening;
        $data = Debitur::with('registernasabah')
            ->with('sifatkredit')
            ->with('jenispenggunaan')
            ->with('sektorekonomi')
            ->with('wilayah')
            ->with('ao')
            ->with('goldebitur')
            ->with('golpenjamin')
            ->where('Rekening', $rekening)
            ->first();
        if ($data) {
            $data2 = DB::table('debitur_sukubunga')
                ->select(DB::raw('MAX(tgl) as tgl'), DB::raw('MAX(id) as id'))
                ->where('rekening', $rekening)
                ->first();
            if ($data2) {
                $tgl = $data2->tgl;
                $id = $data2->id;
                $data3 = DebiturSukuBunga::where('tgl', $tgl)
                    ->where('ID', $id)
                    ->where('Rekening', $rekening)
                    ->where('tgl', $tgl)
                    ->first();
                if ($data3) {
                    $bungaLama = $data3->Sukubunga;
                }
            }
            $array = [
                'Nama' => $data->registernasabah->Nama ?? '',
                'Alamat' => $data->registernasabah->Alamat ?? '',
                'NoPK' => $data->NoPengajuan ?? '',
                'SifatKredit' => $data->SifatKredit ?? '',
                'KetSifatKredit' => $data->sifatkredit->KETERANGAN ?? '',
                'JenisPenggunaan' => $data->JenisPenggunaan ?? '',
                'KetJenisPenggunaan' => $data->jenispenggunaan->Keterangan ?? '',
                'GolonganDebitur' => $data->GolonganDebitur ?? '',
                'KetGolDebitur' => $data->goldebitur->Keterangan ?? '',
                'SektorEkonomi' => $data->SektorEkonomi ?? '',
                'KetSektorEkonomi' => $data->sektorekonomi->Keterangan ?? '',
                'Wilayah' => $data->Wilayah ?? '',
                'KetWilayah' => $data->wilayah->Keterangan ?? '',
                'AccountOfficer' => $data->AO ?? '',
                'KetAO' => $data->ao->Nama ?? '',
                'GolPenjamin' => $data->GolonganPenjamin ?? '',
                'KetGolPenjamin' => $data->golpenjamin->Keterangan ?? '',
                'SukuBungaLama' => $bungaLama ?? '',
                'Lama' => $data->Lama ?? '',
                'Plafond' => $data->Plafond ?? ''
            ];
            return response()->json(
                $array
            );
        } else {
            return response()->json(
                ['status' => 'error', 'message' => 'No Rekening Pinjaman Tidak Terdaftar!'],
                404
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $tgl = GetterSetter::getTglTransaksi();
            // Cek rekening kredit
            $data = Debitur::where('Rekening', $rekening)->first();
            if (!$data) {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Valid!'],
                    404
                );
            }
            $array = [
                'tgl' => $tgl,
                'Rekening' => $rekening,
                'Sukubunga' => $request->SukuBunga
            ];
            DebiturSukuBunga::create($array);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }
}
