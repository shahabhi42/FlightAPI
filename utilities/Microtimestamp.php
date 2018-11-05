<?php
class Microtimestamp{
    // Get MicrotimeStamp (Unique ID)
    public static function getMicrotimestampString(){
        $micro = microtime(false);
        $m = explode(' ', $micro);
        $micro = $m[1].$m[0];
        $micro = str_replace('.', '', $micro);

        return $micro;
    }

    // Get MicrotimeStamp (Unique ID) Float
    public static function getMicrotimeFloat(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}