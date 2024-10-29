<?php

namespace App\Helpers;

use App\Models\fun\BukuBesar;
use App\Models\fun\Config as FunConfig;
use App\Models\fun\KartuHutang;
use App\Models\fun\KartuStock;
use App\Models\fun\NomorFaktur;
use App\Models\fun\TglTransaksi;
use App\Models\fun\UrutFaktur;
use App\Models\master\PerubahanHargaStock;
use App\Models\master\Stock;
use App\Models\pembelian\PelunasanHutang;
use App\Models\pembelian\Pembelian;
use App\Models\pembelian\RtnPembelian;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotRtnPembelian;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\Configuration\Constant;
use PSpell\Config;
use Illuminate\Support\Facades\Validator;

class Constants
{
    public const KR_SALDOAWAL = "0";
    public const KR_PEMBELIAN = "1";
    public const KR_PENJUALAN = "2";
    public const KR_RETUR_PEMBELIAN = "3";
    public const KR_RETUR_PENJUALAN = "4";
    public const KR_PENJUALAN_KASIR = "5";
    public const KR_PENYESUAIAN = "6";
    public const KR_PACKING = "7";
    public const KR_MUTASISTOKDARI = "8";
    public const KR_MUTASISTOKKE = "9";
    public const KR_PELUNASAN_HUTANG = "10";
    public const KR_PELUNASAN_PIUTANG = "11";
}
class Assist
{
    public static function getLastKodeRegister($key, $len)
    {
        $valueReturn = '';
        $ID = 0;
        try {
            $query = NomorFaktur::where('KODE', str_replace(' ', '', $key))
                ->first();

            if ($query) {
                $ID = $query->ID;
                $ID++;
            } else {
                $value = str_replace(' ', '', $key);
                NomorFaktur::create(['KODE' => $value]);
                $query = NomorFaktur::where('KODE', $value)->first();

                if ($query) {
                    $ID = $query->ID;
                    $ID++;
                }
            }

            $valueReturn = (string) $ID;
            $valueReturn = str_pad($valueReturn, $len, '0', STR_PAD_LEFT);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error']);
        }

        return $valueReturn;
    }

