<?php

namespace Resgef\Synclist\System\Helper\ServerTime;


use Carbon\Carbon;

class ServerTime extends Carbon
{
    /*
     * ebay api's default format is a modified ISO8601 format, eg. 2016-04-28T03:32:16.000Z
     * this is the time format ebay api provides by default
     * ie. by default, times from ebay api xml are in slightly modified iso8601 format in UTC
     * the modification is in seconds to include microseconds(`u` for php),
     * and ending Z stands for Zulu ie. UTC
     * http://developer.ebay.com/devzone/xml/docs/reference/ebay/types/simpletypes.html
     */

    const EBAY = 'Y-m-d\TH:i:s.u\Z';

    static function createFromEbayTime($ebayapidatetime)
    {
        if (!$ebayapidatetime) {
            print("invalid ebay date\n");
        }
        return static::createFromFormat(self::EBAY, $ebayapidatetime, 'UTC');
    }

    static function createFromEtsyTimestamp($timestamp)
    {
        return static::createFromTimestampUTC($timestamp);
    }

    static function createFromISO8601String($datetime)
    {
        return static::createFromFormat(parent::ISO8601, $datetime);
    }

    # diff in days of two times in same format

    static function diffInDaysFromFormat($time1, $time2, $format = parent::ISO8601)
    {
        return static::createFromFormat($format, $time1)->diffInDays(static::createFromFormat($format, $time2));
    }

    /**
     * ebay api time filter limit is 30 days
     * given time_from and time_to limits, build 30 day intervals of time ranges
     * if the time_from past beyond 30 days from time_to, then slice the intervals by 30 days
     * if the time_from is empty then assert it before 30days of time_to
     * dates in iso8601 format
     * @param string $time_from
     * @param string $time_to
     * @param integer $interval_days
     * @return array
     */
    static function build_ebay_intervals($time_from, $time_to, $interval_days)
    {
        if (!$time_from || !$time_to) {
            return [];
        }
        $intervals = [];
        $num_intervals = (ServerTime::diffInDaysFromFormat($time_from, $time_to) / $interval_days);
        for ($i = 1; $i <= floor($num_intervals); $i++) { //loop over full numbers
            $intervals[] = [
                'from' => ServerTime::createFromISO8601String($time_from)->addDays((($i - 1) * $interval_days))->toISO8601String(),
                'to' => ServerTime::createFromISO8601String($time_from)->addDays(($i * $interval_days))->toISO8601String()
            ];
        }
        if ($num_intervals > floor($num_intervals)) { //has fraction, make for it
            $intervals[] = [
                'from' => ServerTime::createFromISO8601String($time_from)->addDays(floor($num_intervals) * $interval_days)->toISO8601String(),
                'to' => $time_to
            ];
        }
        return $intervals;
    }

    public function toEbayString()
    {
        return $this->format(self::EBAY);
    }

    static function diffForHumansFromFormat($from, $to, $format = Carbon::ISO8601)
    {
        $diff = static::createFromFormat($format, $to)->diffForHumans(Carbon::createFromFormat($format, $from));
        $diff = trim(preg_replace('#ago|before|after|from now#', '', $diff));
        return $diff;
    }

    static function diffHumanReadable(Carbon $time1, Carbon $time2 = null)
    {
        if (!$time2) {
            $time2 = static::now();
        }
        return self::diffForHumansFromFormat($time1->toIso8601String(), $time2->toIso8601String());
    }

}
