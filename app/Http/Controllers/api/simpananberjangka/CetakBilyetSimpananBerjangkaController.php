<?php

namespace App\Http\Controllers\api\simpananberjangka;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\PerhitunganDeposito;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakBilyetSimpananBerjangkaController extends Controller
{
    function getRekening(Request $request)
    {
        $tgl = GetterSetter::getTglTransaksi();
        $rekening = $request->Rekening;
        $data = DB::table('deposito AS d')
            ->leftJoin('registernasabah AS r', 'r.Kode', '=', 'd.Kode')
            ->leftJoin('golongandeposan AS g', 'g.Kode', '=', 'd.GolonganDeposan')
            ->leftJoin('golongandeposito AS gd', 'gd.Kode', '=', 'd.GolonganDeposito')
            ->leftJoin('kodya AS k', 'k.Kode', '=', 'r.Kodya')
            ->leftJoin('tabungan AS t', 't.Rekening', '=', 'd.RekeningTabungan')
            ->leftJoin('jenisidentitas AS j', 'j.Kode', '=', 'r.JenisIdentitas')
            ->select(
                'r.Nama',
                'r.Alamat',
                'r.RTRW',
                'k.Keterangan AS Kodya',
                'r.KTP',
                'd.Tgl',
                'd.RekeningTabungan',
                'd.RekeningLama',
                'd.BungaDiBayar',
                'd.ID',
                'd.GolonganDeposan',
                'd.NoBilyet',
                'g.Keterangan AS NamaGolonganDeposan',
                'r.Kodya',
                'r.Kecamatan',
                'r.Kelurahan',
                'd.GolonganDeposito',
                'gd.Keterangan AS NamaGolonganDeposito',
                'gd.Lama',
                'd.SukuBunga',
                'd.ARO',
                'd.MataUang',
                'd.JthTmp',
                'd.Nominal AS Nominal',
                't.RekeningLama AS RekTabLama',
                'r.Kodya AS KdKodya',
                'r.Kecamatan',
                'r.Kelurahan',
                'j.Keterangan AS JenisIdentitas'
            )
            ->where('d.Rekening', '=', $rekening)
            ->first();
        if ($data) {
            $kelurahan = Func::SeekDaerah($data->KdKodya . '.' . $data->Kecamatan . '.' . $data->Kelurahan);
            $kecamatan = Func::SeekDaerah($data->KdKodya . '.' . $data->Kecamatan);
            $kodya = Func::SeekDaerah($data->KdKodya);
            $tglCair = Func::getJumlahHari(Carbon::parse($tgl)->format('d-m-Y'));
            $persenPajak = GetterSetter::getDBConfig('msTarifPajak');
            $bunga = $data->Nominal * $data->SukuBunga / 100 / 12;
            $pajak = $data->Nominal > 7500000 ? Func::mod50($persenPajak * $bunga / 100) : 0;
            $alamat = $data->Alamat . " " . $data->RTRW . " " . $kecamatan . " " . $kodya;
            $jthtmp = PerhitunganDeposito::getTglJthTmpDeposito($rekening, $data->Tgl);
            if ($data->RekeningTabungan == "") {
                $rekTabungan = '1'; //Tunai
            } else {
                $rekTabungan = '2'; //Masuk Tabungan
            }
            $array = [
                'Nama' => $data->Nama,
                'Alamat' => $alamat,
                'Kota' => Func::SeekDaerah($data->Kodya),
                'TglValuta' => $data->Tgl,
                'JthTmp' => $jthtmp,
                'GolDeposan' => $data->GolonganDeposan,
                'KetGolDeposan' => $data->NamaGolonganDeposan,
                'GolDeposito' => $data->GolonganDeposito,
                'KetGolDeposito' => $data->NamaGolonganDeposito,
                'JangkaWaktu' => $data->Lama,
                'TerbilangLama' => Func::Terbilang($data->Lama, false),
                'NoBilyet' => $data->NoBilyet,
                'SukuBunga' => $data->SukuBunga,
                'TerbilangSukuBunga' => Func::Terbilang($data->SukuBunga, false),
                'JenisIdentitas' => $data->JenisIdentitas,
                'NoIdentitas' => $data->KTP,
                'ID' => str_pad($data->ID, 6, '0', STR_PAD_LEFT),
                'ARO' => $data->ARO,
                'BungaDiBayar' => $data->BungaDiBayar,
                'CaraPencairan' => $rekTabungan,
                'MataUang' => $data->MataUang,
                'RekTabungan' => $data->RekeningTabungan,
                'Nominal' => $data->Nominal,
                'TerbilangNominal' => Func::Terbilang(round(Func::String2Number($data->Nominal), 0)),
                'Bunga' => $bunga,
                'BungaNetto' => $bunga - $pajak,
                'Pajak' => $pajak
            ];
        } else {
            return response()->json(['status' => 'error', 'message' => 'Rekening Tidak Valid!']);
        }

        return response()->json($array);
    }
}