    // UNTUK MENYIMPAN GENERATE KODE
    public static function setLastKodeRegister($kode)
    {
        try {

            $noFaktur = NomorFaktur::where('KODE', $kode)->first();
            if ($noFaktur) {
                $id = $noFaktur->ID;
                $id++;
                $noFaktur->ID = $id;
                $noFaktur->save();
            } else {
                $id = 1;
                $noFaktur = NomorFaktur::create([
                    'KODE' => $kode,
                    'ID' => $id
                ]);
            }
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    // UNTUK MENDAPATKAN TANGGAL TRANSAKSI
    public static function getTglTransaksi()
    {
        $valueReturn = "";
        $tglTransaksi = "coba";
        try {
            $query = TglTransaksi::where('Status', '0')->first();
            if ($query) {
                $tglTransaksi = $query->Tgl;
            }
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error']);
        }
        $valueReturn = (string)$tglTransaksi;

        return $valueReturn;
    }

    // UNTUK MENDAPATKAN GENERATE FAKTUR
    public static function getLastFaktur($key, $len)
    {
        try {
            $instance = new self();
            $valueReturn = "";
            $tgl = str_replace("-", "", $instance->getTglTransaksi());
            $valueReturn = $instance->getLastKodeRegister($key, $len);
            $key = str_replace(" ", "", $key) . '101' . $tgl;
            $valueReturn = $key . $valueReturn;
            return $valueReturn;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    // UNTUK MENYIMPAN GENERATE FAKTUR
    public static function setLastFaktur($key)
    {
        try {
            $result = NomorFaktur::where('KODE', $key)->first();
            if ($result) {
                $id = $result->ID;
                $id++;
                $result->ID = $id;
                $result->save();
            } else {
                $id = 1;
                $result = NomorFaktur::create([
                    'KODE' => $key,
                    'ID' => $id
                ]);
            }
        } catch (\Exception $ex) {
            // dd($ex);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    // UNTUK UPDATE REKENING PEMBELIAN
    public static function updRekeningPembelian($FAKTUR)
    {
        try {
            $instance = new self();
            $bukubesar = BukuBesar::where('FAKTUR', $FAKTUR)->delete();
            $totpembelian = TotPembelian::where('FAKTUR', $FAKTUR)->get();
            $keterangan = "Mutasi Pembelian " . $FAKTUR;
            foreach ($totpembelian as $tp) {
                if ($tp->TOTAL > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $tp->GUDANG,
                        $tp->TGL,
                        $instance->getDBConfig('KR_PERSEDIAAN'),
                        $keterangan,
                        $tp->TOTAL,
                        '0',
                        'K'
                    );
                }
                if ($tp->TUNAI > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $tp->GUDANG,
                        $tp->TGL,
                        $instance->getDBConfig('KR_KAS'),
                        $keterangan,
                        '0',
                        $tp->TUNAI,
                        'K'
                    );
                }
                if ($tp->HUTANG > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $tp->GUDANG,
                        $tp->TGL,
                        $instance->getDBConfig('KR_HUTANGDAGANG'),
                        $keterangan,
                        '0',
                        $tp->HUTANG,
                        'K'
                    );
                }
            }
            // Mengembalikan respons JSON dengan status 'success' jika berhasil
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // Mengembalikan respons JSON dengan status 'error' dan pesan kesalahan jika terjadi kesalahan
            return response()->json(['status' => 'error']);
        }
    }

    public static function getDBConfig($KEY)
    {
        try {
            $result = '';
            $query = FunConfig::where('KODE', $KEY)->first();
            if ($query) {
                $result = $query->KETERANGAN;
            }
            return $result;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function setDBConfig($KEY, $VALUE)
    {
        try {
            $result = FunConfig::where('Kode', $KEY)->first();
            if (!$result) {
                FunConfig::insert(['Kode' => $KEY]);
            }
            $where = ['Kode' => $KEY];
            $data = ['Keterangan' => $VALUE];
            FunConfig::where($where)->update($data);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function updBukuBesar(
        $FAKTUR,
        $CABANG,
        $TGL,
        $REKENING,
        $KETERANGAN,
        $DEBET,
        $KREDIT,
        $KAS
    ) {
        try {
            $instance = new self();
            $vaFaktur = $instance->getUrutFaktur($FAKTUR);
            if (empty($KAS)) {
                $KAS = "N";
            }

            if ($DEBET > 0 || $KREDIT > 0) {
                $vaInsert = [
                    'CABANG' => $CABANG,
                    'STATUS' => '1',
                    'URUT' => $vaFaktur['ID'],
                    'FAKTUR' => $FAKTUR,
                    'TGL' => $TGL,
                    'REKENING' => $REKENING,
                    'KETERANGAN' => $KETERANGAN,
                    'DEBET' => $DEBET,
                    'KREDIT' => $KREDIT,
                    'KAS' => $KAS,
                    'DATETIME' => $vaFaktur['DATETIME'],
                    'USERNAME' => $vaFaktur['USERNAME'],
                ];
                BukuBesar::create($vaInsert);
            }
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function getUrutFaktur($faktur)
    {
        $valueReturn = [
            'USERNAME' => 'ARADHEA',
            'DATETIME' => Carbon::now(),
            'ID' => '',
        ];

        // Data Setelah 12 Bulan Bisa di Hapus biar tidak terlalu besar
        $dTglAwal = now()->subMonths(12)->format('Y-m-d');
        UrutFaktur::where('tgl', '=', $dTglAwal)->delete();

        try {
            $result = UrutFaktur::select('ID', 'UserName', 'DateTime')
                ->where('Faktur', $faktur)
                ->first();

            if ($result) {
                $valueReturn['UserName'] = $result->UserName;
                $valueReturn['DateTime'] = $result->DateTime;
                $valueReturn['ID'] = $result->ID;
            } else {
                $value = [
                    'TGL' => now()->format('Y-m-d'),
                    'FAKTUR' => $faktur,
                    'DATETIME' => $valueReturn['DATETIME'],
                    'USERNAME' => $valueReturn['USERNAME'],
                ];

                UrutFaktur::insert($value);

                $result = UrutFaktur::selectRaw('IFNULL(MAX(ID), 1) as ID')
                    ->first();

                $valueReturn['ID'] = $result->ID;
            }
        } catch (\Exception $ex) {
            // Handle the exception
            return response()->json(['status' => 'error']);
        }

        return $valueReturn;
    }

    public static function updRekeningReturPembelian($FAKTUR)
    {
        try {
            $instance = new self();
            $bukubesar = BukuBesar::where('FAKTUR', $FAKTUR)->delete();
            $totrtnpembelian = TotRtnPembelian::where('FAKTUR', $FAKTUR)->get();
            $keterangan = "Mutasi Retur Pembelian " . $FAKTUR;
            foreach ($totrtnpembelian as $trp) {
                if ($trp->TOTAL > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $trp->GUDANG,
                        $trp->TGL,
                        $instance->getDBConfig('KR_PERSEDIAAN'),
                        $keterangan,
                        '0',
                        $trp->TOTAL,
                        'K'
                    );
                }
                if ($trp->TUNAI > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $trp->GUDANG,
                        $trp->TGL,
                        $instance->getDBConfig('KR_KAS'),
                        $keterangan,
                        $trp->TUNAI,
                        '0',
                        'K'
                    );
                }
                if ($trp->HUTANG > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $trp->GUDANG,
                        $trp->TGL,
                        $instance->getDBConfig('KR_HUTANGDAGANG'),
                        $keterangan,
                        $trp->HUTANG,
                        '0',
                        'K'
                    );
                }
                if ($trp->DISCOUNT > 0) {
                    $instance->updBukuBesar(
                        $FAKTUR,
                        $trp->GUDANG,
                        $trp->TGL,
                        $instance->getDBConfig('KR_BIAYAPOTPEMBELIAN'),
                        $keterangan,
                        $trp->DISCOUNT,
                        '0',
                        'K'
                    );
                }
            }
            // Mengembalikan respons JSON dengan status 'success' jika berhasil
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            // Mengembalikan respons JSON dengan status 'error' dan pesan kesalahan jika terjadi kesalahan
            return response()->json(['status' => 'error']);
        }
    }

    public static function updRekeningPelunasanHutang($FAKTUR)
    {
        try {
            $instance = new self();
            $bukubesar = BukuBesar::where('FAKTUR', $FAKTUR)->delete();
            $pelunasanhutang = PelunasanHutang::with('totpelunasanhutang.supplier')
                ->where('FAKTUR', $FAKTUR)
                ->orderBy('FAKTUR')
                ->orderBy('TGL')
                ->orderBy('ID', 'DESC')
                ->get();
            foreach ($pelunasanhutang as $ph) {
                $nHutang = $ph->BAYARFAKTUR;
                $nDiscount = $ph->DISCOUNT;
                $nBayar = $nHutang - $nDiscount;
                $dTgl = $ph->TGL;
                $dTglJthTmp = $ph->TGLFAKTUR;
                $cNamaSupplier = $ph->totpelunasanhutang->supplier->NAMA;
                $cFKT = $ph->FKT;
                $cSupplier = $ph->totpelunasanhutang->SUPPLIER;
                $cUser = $ph->USERNAME;
                $instance->updKartuHutang(
                    Constants::KR_PELUNASAN_HUTANG,
                    $FAKTUR,
                    $dTgl,
                    $cFKT,
                    $dTglJthTmp,
                    "S",
                    $cSupplier,
                    "",
                    "Pelunasan Hutang " . $FAKTUR,
                    0,
                    $nHutang
                );

                $instance->updKartuHutang(
                    Constants::KR_PELUNASAN_HUTANG,
                    $FAKTUR,
                    $dTgl,
                    $cFKT,
                    $dTglJthTmp,
                    "S",
                    $cSupplier,
                    "",
                    "Disc. Pelunasan Hutang " . $FAKTUR,
                    0,
                    $nDiscount
                );

                $instance->updBukuBesar(
                    $FAKTUR,
                    "",
                    $dTgl,
                    $instance->getDBConfig("KR_HUTANGDAGANG"),
                    "Mutasi Pembelian an. " . $cNamaSupplier . " " . $cFKT,
                    $nHutang,
                    0,
                    $cUser,
                    "K"
                );

                $instance->updBukuBesar(
                    $FAKTUR,
                    "",
                    $dTgl,
                    $instance->getDBConfig("KR_KAS"),
                    "Pelunasan Hutang an. " . $cNamaSupplier . " " . $cFKT,
                    0,
                    $nBayar,
                    $cUser,
                    "K"
                );

                $instance->updBukuBesar(
                    $FAKTUR,
                    "",
                    $dTgl,
                    $instance->getDBConfig("KR_KAS"),
                    "Disc. Pelunasan Hutang an. " . $cNamaSupplier . " " . $cFKT,
                    0,
                    $nDiscount,
                    $cUser,
                    "K"
                );
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function updKartuHutang(
        $STATUS,
        $FAKTUR,
        $TGL,
        $FKT,
        $JTHTMP,
        $SC,
        $SUPPLIER,
        $GUDANG,
        $KETERANGAN,
        $DEBET,
        $KREDIT
    ) {
        try {
            $instance = new self();
            $vaFaktur = $instance->getUrutFaktur($FAKTUR);
            if ($DEBET > 0 || $KREDIT > 0) {
                $vaInsert = [
                    'STATUS' => $STATUS,
                    'FAKTUR' => $FAKTUR,
                    'URUT' => $vaFaktur['ID'],
                    'TGL' => $TGL,
                    'GUDANG' => $GUDANG,
                    'SC' => $SC,
                    'SUPPLIER' => $SUPPLIER,
                    'KETERANGAN' => $KETERANGAN,
                    'DEBET' => $DEBET,
                    'KREDIT' => $KREDIT,
                    'FKT' => $FKT,
                    'JTHTMP' => $JTHTMP, //->isEmpty() ? '1900-01-01' : $JTHTMP,
                    'DATETIME' => $vaFaktur['DATETIME'],
                    'USERNAME' => $vaFaktur['USERNAME'],
                ];
                KartuHutang::create($vaInsert);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function updKartuStockPembelian($FAKTUR)
    {
        try {
            $instance = new self();
            $kartustock = KartuStock::where('FAKTUR', $FAKTUR)->delete();
            $pembelian = Pembelian::with('totpembelian.supplier')
                ->where('FAKTUR', $FAKTUR)
                ->get();
            foreach ($pembelian as $row) {
                $instance->updKartuStock(
                    Constants::KR_PEMBELIAN,
                    $FAKTUR,
                    $row->totpembelian->TGL,
                    $row->totpembelian->GUDANG,
                    $row->KODE,
                    $row->SATUAN,
                    $row->QTY,
                    "D",
                    "Pembelian an. " . $row->totpembelian->supplier->NAMA,
                    $row->HARGA,
                    $row->DISCOUNT,
                    $row->totpembelian->PERSDISC,
                    $row->totpembelian->PERSDISC2,
                    $row->totpembelian->PPN
                );
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function updKartuStock(
        $STATUS,
        $FAKTUR,
        $TGL,
        $GUDANG,
        $KODE,
        $SATUAN,
        $QTY,
        $DK,
        $KETERANGAN,
        $HARGA,
        $DISCITEM,
        $DISCFAKTUR1,
        $DISCFAKTUR2,
        $PPN
    ) {
        try {
            $instance = new self();
            $nIsi = 1;
            $vaSatuan = $instance->GetSatuanStock($KODE, $SATUAN);
            if ($vaSatuan['Satuan'] == 2) {
                $nIsi = $vaSatuan['Isi'];
            } else if ($vaSatuan['Satuan'] == 3) {
                $nIsi = $vaSatuan['Isi'] * $vaSatuan['Isi2'];
            }

            $nDebet = $QTY * $nIsi;
            $nHP = round(Func::Devide($HARGA, $nIsi), 2);
            $nHP *= (1 - (intval($DISCITEM) / 100));
            $nHP *= (1 - (intval($DISCFAKTUR1) / 100));
            $nHP *= (1 - (intval($DISCFAKTUR2) / 100));
            $nHP = max($nHP, 0);
            $nKredit = 0;
            if ($DK == "K") {
                $nDebet = 0;
                $nKredit = $QTY * $nIsi;
            }

            if ($nDebet != 0 || $nKredit != 0) {
                $vaFaktur = $instance->GetUrutFaktur($FAKTUR, $TGL);
                // dd($STATUS);
                $va = [
                    'STATUS' => $STATUS,
                    'FAKTUR' => $FAKTUR,
                    'URUT' => $vaFaktur['ID'],
                    'TGL' => Func::Date2String($TGL),
                    'GUDANG' => $GUDANG,
                    'KODE' => $KODE,
                    'SATUAN' => $SATUAN,
                    'QTY' => Func::String2Number($QTY),
                    'DEBET' => Func::String2Number($nDebet),
                    'KREDIT' => Func::String2Number($nKredit),
                    'KETERANGAN' => $KETERANGAN,
                    'HARGA' => Func::String2Number($HARGA),
                    'DISCITEM' => Func::String2Number($DISCITEM),
                    'DISCFAKTUR1' => Func::String2Number($DISCFAKTUR1),
                    'DISCFAKTUR2' => Func::String2Number($DISCFAKTUR2),
                    'PPN' => Func::String2Number($PPN),
                    'HP' => $nHP,
                    'DATETIME' => $vaFaktur['DATETIME'],
                    'USERNAME' => $vaFaktur['USERNAME'],
                ];

                KartuStock::create($va);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function getSatuanStock($KODE, $SATUAN)
    {
        try {
            $valueReturn = [];
            $cKey = '';
            $result = Stock::select('SATUAN', 'SATUAN2', 'SATUAN3', 'HB', 'HB2', 'HB3', 'HJ', 'HJ2', 'HJ3', 'ISI', 'ISI2')
                ->where('kode', $KODE)
                ->orWhere('kode_toko', $KODE)
                ->first();

            if ($result) {
                if ($SATUAN === $result->SATUAN || $SATUAN === $result->SATUAN2 || $SATUAN === $result->SATUAN3) {
                    if ($SATUAN === $result->SATUAN) {
                        $valueReturn['Satuan'] = 1;
                    } elseif ($SATUAN === $result->SATUAN2) {
                        $valueReturn['Satuan'] = 2;
                        $cKey = '2';
                    } elseif ($SATUAN === $result->SATUAN3) {
                        $valueReturn['Satuan'] = 3;
                        $cKey = '3';
                    }

                    $valueReturn['HB'] = $result->{'HB' . $cKey};
                    $valueReturn['HJ'] = $result->{'HJ' . $cKey};
                    $valueReturn['Isi'] = $result->Isi;
                    $valueReturn['Isi2'] = $result->Isi2;
                }
            }
            return $valueReturn;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function getSisaFaktur($FKT, $TGL)
    {
        try {
            $result = DB::select("SELECT IFNULL(SUM(Debet-Kredit), 0) AS SisaFKT FROM kartuhutang WHERE
            Faktur = ? OR FKT = ? AND Tgl <= ?", [$FKT, $FKT, $TGL]);


            if (count($result) > 0) {
                $nSaldo = $result[0]->SisaFKT;
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return $nSaldo;
    }

    public static function updKartuStockReturPembelian($FAKTUR)
    {
        try {
            $instance = new self();
            // $kartustock = KartuStock::where('FAKTUR', $FAKTUR)->delete();
            // $kartuhutang = KartuHutang::where('FAKTUR', $FAKTUR)->delete();
            $rtnpembelian = RtnPembelian::with('totrtnpembelian.supplier')
                ->where('FAKTUR', $FAKTUR)
                ->get();
            foreach ($rtnpembelian as $rtn) {
                $tgl = $rtn->TGL;
                $gudang = $rtn->totrtnpembelian->GUDANG;
                $kode = $rtn->KODE;
                $satuan = $rtn->SATUAN;
                $qty = $rtn->QTY;
                $harga = $rtn->HARGA;
                $discount = $rtn->DISCOUNT;
                $persdisc = $rtn->totrtnpembelian->PERSDISC;
                $persdisc2 = $rtn->totrtnpembelian->PERSDISC2;
                $ppn = $rtn->totrtnpembelian->PPN;
                $supplier = $rtn->totrtnpembelian->supplier->NAMA;
            }
            $instance->updKartuStock(
                Constants::KR_RETUR_PEMBELIAN,
                $FAKTUR,
                $tgl,
                $gudang,
                $kode,
                $satuan,
                $qty,
                'K',
                "Retur Pembelian ke " . $supplier,
                $harga,
                $discount,
                $persdisc,
                $persdisc2,
                $ppn,
            );

            $totrtnpembelian = TotRtnPembelian::with('supplier')
                ->where('FAKTUR', $FAKTUR)
                ->get();
            // dd($gudang);
            foreach ($totrtnpembelian as $data) {
                $tgl = $data->TGL;
                $fakturPembelian = $data->FAKTURPEMBELIAN;
                $jthtmp = $data->JTHTMP;
                $supplier = $data->SUPPLIER;
                $gudang = $data->GUDANG;
                $nama = $data->supplier->NAMA;
                $hutang = $data->HUTANG;
            }
            $instance->updKartuHutang(
                Constants::KR_RETUR_PEMBELIAN,
                $FAKTUR,
                $tgl,
                $fakturPembelian,
                $jthtmp,
                'S',
                $supplier,
                $gudang,
                'Retur Pembelian ke ' . $nama,
                '0',
                $hutang
            );
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error']);
        }
        return response()->json(['status' => 'success']);
    }

    public static function getHargaBeli($kode)
    {
        $countData = PerubahanHargaStock::where('KODE', $kode)
            ->count();

        $retValHb = 0;
        if ($countData > 0) {
            $retValHb = PerubahanHargaStock::where('KODE', $kode)
                ->orderBy('ID', 'desc')
                ->limit(1)
                ->value('HB');
        } else {
            $retValHb = Stock::select('HB')
                ->where('KODE', $kode)
                ->value('HB');
        }

        return $retValHb;
    }

    public static function getHargaJual($kode)
    {
        $countData = PerubahanHargaStock::where('KODE', $kode)
            ->count();

        $retValHj = 0;
        if ($countData > 0) {
            $retValHj = PerubahanHargaStock::where('KODE', $kode)
                ->orderBy('ID', 'desc')
                ->limit(1)
                ->value('HJ');
        } else {
            $retValHj = Stock::select('HJ')
                ->where('KODE', $kode)
                ->value('HJ');
        }
        return $retValHj;
    }
}
