<?php

Interface ConfigInterface {
    static function validate($filepath);
    /*
     * get should return $key values from deep inside by recursive search if necessary 
     * to make the config feel like plain key=>value list
     */
    public function get($key);
    /*
     * 
     */
    function __get($key);
    function __isset($key);
    public function __toArray();
}