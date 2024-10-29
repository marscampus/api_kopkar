<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganTabungan;
use App\Http\Controllers\Controller;
use App\Models\fun\Angsuran;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\pinjaman\Agunan;
use App\Models\pinjaman\Debitur;
use App\Models\Pinjaman\JadwalAngsuran;
use App\Models\Pinjaman\PengajuanKredit;
use App\Models\simpanan\Tabungan;
use Illuminate\Http\Request;

class PembatalanPencairanPinjamanController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $noPencairan = '';
            $data = Debitur::with('registernasabah')
                ->where('Rekening', $rekening)->first();
            if ($data) {
                // Cek status
                if ($data->StatusPencairan == '0') {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Pinjaman Belum Pernah Dicairkan atau Sudah Dibatalkan!'],
                        404
                    );
                }
                // Apabila sudah diangsur maka tidak dapat dihapus
                $faktur = $data->Faktur;
                $tglRealisasi = $data->Tgl;
                $data2 = Angsuran::where('Rekening', $rekening)
                    ->where('Status', '5')
                    ->where('Tgl', '>', $tglRealisasi)
                    ->first();
                if ($data2) {
                    return response()->json(
                        ['status' => 'error', 'message' => 'Kredit Sudah Pernah Diangsur, Tidak Dapat Dihapus!'],
                        404
                    );
                }
                // No. Pencairan
                $faktur = 'R%';
                $data3 = Angsuran::where('Faktur', 'LIKE', $faktur)
                    ->where('Rekening', $rekening)
                    ->first();
                if ($data3) {
                    $noPencairan = $data3->Faktur;
                }
                // Atas Nama dan Saldo Tabungan dari Rekening Tabungan
                $rekTabungan = $data->RekeningTabungan;
                if (empty($rekTabungan)) {
                } else {
                    $data4 = Tabungan::where('Rekening', $rekTabungan)->first();
                    if ($data4) {
                        $atasNama = $data4->NamaNasabah;
                        $saldoAkhir = $data4->SaldoAkhir;
                    }
                }
                $pencairan = $data->Plafond - $data->Administrasi + $data->Notaris + $data->PencairanPokok + $data->Materai + $data->Asuransi + $data->Provisi + $data->BiayaTaksasi + $data->Lainnya;
                $array = [
                    'NamaNasabah' => $data->registernasabah->Nama,
                    'AlamatNasabah' => $data->registernasabah->Alamat,
                    'NoPencairan' => $noPencairan,
                    'Plafond' => $data->Plafond,
                    'PencairanPokok' => $data->PencairanPokok,
                    'Administrasi' => $data->Administrasi,
                    'Notaris' => $data->Notaris,
                    'Materai' => $data->Materai,
                    'Asuransi' => $data->Asuransi,
                    'Provisi' => $data->Provisi,
                    'Taksasi' => $data->BiayaTaksasi,
                    'Lainnya' => $data->Lainnya,
                    'Pencairan' => $pencairan,
                    'RekTabungan' => isset($rekTabungan) ? $rekTabungan : '',
                    'AtasNama' => isset($atasNama) ? $atasNama : '',
                    'SaldoTabungan' => isset($saldoAkhir) ? $saldoAkhir : '',
                    'Keterangan' => 'PEMBATALAN PENCAIRAN KREDIT'
                ];
            } else {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Terdaftar!'],
                    404
                );
            }
            return response($array);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            // Cek rekening
            $data = Debitur::where('Rekening', $rekening)->first();
            if (!$data) {
                return response()->json(
                    ['status' => 'error', 'message' => 'Rekening Pinjaman Tidak Valid!'],
                    404
                );
            }
            $tglTransaksi = GetterSetter::getTglTransaksi();
            $rekTabungan = $request->RekTabungan;
            $saldoTabungan = PerhitunganTabungan::getSaldoTabungan($rekTabungan, $tglTransaksi);
            if ($saldoTabungan < $request->Plafond) {
                $data = Debitur::where('Rekening', $rekening)->get();
                foreach ($data as $d) {
                    $faktur = $d->Faktur;
                    MutasiTabungan::where('Faktur', $faktur)->delete();
                    Angsuran::where('Faktur', $faktur)->delete();
                    MutasiDeposito::where('Faktur', $faktur)->delete();
                    $array = [
                        'Faktur' => '',
                        'StatusPencairan' => '0'
                    ];
                    Debitur::where('Rekening', $rekening)->update($array);
                    $data2 = Debitur::where('Rekening', $rekening)->first();
                    if ($data2) {
                        $rekPengajuan = $data2->NoPengajuan;
                        $array = ['StatusPengajuan' => '0'];
                        PengajuanKredit::where('Rekening', $rekPengajuan)->update($array);
                        $rekJaminan = $data2->RekeningJaminan;
                        $array2 = ['StatusPencairan' => '0'];
                        Agunan::where('Rekening', $rekJaminan)->update($array2);
                        JadwalAngsuran::where('Rekening', $rekening)->delete();
                    }
                }
            } else {
                return response()->json(
                    ['status' => 'error'],
                    404
                );
            }
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
    }
}
