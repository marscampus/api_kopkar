<?php

namespace App\Helpers;

use App\Models\Log;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use NumberFormatter;

class Func
{
    public static function Devide($A, $B)
    {
        $nRetval = 0;

        if (empty($A) || empty($B) || $A == 0 || $B == 0) {
            $nRetval = 0;
        } else {
            $nRetval = $A / $B;
        }

        return $nRetval;
    }


    public static function Date2String($dTgl)
    {
        $cRetval = substr($dTgl, 0, 10);
        $va = explode("-", $dTgl);
        // Jika Array 1 Bukan Tahun maka akan berisi 2 Digit
        if (strlen($va[0]) == 2) {
            $cRetval = $va[2] . "-" . $va[1] . "-" . $va[0];
        }
        return $cRetval;
    }

    public static function String2Date($dTgl)
    {
        $cRetval = substr($dTgl, 0, 10);
        $va = explode("-", $dTgl);

        // Jika Array 1 Bukan Tahun maka akan berisi 2 Digit
        if (strlen($va[0]) == 2) {
            $cRetval = $va[2] . "-" . $va[1] . "-" . $va[0];
        }

        $date = DateTime::createFromFormat('Y-m-d', $cRetval);
        if ($date) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    public static function String2Number($cString)
    {
        return str_replace(",", "", $cString);
    }

    public static function getZFormat($value)
    {
        $valueReturn = strval($value);
        $valueReturn = number_format(floatval($valueReturn), 2);
        return $valueReturn;
    }

    public static function getZFormatWithDecimal($value, $decimal)
    {
        $valueReturn = strval($value);
        $valueReturn = number_format(floatval($valueReturn), $decimal);
        return $valueReturn;
    }

    public static function formatDate($value)
    {
        return date('d-m-Y', strtotime($value));
    }

    public static function Tgl2Time($dTgl)
    {
        if (empty($dTgl)) {
            return 0;
        }

        $instance = new self();
        $dTgl = $instance->String2Date($dTgl);

        // Ubah format tanggal menjadi Y-m-d jika belum dalam format tersebut
        $va = explode("-", $dTgl);
        if (count($va) !== 3) {
            return 0; // Format tanggal tidak valid
        }

        // Pastikan nilai bulan dan hari berada dalam rentang yang valid
        $year = intval($va[0]);
        $month = intval($va[1]);
        $day = intval($va[2]);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return 0; // Nilai bulan atau hari tidak valid
        }

        // Gunakan Carbon untuk membuat waktu berdasarkan tanggal yang sudah dipecah
        $time = Carbon::create($year, $month, $day, 0, 0, 0);

        return $time->timestamp;
    }

    public static function replaceKarakterKhusus($cString)
    {
        $cString = str_replace(":", "", $cString);
        $cString = str_replace("=", "", $cString);
        $cString = str_replace(";", "", $cString);
        $cString = str_replace("'", "", $cString);
        $cString = str_replace(",", " ", $cString);
        $cString = str_replace(".", " ", $cString);
        $cString = str_replace("+", "", $cString);
        $cString = str_replace("/", " ", $cString);
        $cString = str_replace("&", "dan", $cString);
        $cString = str_replace(PHP_EOL, " ", $cString); // Mengganti chr(10) dengan PHP_EOL

        // Gunakan preg_replace untuk mengganti multiple whitespace menjadi satu whitespace
        $cString = preg_replace('/\s+/', ' ', $cString);

        // Gunakan trim untuk menghapus whitespace di awal dan akhir string
        // $cString = trim(String2SQL($cString)); // Pastikan Anda telah mengimplementasikan String2SQL()
        $cString = trim($cString);

        return $cString;
    }

    public static function String2SQL($cChar)
    {
        $patterns = [
            '/&/',
            "/'/",
            '/"/',
            '/  /',
        ];
        $replacements = [
            '',
            '\'',
            '"',
            ' ',
        ];
        $cChar = preg_replace($patterns, $replacements, $cChar);
        $cChar = trim(preg_replace('/\s\s+/', ' ', $cChar));
        return $cChar;
    }

    public static function Terbilang($nNilai, $lRupiah = true)
    {
        $instance = new self();
        $formatter = new NumberFormatter('id', NumberFormatter::SPELLOUT);
        $nNilai = $instance->String2Number($nNilai);
        $cRetval = $formatter->format($nNilai);
        $cRetval = ucwords($cRetval);
        if ($lRupiah) {
            $cRetval .= ' Rupiah';
        }

        return $cRetval;
    }

    public static function GetFullDate($dTgl)
    {
        $instance = new self();
        $nTgl = date('d', strtotime($dTgl));
        $nBulan = date('m', strtotime($dTgl));
        $nTahun = date('Y', strtotime($dTgl));
        $cBulan = $instance->GetMonth($nBulan);

        $cTgl = $nTgl . " " . $cBulan . " " . $nTahun;

        return $cTgl;
    }

