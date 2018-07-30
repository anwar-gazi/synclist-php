<?php

class CarbonX extends Carbon\Carbon {
   /**
    * get current timezone name
    */
   public static function tz_name() {
        $dt = static::now();
        $time = $dt->toDateTimeString();
        $tz_obj = $dt->getTimeZone();
        $tz_name = $tz_obj->getName();
        
        return $tz_name;
   }
   
   public static function tz_offset() {
        $dt = static::now();
        $time = $dt->toDateTimeString();
        $tz_obj = $dt->getTimeZone();

        $tz_offset = $tz_obj->getOffset($dt);

        return $tz_offset;
   }
}