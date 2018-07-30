<?php

namespace ResGef\SyncList;

/* ---------------------------------------------------------------------------
 * ---------------------------------------------------------------------------
 * the class container file basename(without .php) must be same as class name
 * upper/lower case doesnt matter
 */

function autoload_synclist($class) {
    $root = dirname(__DIR__);
    $regitered_paths = ["$root/system/engine", "$root/system/helper", "$root/system/lib"];

    if (empty($regitered_paths)) {
        \trigger_error("no paths registered for autoloading!", \E_USER_ERROR);
    }
    /** over each registered paths */
    foreach ($regitered_paths as $full_path) { // each entry in user supplied path
        /** recursive scan inside the path for our class */
        $file = scandir_for($full_path, $class);
        if ($file) {
            require_once $file;
            return true;
        }
    }
    
    return false;
}
/**
 * 
 * pass as many number of arguments as you want
 */
function register_autoload_paths() {
    $paths = \func_get_args();
    $num_args = \func_num_args();
    for ($i = 0; $i < $num_args; $i++) {
        $full_path = $paths[$i];
        if (is_dir($full_path)) {
            $GLOBALS['_RESGEF_SYNCLIST_AUTOLOAD_PATHS_'][] = $full_path;
        } else {
            trigger_error("autoload path register fail: '$full_path' is not a valid directory", E_USER_ERROR);
        }
    }
}

function scandir_for($dir, $class) {
    $Directory = new \RecursiveDirectoryIterator($dir);
    $Iterator = new \RecursiveIteratorIterator($Directory);

    foreach ($Iterator as $I) {
        if (
                $I->isFile() &&
                (
                (\strtolower($I->getBasename('.php')) == \strtolower($class)) ||
                (\strtolower($I->getBasename('.php')) == \strtolower(\str_replace("_", "", $class)))
                )
        ) {
            return $I->getPathname();
        }
    }
    return false;
}