    public static function GetMonth($nBulan)
    {
        $n = min(max(intval($nBulan) - 1, 0), 11);
        $vaMonth = [
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember"
        ];

        return $vaMonth[$n];
    }

    public static function PembulatanKeatas($nNominal, $nPembulatan)
    {
        $nNominal1 = intval($nNominal);

        if (($nNominal - $nNominal1) > $nPembulatan) {
            $nRetval = $nNominal1 + 1;
        } else {
            $nRetval = $nNominal1;
        }

        return $nRetval;
    }

    public static function RoundUp($nNumber, $nPembulatan)
    {
        if ($nPembulatan <> 0) {
            $nNumber = ceil($nNumber);
            $nSelisih = $nNumber % $nPembulatan;
            if ($nSelisih <> 0) {
                $nNumber += ($nPembulatan - $nSelisih);
            }
        }
        return $nNumber;
    }

    public static function SeekDaerah($cKode)
    {
        $dbRow = DB::table('kodya')
            ->select('Keterangan')
            ->where('kode', $cKode)
            ->first();

        $cKeterangan = "";

        if ($dbRow) {
            $cKeterangan = $dbRow->Keterangan;
        }

        return $cKeterangan;
    }

    public static function last_day_of_month($date)
    {
        return date('Y-m-t', strtotime($date));
    }

    public static function modAngsuran($number)
    {
        $roundUp = GetterSetter::getDBConfig('msPembulatanAngsuran', 1);
        if ($roundUp == 0) return $number;
        if ($number == 0) return $number;
        $number = $number;
        if ($number > 0) {
            $selisih = $number % $roundUp;
            if ($selisih <> 0) {
                $number += ($roundUp - $selisih);
            }
        }
        return $number;
    }

    public static function getJumlahHari($tgl)
    {
        $carbonDate = Carbon::createFromFormat('d-m-Y', $tgl);
        $carbonDate->addMonth();
        $jumlah = $carbonDate->daysInMonth;
        return $jumlah;
    }

    public static function mod50($number)
    {
        $number = ceil($number);
        $selisih = $number % 50;
        if ($selisih !== 0) {
            $number += (50 - $selisih);
        }
        return $number;
    }

    public static function formatCurrency($amount)
    {
        if ($amount === 0 || !is_numeric($amount)) {
            // Return '0' with separators for zero or invalid values
            $formatter = new NumberFormatter('id-ID', NumberFormatter::DECIMAL);
            return $formatter->format(0);
        } else {
            // Return formatted amount for non-zero values
            $formatter = new NumberFormatter('id-ID', NumberFormatter::DECIMAL);
            return $formatter->format($amount);
        }
    }

    public static function getRekeningLawan($field, $table, $where)
    {
        $tableKecil = strtolower($table);
        $data = DB::table($tableKecil)
            ->select($field . ' AS Rekening')
            ->whereRaw($where)
            ->first();
        if ($data) {
            $rekening = $data->Rekening;
        }
        return $rekening;
    }

    public static function writeLog($controller, $func, $reqData, $retVal, $user)
    {
        $array = [
            "Controller" => $controller,
            "Function" => $func,
            "Tgl" => Carbon::now()->format('Y-m-d'),
            "Request" => json_encode($reqData, JSON_PRETTY_PRINT),
            "Response" => json_encode($retVal, JSON_PRETTY_PRINT),
            "User" => $user,
            "DateTime" => Carbon::now()
        ];
        Log::create($array);
    }

    public static function getTglIdentik($dTglAwal, $dTglAkhir)
    {
        $i = "";
        $n = 0;
        $dTglAwal = self::Date2String($dTglAwal); // Jika Date2String adalah fungsi yang Anda miliki
        $dTglAkhir = self::Date2String($dTglAkhir);

        $dTgl = $dTglAwal;

        if ($dTglAwal <= $dTglAkhir) {
            while ($i <= $dTglAkhir) {
                $i = Carbon::parse($dTglAwal)->addMonthsNoOverflow($n)->format('Y-m-d');

                if ($i <= $dTglAkhir) {
                    $dTgl = $i;
                }

                $n++;
            }
        }

        return $dTgl;
    }

    public static function isHoliday($nTime)
    {
        $vaTgl = getdate($nTime);
        $lRetval = false;

        if ($vaTgl['wday'] == 0 || $vaTgl['wday'] == 6) {
            $lRetval = true;
        } else {
            $cTgl = date("Y-m-d", $nTime);
            $dbData = DB::table('harilibur')->select('Tgl')->where('Tgl', '=', $cTgl)->get();

            if ($dbData->count() > 0) {
                $lRetval = true;
            }
        }

        return $lRetval;
    }

