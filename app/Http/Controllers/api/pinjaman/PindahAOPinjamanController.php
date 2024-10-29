<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Debitur;
use App\Models\pinjaman\DebiturAO;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class PindahAOPinjamanController extends Controller
{
    public function getRekening(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $cRekening = $vaRequestData['Rekening'];
            $dTgl = GetterSetter::getTglTransaksi();
            $vaData = DB::table('debitur as d')
                ->select(
                    'r.Nama',
                    'r.Alamat',
                    'd.NoPengajuan',
                    'd.SifatKredit',
                    'd.JenisPenggunaan',
                    'd.GolonganDebitur',
                    'd.SektorEkonomi',
                    'd.Wilayah',
                    'd.AO',
                    'd.Plafond',
                    'd.SukuBunga',
                    'd.Lama',
                    'd.GolonganKredit',
                    'd.AOTagih',
                    DB::raw("(select cabangentry from debitur_cabang where rekening = d.rekening and tgl <= '$dTgl' 
                                  order by tgl desc limit 1 ) as cabangentry"),
                    DB::raw("(select AO_Baru from debitur_ao where rekening = d.rekening and tgl <= '$dTgl' 
                                  order by tgl desc limit 1 ) as ao")
                )
                ->leftJoin('registernasabah as r', 'r.Kode', '=', 'd.Kode')
                ->where('d.rekening', '=', $cRekening)
                ->first();

            if ($vaData) {
                if ($vaData->AOTagih == '') {
                    $vaData->AOTagih = $vaData->AO;
                }
                $vaResult = [
                    'NoPK' => $vaData->NoPengajuan,
                    'SifatKredit' => $vaData->SifatKredit,
                    'KetSifatKredit' => GetterSetter::getKeterangan($vaData->SifatKredit, 'Keterangan', 'sifatKredit'),
                    'JenisPenggunaan' => $vaData->JenisPenggunaan,
                    'KetJenisPenggunaan' => GetterSetter::getKeterangan($vaData->JenisPenggunaan, 'Keterangan', 'jenispenggunaan'),
                    'GolonganDebitur' => $vaData->GolonganDebitur,
                    'KetGolonganDebitur' => GetterSetter::getKeterangan($vaData->GolonganDebitur, 'Keterangan', 'golongandebitur'),
                    'SektorEkonomi' => $vaData->SektorEkonomi,
                    'KetSektorEkonomi' => GetterSetter::getKeterangan($vaData->SektorEkonomi, 'Keterangan', 'sektorekonomi'),
                    'Wilayah' => $vaData->Wilayah,
                    'KetWilayah' => GetterSetter::getKeterangan($vaData->Wilayah, 'Keterangan', 'wilayah'),
                    'GolKredit' => $vaData->GolonganKredit,
                    'KetGolKredit' => GetterSetter::getKeterangan($vaData->GolonganKredit, 'Keterangan', 'golongankredit'),
                    'Plafond' => $vaData->Plafond,
                    'JangkaWaktu' => $vaData->Lama,
                    'SukuBungaAngsuran' => $vaData->SukuBunga,
                    'Nama' => $vaData->Nama,
                    'AOLama' => $vaData->AOTagih,
                    'NamaAOLama' => GetterSetter::getKeterangan($vaData->AOTagih, 'Nama', 'ao'),
                    'Alamat' => $vaData->Alamat
                ];
            }
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Pindah AO', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Pindah AO', 'getRekening', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $vaArray = [];
            $cRekening = $vaRequestData['Rekening'];
            $cAOLama = $vaRequestData['AOLama'];
            $cAOBaru = $vaRequestData['AOBaru'];
            $dTgl = GetterSetter::getTglTransaksi();
            $cKeterangan = $vaRequestData['Keterangan'];
            $vaArray = [
                'Tgl' => $dTgl,
                'Rekening' => $cRekening,
                'AO_Lama' => $cAOLama,
                'AO_Baru' => $cAOBaru,
                'UserName' => $cUser,
                'DateTime' => Carbon::now(),
                'Keterangan' => $cKeterangan
            ];
            DebiturAO::where('Rekening', '=', $cRekening)
                ->update($vaArray);
            Debitur::where('Rekening', '=', $cRekening)
                ->update(['AOTagih' => $cAOBaru]);
            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Pindah AO Pinjaman', 'store', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Pindah AO', 'store', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
