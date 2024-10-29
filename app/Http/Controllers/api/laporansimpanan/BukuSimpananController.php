<?php

namespace App\Http\Controllers\api\laporansimpanan;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\MutasiTabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BukuSimpananController extends Controller
{
    public function data(Request $request)
    {
        $rekening = $request->Rekening;
        $tglAwal = $request->TglAwal;
        $tglAkhir = $request->TglAkhir;
        $saldoAwal = 0;
        $data = MutasiTabungan::select(DB::raw('IFNULL(SUM(Kredit-Debet),0) as SaldoAwal'))
            ->where('Rekening', $rekening)
            ->where('Tgl', '<', $tglAwal)
            ->first();
        if ($data) {
            $saldoAwal = $data->SaldoAwal;
        } else {
            $saldoAwal = 0;
        }

        $vaArray = [];

        // Inisialisasi data saldo awal
        $vaArray[] = [
            'No' => 1,
            'Tanggal' => '',
            'Faktur' => '',
            'Sandi' => '',
            'Keterangan' => 'Saldo Awal',
            'Debet' => '',
            'Kredit' => '',
            'SaldoAkhir' => $saldoAwal,
            'DC' => '',
            'User' => ''
        ];
        $data2
            = DB::table('mutasitabungan')
            ->select('Faktur', 'Tgl', 'KodeTransaksi', 'Keterangan', 'Debet', 'Kredit', 'UserName')
            ->where('rekening', $rekening)
            ->where('tgl', '>=', $tglAwal)
            ->where('tgl', '<=', $tglAkhir)
            ->orderBy('tgl')
            ->orderBy('id')
            ->get();

        $row = 1;
        $saldoAkhir = $saldoAwal;
        $totalDebet = 0;
        $totalKredit = 0;
        $cif = '';
        $nama = '';
        $rekening = '';
        $atasNama = '';
        $alamat = '';

        foreach ($data2 as $d2) {
            $saldoAkhir += $d2->Kredit - $d2->Debet;
            $totalDebet += $d2->Debet;
            $totalKredit += $d2->Kredit;
            $DC = $saldoAkhir < 0 ? "D" : "C";
            $DC = $d2->Kredit > 0 ? "C" : "D";
            ++$row;

            // Tambahkan data transaksi ke dalam array
            $vaArray[] = [
                'No' => $row,
                'Tanggal' => $d2->Tgl,
                'Faktur' => $d2->Faktur,
                'Sandi' => $d2->KodeTransaksi,
                'Keterangan' => $d2->Keterangan,
                'Debet' => $d2->Debet,
                'Kredit' => $d2->Kredit,
                'SaldoAkhir' => $saldoAkhir,
                'DC' => $DC,
                'User' => $d2->UserName
            ];
        }

        // Gabungkan data saldo awal ke data akhir dalam satu variabel
        $result = [
            'data' => [
                'CIF' => GetterSetter::getKode($request->Rekening),
                'Nama' => GetterSetter::getNamaRegisterNasabah($request->Rekening),
                'Rekening' => $request->Rekening,
                'Alamat' => GetterSetter::getAlamatRegisterNasabah($request->Rekening),
                'TotalDebet' => $totalDebet,
                'TotalKredit' => $totalKredit,
                'SaldoAwal' => $saldoAwal,
            ],
            'mutasi' => $vaArray // Simpan data transaksi dalam array "mutasi"
        ];
        return $result;
    }
}
