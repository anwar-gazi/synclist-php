<?php

class Csv {
    static function load_file($fpath, $remove_headline=true) {
        $csv = array_map('str_getcsv', file($fpath));
        /* first line/element is probably field names */
        if ($remove_headline && preg_match('#[a-zA-Z]+#', implode('', $csv[0])) === 1) {
            $csv[0] = '';
            $lines = array_filter($csv);
        } else {
            $lines = $csv;
        }
        return $lines;
    }
}