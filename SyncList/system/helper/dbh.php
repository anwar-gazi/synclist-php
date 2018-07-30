<?php

class DBH {
    static function query($sql) {
        return $GLOBALS['Registry']->db->query($sql);
    }
    static function db() {
        return $GLOBALS['Registry']->db;
    }
}