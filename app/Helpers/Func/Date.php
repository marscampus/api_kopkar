<?php

namespace App\Helpers\Func;

use Carbon\Carbon;

class Date
{
    const N_SEC_MIN = 60;
    const N_SEC_HOUR = 3600;
    const N_SEC_DAY = 86400;
    const N_SEC_YEAR = 31536000;
    const N_SEC_YEAR4 = 31622400;

    public static function nextMonth($nTime, $nNextMonth)
    {
        $nDay = date("d", $nTime);
        $nMonth = date("m", $nTime);
        $nYear = date("Y", $nTime);

        $n1 = mktime(0, 0, 0, $nMonth + $nNextMonth, $nDay, $nYear);
        $n2 = mktime(0, 0, 0, $nMonth + $nNextMonth + 1, 0, $nYear);
        return min($n1, $n2);
    }

    public static function getYearSec($nYear)
    {
        return self::isKabisat($nYear) ? self::N_SEC_YEAR4 : self::N_SEC_YEAR;
    }

    public static function getMonthSec($nMonth, $nYear)
    {
        $nMonth--;
        $date = Carbon::createFromDate($nYear, $nMonth + 1, 1);

        if ($nMonth == 1 && self::isKabisat($nYear)) {
            $date->addDay();
        }

        return $date->daysInMonth * self::N_SEC_DAY;
    }

    public static function isKabisat($nYear)
    {
        return $nYear % 4 == 0;
    }

    public static function getDay($nTime)
    {
        $va = [
            "Minggu",
            "Senin",
            "Selasa",
            "Rabu",
            "Kamis",
            "Jum'at",
            "Sabtu"
        ];
        $vaTgl = getdate($nTime);
        return $va[$vaTgl['wday']];
    }

    public static function nextDay($nTime, $nNextDay)
    {
        $nDay = date("d", $nTime);
        $nMonth = date("m", $nTime);
        $nYear = date("Y", $nTime);

        $n = mktime(0, 0, 0, $nMonth, $nDay + $nNextDay, $nYear);
        return $n;
    }

    public static function getDayAwal($nTime)
    {
        $nDay = date('d', $nTime);
        $nMonth = date('m', $nTime);
        $nYear = date('Y', $nTime);

        $n1 = mktime(0, 0, 0, $nMonth, $nDay - 1, $nYear);
        return $n1;
    }
}
