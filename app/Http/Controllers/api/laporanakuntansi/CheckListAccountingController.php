<?php

namespace App\Http\Controllers\api\laporanakuntansi;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\GolonganSimpananBerjangka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckListAccountingController extends Controller
{
    public function data(Request $request)
    {
        $tgl = $request->Tgl;
        $vaArray = [];
        // ARRAY SIMPANAN
        $vaArray[1] = [
            'No' => '1',
            'Keterangan' => '<b>Simpanan</b>',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data = DB::table('tabungan as t')
            ->leftJoin('golongantabungan as g', 'g.Kode', '=', 't.GolonganTabungan')
            ->leftJoin('rekening as r', 'r.Kode', '=', 'g.Rekening')
            ->leftJoin('mutasitabungan as m', function ($join) use ($tgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $tgl);
            })
            ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
            ->where('t.tgl', '<=', $tgl)
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.Rekening as RekeningAkuntansi', 'r.Keterangan as NamaRekening', DB::raw('ifnull(sum(m.kredit-m.debet),0) as Saldo'))
            ->get();
        $row = 0;
        $kreditTabungan = 0;
        $debetTabungan = 0;
        foreach ($data as $d) {
            if ($d->Saldo > 0) {
                $key = "T" . $d->RekeningAkuntansi;
                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($tgl, $d->RekeningAkuntansi);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d->RekeningAkuntansi . ' - ' . $d->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditTabungan += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d->Saldo;
                $debetTabungan += $d->Saldo;
            }
        }
        $vaArray[29] = [
            'No' => '',
            'Keterangan' => '<b>TOTAL SIMPANAN</b>',
            'SaldoNominatif' => $debetTabungan,
            'SaldoNeraca' => $kreditTabungan,
            'Selisih' => $debetTabungan - $kreditTabungan
        ];

        // ARRAY DEPOSITO
        $vaArray[2] = [
            'No' => '2',
            'Keterangan' => '<b>Simpanan Berjangka</b>',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data2 = DB::table('deposito as t')
            ->leftJoin('golongandeposito as g', 'g.Kode', '=', 't.GolonganDeposito')
            ->leftJoin('rekening as r', 'r.Kode', '=', 'g.RekeningAkuntansi')
            ->leftJoin('mutasideposito as m', function ($join) use ($tgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $tgl);
            })
            ->leftJoin('cabang as c', 'c.Kode', '=', 't.CabangEntry')
            ->where('t.tgl', '<=', $tgl)
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.RekeningAkuntansi', DB::raw('IFNULL(SUM(m.SetoranPlafond - m.PencairanPlafond), 0) AS Saldo'), 'r.Keterangan as NamaRekening')
            ->get();
        $row = 0;
        $kreditDeposito = 0;
        $debetDeposito = 0;
        foreach ($data2 as $d2) {
            if ($d2->Saldo > 0) {
                $rekening = $d2->Rekening;
                $golongan = GetterSetter::getGolonganDepositoPeriode($rekening, $tgl);
                $data3 = GolonganSimpananBerjangka::where('Kode', $golongan)->first();
                if ($data3) {
                    $d2->RekeningAkuntansi = $data3->RekeningAkuntansi;
                }
                $key = 'D' . $d2->RekeningAkuntansi;

                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($tgl, $d2->RekeningAkuntansi);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d2->RekeningAkuntansi . ' - ' . $d2->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditDeposito += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d2->Saldo;
                $debetDeposito += $d2->Saldo;
            }
        }
        $vaArray[30] = [
            'No' => '',
            'Keterangan' => '<b>TOTAL SIMPANAN BERJANGKA</b>',
            'SaldoNominatif' => $debetDeposito,
            'SaldoNeraca' => $kreditDeposito,
            'Selisih' => $debetDeposito - $kreditDeposito
        ];

        // ARRAY KREDIT
        $debetKredit = 0;
        $kreditKredit = 0;
        $vaArray[3] = [
            'No' => '3',
            'Keterangan' => '<b>Pinjaman</b>',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $data4 = DB::table('debitur as t')
            ->leftJoin('rekening as r', 'r.Kode', '=', DB::raw('IFNULL((SELECT GolonganKredit_Baru FROM debitur_golongankredit WHERE Rekening = t.Rekening AND tgl <= "' . $tgl . '" ORDER BY tgl DESC LIMIT 1), t.GolonganKredit)'))
            ->leftJoin('debitur_cabang as dc', 'dc.Rekening', '=', 't.Rekening')
            ->leftJoin('cabang as c', 'c.Kode', '=', DB::raw('IFNULL((SELECT CabangEntry FROM debitur_cabang WHERE Rekening = t.Rekening AND tgl <= "' . $tgl . '" ORDER BY tgl DESC LIMIT 1), t.CabangEntry)'))
            ->leftJoin('angsuran as m', function ($join) use ($tgl) {
                $join->on('m.rekening', '=', 't.Rekening')
                    ->where('m.Tgl', '<=', $tgl)
                    ->where('t.tglwriteoff', '>', $tgl);
            })
            ->leftJoin('golongankredit as g', 'g.Kode', '=', DB::raw('IFNULL((SELECT GolonganKredit_Baru FROM debitur_golongankredit WHERE Rekening = t.Rekening AND tgl <= "' . $tgl . '" ORDER BY tgl DESC LIMIT 1), t.GolonganKredit)'))
            ->where('t.tgl', '<=', $tgl)
            ->where('t.offbalance', '=', 0)
            ->groupBy('g.kode')
            ->orderBy('g.kode')
            ->select('t.Rekening', 'g.Rekening as RekeningAkuntansi', 'r.Keterangan as NamaRekening', DB::raw('IFNULL(SUM(m.DPokok - m.KPokok), 0) AS Saldo'))
            ->get();
        $row = 0;
        foreach ($data4 as $d4) {
            if ($d4->Saldo > 0) {
                $key = 'K' . $d4->RekeningAkuntansi;
                if (!isset($vaArray[$key])) {
                    $tabunganNeraca = GetterSetter::getSaldoCekList($tgl, $d4->RekeningAkuntansi);
                    $vaArray[$key] = [
                        'No' => '',
                        'Keterangan' => $d4->RekeningAkuntansi . ' - ' . $d4->NamaRekening,
                        'SaldoNominatif' => 0,
                        'SaldoNeraca' => $tabunganNeraca,
                        'Selisih' => 0
                    ];
                    $kreditKredit += $tabunganNeraca;
                }
                $vaArray[$key]['SaldoNominatif'] += $d4->Saldo;
                $debetKredit += $d4->Saldo;
            }
        }
        $vaArray[31] = [
            'No' => '',
            'Keterangan' => 'TOTAL KREDIT',
            'SaldoNominatif' => $debetKredit,
            'SaldoNeraca' => $kreditKredit,
            'Selisih' => $debetKredit - $kreditKredit
        ];

        // ARRAY LAIN LAIN
        $vaArray[4] = [
            'No' => '4',
            'Keterangan' => 'Lain-Lain',
            'SaldoNominatif' => 0,
            'SaldoNeraca' => 0,
            'Selisih' => 0
        ];
        $PBLeadger = 0;
        $PBNeraca = GetterSetter::getSaldoCekList($tgl, GetterSetter::getDBConfig('msRekeningPB'));
        $vaArray['PB'] = [
            'No' => '',
            'Keterangan' => '4.1 Pemindah Bukuan',
            'SaldoNominatif' => $PBLeadger,
            'SaldoNeraca' => $PBNeraca,
            'Selisih' => $PBLeadger - $PBNeraca
        ];
        foreach ($vaArray as $key => $value) {
            $vaArray[$key]['Selisih'] = $value['SaldoNominatif'] - $value['SaldoNeraca'];
            $vaArray[$key]['SaldoNominatif'] = $vaArray[$key]['SaldoNominatif'];
            $vaArray[$key]['SaldoNeraca'] = $vaArray[$key]['SaldoNeraca'];
            $vaArray[$key]['Selisih'] = $vaArray[$key]['Selisih'];
        }

        $responseArray = [
            'data' => $vaArray
        ];

        return $responseArray;
    }
}
