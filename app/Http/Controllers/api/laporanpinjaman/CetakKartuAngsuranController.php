<?php

namespace App\Http\Controllers\api\laporanpinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Debitur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakKartuAngsuranController extends Controller
{
    public function getRekening(Request $request)
    {
        $rekening = $request->Rekening;
        $data = DB::table('debitur as d')
            ->select('r.Nama', 'r.Alamat', 'd.Tgl')
            ->leftJoin('registernasabah as r', 'r.kode', '=', 'd.kode')
            ->where('d.rekening', '=', $rekening)
            ->first();
        if ($data) {
            $array = [
                'Nama' => $data->Nama,
                'Alamat' => $data->Alamat
            ];
        } else {
            return response()->json(['status' => 'error', 'message' => 'Rekening Tidak Terdaftar!'], 404);
        }
        return response()->json($array);
    }

    public function data(Request $request)
    {
        $rekening = $request->Rekening;
        $tgl = $request->Tgl;
        $vaKolek = ['Lancar', 'DPK', 'Kurang Lancar', 'Diragukan', 'Macet'];
        $data =  DB::table('debitur AS d')
            ->leftJoin('angsuran AS a', function ($join) use ($tgl) {
                $join->on('a.Rekening', '=', 'd.Rekening')
                    ->where('a.Tgl', '<=', $tgl);
            })
            ->where('d.rekening', $rekening)
            ->select(
                'd.Plafond',
                'd.Kode',
                'd.Lama',
                'd.NoSpk',
                'd.SukuBunga',
                'd.Tgl',
                'd.CaraPerhitungan',
                DB::raw('IFNULL(SUM(a.kpokok), 0) AS PembayaranPokok'),
                DB::raw('IFNULL(SUM(a.kbunga), 0) AS PembayaranBunga'),
                DB::raw('IFNULL(SUM(a.krra), 0) AS RRA')
            )
            ->groupBy(
                'd.Rekening'
            )
            ->first();

        $result = [];

        if ($data) {
            $tglRealisasi = $data->Tgl;
            $vaPembayaranKredit = GetterSetter::getTotalPembayaranKredit($rekening, $tgl);
            $pembayaranPokok = $vaPembayaranKredit['PembayaranPokok'];
            $pembayaranBunga = $vaPembayaranKredit['PembayaranPokok'] + $data->RRA;
            $vaKol = GetterSetter::getTunggakan($rekening, $tgl);
            $jthtmp = Carbon::parse($data->Tgl)->addMonths($data->Lama)->format('Y-m-d');

            $data2 = DB::table('agunan AS a')
                ->leftJoin('pengajuankredit AS r', 'r.Jaminan', '=', 'a.Rekening')
                ->leftJoin('debitur AS d', 'd.NoPengajuan', '=', 'r.Rekening')
                ->select('a.Rekening', 'a.No', 'a.Jaminan')
                ->where('d.Rekening', '=', $rekening)
                ->get();
            foreach ($data2 as $d2) {
                $va = GetterSetter::getDetailJaminan($d2->Rekening, $d2->No, $d2->Jaminan, $tgl);
                $detailJaminan = "";
                foreach ($va as $k => $v) {
                    foreach ($v as $key => $value) {
                        $cKeterangan = $key . " : " . $value . ", ";
                        if ($value == "") {
                            $cKeterangan = "";
                        }
                        $detailJaminan .= $cKeterangan;
                    }
                }
                $detailJaminan = substr($detailJaminan, 8);
                $vaDetail[$d2->No] = array("Judul" => $detailJaminan);
            }
            $bunga = round($data->Plafond * $data->SukuBunga / 100 / 12 * $data->Lama, 0);

            $array = [
                'NoRekening' => $rekening,
                'NamaDebitur' => GetterSetter::getKeterangan($data->Kode, 'Nama', 'registernasabah'),
                'Alamat' => GetterSetter::getKeterangan($data->Kode, 'Alamat', 'registernasabah'),
                'NoSPK' => $data->NoSpk,
                // 'Kolektebilitas' => $vaKol['Kol_Awal'] . ' - ' . $vaKolek[$vaKol['Kol_Awal']],
                // 'FRTunggakan' => $vaKol['FR_Awal'] . ' Bulan',
                'Plafond' => $data->Plafond,
                'TglRealisasi' => $data->Tgl,
                'LamaAngsuran' => $data->Lama,
                'JatuhTempo' => $jthtmp,
                'SukuBunga' => $data->SukuBunga . '% per Tahun',
                // 'TunggakanPokok' => $vaKol['T_Pokok_Awal'],
                // 'TunggakanBunga' => $vaKol['T_Bunga_Awal']
            ];

            $result['data'] = $array;
        }

        $TBunga = "";
        $faktur = "";
        $data3 = DB::table('debitur AS d')
            ->select('d.Rekening', 'd.Faktur', 'd.Lama')
            ->where('Rekening', $rekening)
            ->first();
        if ($data3) {
            $TBunga = GetterSetter::getAngsuranBunga($data3->Rekening, 1) * $data3->Lama;
            $faktur = $data3->Faktur;
        }
        $data4
            = DB::table('angsuran as a')
            ->leftJoin('debitur as d', 'd.rekening', '=', 'a.rekening')
            ->select(
                'a.Rekening',
                'a.Faktur',
                'a.Tgl',
                'a.DPokok',
                'a.KPokok',
                'a.DBunga',
                DB::raw('(a.KBunga + a.KRRA + a.KBungaRK) as KBunga'),
                'a.Denda',
                'a.Keterangan',
                'd.Lama'
            )
            ->where('a.rekening', '=', $rekening)
            ->orderBy('a.tgl')
            ->orderBy('a.id')
            ->get();

        $row = 0;
        $totalDPokok = 0;
        $totalKPokok = 0;
        $bakiDebet = 0;
        $totalBunga = 0;
        $tunggakanBunga = 0;
        $totalAngsuran = 0;
        $tglAkhirAngsuran = Carbon::parse($tgl);
        $sisaJasa = 0;
        $jasaAwal = 0;
        $ke = 0;
        $bakiJasa = 0;
        foreach ($data4 as $d4) {
            $tglAkhirAngsuran = $d4->Tgl;
            $totalDPokok += $d4->DPokok;
            $totalKPokok += $d4->KPokok;
            $pokokAwal = $bakiDebet;
            $bakiDebet += $d4->DPokok - $d4->KPokok;
            if ($d4->Faktur == $faktur) {
                $jasaAwal = $TBunga;
            } else {
                $jasaAwal = 0;
            }
            $bakiJasa += $jasaAwal - $d4->KBunga;
            $totalBunga += $d4->KBunga;
            $totalAngsuran = $d4->KPokok + $d4->KBunga + $d4->Denda;
            $vaArray = [
                'No' => ++$row,
                'Tgl' => $d4->Tgl,
                'Keterangan' => $d4->Keterangan,
                'Pokok' => $d4->KPokok,
                'Bunga' => $d4->KBunga,
                'Denda' => $d4->Denda,
                'TAngsuran' => $totalAngsuran,
                'BakiDebet' => $bakiDebet
            ];
            $result['detail'][] = $vaArray;
        }
        return $result;
    }
}
