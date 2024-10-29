<?php

namespace App\Http\Controllers\api\Pinjaman;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\pinjaman\Debitur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakSuratPinjamanController extends Controller
{
    public function getRekening1(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $data
                = DB::table('debitur')
                ->leftJoin('registernasabah', 'registernasabah.kode', '=', 'debitur.kode')
                ->where('debitur.rekening', $rekening)
                ->select(   //"registernasabah.*",
                            //"debitur.*",
                            "registernasabah.Nama as NAMA-ANGGOTA",
                            "registernasabah.TglLahir as TANGGAL-LAHIR",
                            "registernasabah.TempatLahir as TEMPAT-LAHIR",
                            "registernasabah.KTP as NOMOR-KTP",
                            "registernasabah.Alamat as ALAMAT-ANGGOTA",
                            "registernasabah.KodePos as KODE-POS",
                            "registernasabah.Telepon as NOMOR-TELPON",
                            "registernasabah.Pekerjaan as PEKERJAAN",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            // "registernasabah.Alamat as Alamat-Anggota",
                            
                            
                            "debitur.NoPK as NOMOR-PK",
                            "debitur.NoSPK as NOMOR-SPK",
                            "debitur.NoSPPK as NOMOR-SPPK",
                            "debitur.Plafond as NOMINAL-PLAFOND",
                            "debitur.Rekening as REKENING-PINJAMAN",
                            "debitur.AO as NAMA-AO",
                        )
                ->first();
            if ($data) {
                return response()->json(
                    $data
                );
            }
        } catch (\Throwable $th) {
            return response()->json(
                ['status' => 'error', 'message' => 'Rekening Tidak Ditemukan!']
            );
        }
    }

    // 10300000174

    // public function getRekening(Request $request)
    // {
    //         //  dd(GetterSetter::getDetailJaminan('103003021400001','1','6','2033-01-01',''));
    //     $dbD = DB::table('agunan as a')
    //     ->leftJoin('debitur as d', 'd.rekeningjaminan', '=', 'a.rekening')
    //     ->where('d.Rekening', '10330000090')
    //     ->orderBy('a.No')
    //     ->select('a.Rekening', 'a.No', 'a.Jaminan', 'a.Nama')
    //     ->get();

    //     $cDetail = "";
    //     $nRow = 0;

    //     // return  $dbD;
    //     if ($dbD->count() > 0) {
    //         foreach ($dbD as $Drw) {
    //             $va = GetterSetter::getDetailJaminan($Drw->Rekening, $Drw->No, $Drw->Jaminan,'2999-01-01','');
    //             // dd($va);
    //             $cJudul = "";
    //             $val = "";
    //             $cSimbol = "";
    //             $nRow++;
    //             $cJudul = "Jaminan ke-" . $nRow . PHP_EOL;
    //             $cSimbol = PHP_EOL;
    //             $val = PHP_EOL;
    //             foreach ($va as $key1 => $v) {
    //                 foreach ($v as $key => $value) {
    //                     if (!empty($value)) {
    //                         $cJudul .= $key . PHP_EOL;
    //                         $cSimbol .= ":" . PHP_EOL;
    //                         $val .= $value . PHP_EOL;
    //                         $cDetail .= $key . " : " . $value . ". " . PHP_EOL;
    //                     }
    //                 }
    //             }

    //             $vaDetailJudul[$Drw->No] = array("Judul" => $cJudul);
    //             $vaDetailIsi[$Drw->No] = array("Isi" => $val);
    //             $vaDetailSimbol[$Drw->No] = array("S" => $cSimbol);
    //             // $cDetail = strtoupper(trim($cDetail));

    //             // $cDetail = trim($cDetail);
    //             $vaDetail[$Drw->No] = array("Judul" => ($cDetail));
    //         }

    //     }

    // }

    public function getRekening(Request $request)
    {
        try {
            $rekening = $request->Rekening;
            $data = DB::table('debitur')
                ->leftJoin('registernasabah', 'registernasabah.kode', '=', 'debitur.kode')
                ->where('debitur.rekening', $rekening)
                ->select(
                    "registernasabah.Nama",
                    "registernasabah.Alamat",
                    "registernasabah.Nama as NAMA-ANGGOTA",
                    "registernasabah.TglLahir as TANGGAL-LAHIR",
                    "registernasabah.TempatLahir as TEMPAT-LAHIR",
                    "registernasabah.KTP as NOMOR-KTP",
                    "registernasabah.Alamat as ALAMAT-ANGGOTA",
                    "registernasabah.KodePos as KODE-POS",
                    "registernasabah.Telepon as NOMOR-TELPON",
                    "registernasabah.Pekerjaan as PEKERJAAN",
                    "debitur.NoPK as NOMOR-PK",
                    "debitur.NoSPK as NOMOR-SPK",
                    "debitur.NoSPPK as NOMOR-SPPK",
                    "debitur.Plafond as NOMINAL-PLAFOND",
                    "debitur.Rekening as REKENING-PINJAMAN",
                    "debitur.AO as NAMA-AO"
                )
                ->first();
        
            $nRow = 0;
            $judul = "";
            $simbol = "";
            $isi = "";
        
            if ($data) {
                $dbD = DB::table('agunan as a')
                    ->leftJoin('debitur as d', 'd.rekeningjaminan', '=', 'a.rekening')
                    ->where('d.Rekening', $rekening)
                    ->orderBy('a.No')
                    ->select('a.Rekening', 'a.No', 'a.Jaminan', 'a.Nama')
                    ->get();
        
                if ($dbD->count() > 0) {
                    foreach ($dbD as $Drw) {
                        $va = GetterSetter::getDetailJaminan($Drw->Rekening, $Drw->No, $Drw->Jaminan, '2999-01-01');
                        $nRow++;
                        $judul .= "Jaminan ke-" . $nRow . "\n";
                        $simbol .= "\n";
                        $isi .= "\n";
        
                        foreach ($va as $key1 => $v) {
                            foreach ($v as $key => $value) {
                                if (!empty($value)) {
                                    $judul .= $key . "\n";
                                    $simbol .= ":\n";
                                    $isi .= $value . "\n";
                                }
                            }
                        }

                        $judul .= "\n\n";
                        $simbol .= "\n\n";
                        $isi .= "\n\n";
                    }
                }
        
                // Menambahkan judul, simbol, dan isi ke dalam data
                $data->JUDUL = $judul;
                $data->SIMBOL = $simbol;
                $data->ISI = $isi;
        
                return response()->json($data);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Rekening Tidak Ditemukan!']);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan.']);
        }
        
        
    }
    
     
}