    public static function EOM($dTgl)
    {
        $day = self::Date2String($dTgl);
        $d = Carbon::create($day)->endOfMonth();

        return $d->format('d-m-Y');
    }

    public static function BOM($dTgl)
    {
        $day = self::Date2String($dTgl);
        $d = Carbon::create($day)->startOfMonth();

        return $d->format('d-m-Y');
    }

    public static function GetTglMutasiTerakhir($cRekening, $cJenis, $dTgl)
    {
        $result = DB::table($cJenis)
            ->select('Tgl')
            ->where('rekening', $cRekening)
            ->where('tgl', '<=', $dTgl)
            ->orderByDesc('tgl')
            ->limit(1)
            ->first();

        $dTgl = $result ? $result->Tgl : '';

        return $dTgl;
    }

    public static function getKodeInduk($cKode)
    {
        $cKodeInduk = $cKode;

        $result = DB::table('registernasabah')
            ->select('KodeInduk')
            ->where('Kode', $cKode)
            ->first();

        if ($result) {
            $cKodeInduk = $result->KodeInduk ?? $cKode;
        }

        return $cKodeInduk;
    }

    public static function pembulatan($nNominal)
    {
        $nNominal1 = intval($nNominal);

        $nRetval = ($nNominal - $nNominal1) > 0.49 ? $nNominal1 + 1 : $nNominal1;

        return $nRetval;
    }

    public static function modAktiva($nNumber)
    {
        $nRoundUp = 1;
        $nSelisih = $nNumber % $nRoundUp;
        if ($nSelisih <> 0) {
            $nNumber += ($nRoundUp - $nSelisih);
        }
        return $nNumber;
    }

    public static function number2String($nNumber, $nDecimals = 2)
    {
        if (empty($nNumber)) {
            $nNumber = 0;
        }
        $nNumber = self::String2Number($nNumber);
        return number_format($nNumber, $nDecimals, ".", ",");
    }

    public static function pickGabungan(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cEmail = $vaRequestData['auth']['email'];
        $cListCabang = [];
        $cKodeGabungan = $vaRequestData['KodeGabungan'];
        unset($vaRequestData['auth']);
        $vaData = DB::table('username')
            ->select(
                'Gabungan',
                'Cabang',
                'CabangInduk'
            )
            ->where('UserName', '=', $cEmail)
            ->first();
        if ($vaData) {
            $cGabungan = $vaData->Gabungan;
            $cCabang = $vaData->Cabang;
            $cCabangInduk = $vaData->CabangInduk;
            switch ($cGabungan) {
                case 0:
                    $cListCabang = DB::table('cabang')
                        ->select(
                            'Kode',
                            'Keterangan'
                        )
                        ->where('Kode', '=', $cCabang)
                        ->orderBy('Kode')
                        ->get();
                    break;
                case 1:
                    $cListCabang = DB::table('cabang')
                        ->select(
                            'Kode',
                            'Keterangan'
                        )
                        ->where('Kode', '=', $cCabang)
                        ->orderBy('Kode')
                        ->get();
                    if ($cKodeGabungan == 'B') {
                        $cListCabang = DB::table('cabang')
                            ->select(
                                'Kode',
                                'Keterangan'
                            )
                            ->where('Kode', '=', $cCabangInduk)
                            ->orderBy('Kode')
                            ->get();
                    }
                    break;
                case 2:
                    $cListCabang = DB::table('cabang')
                        ->select(
                            'Kode',
                            'Keterangan'
                        )
                        ->orderBy('Kode')
                        ->get();
                    if ($cKodeGabungan == 'B') {
                        $cListCabang = DB::table('cabang')
                            ->select(
                                'Kode',
                                'Keterangan'
                            )
                            ->orderBy('Kode')
                            ->get();
                    }
                    break;
                default:
                    break;
            }
        }
        return response()->json($cListCabang);
    }

    public static function getUserName(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cEmail = $vaRequestData['auth']['email'];
            unset($vaRequestData['auth']);
            $vaData = DB::table('username')
                ->select(
                    'UserName'
                )
                ->where('UserName', '=', $cEmail)
                ->first();
            if ($vaData) {
                $cUserName = $vaData->UserName;
            }
            return $cUserName;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function getEmail(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cValueReturn = $vaRequestData['auth']['email'];
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return $cValueReturn;
    }

    public static function formatSaldo($nNumber)
    {
        if ($nNumber < 0) {
            return '(' . number_format(abs($nNumber), 2, ',', '.') . ')';
        } else {
            return number_format($nNumber, 2, ',', '.');
        }
    }
}
