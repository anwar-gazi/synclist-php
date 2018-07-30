<?php


class StrHelper {
    function contains($needle, $haystack) {
        return (strpos($haystack, $needle)!==FALSE);
    }
}