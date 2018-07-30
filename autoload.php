<?php

require __DIR__ . '/vendor/autoload.php';

/**
 * our rule for autoload: namespace indicates path and should be up to the file name, eg. Path\To\File(.php)\Class
 * @param $class
 * @return bool
 */
function autoload_synclist($class)
{
    $ns_lookup = [
        'Resgef\SyncList' => __DIR__
    ];
    // now remove the trailing class portion
    $class_ns = substr_replace($class, '', strrpos($class, '\\'));
    foreach ($ns_lookup as $ns_prefix => $ns_path) {
        if (strpos(strtolower($class), strtolower($ns_prefix)) !== false) { //namespace prefix found
            $pseudo_path = str_replace(strtolower($ns_prefix), '', strtolower($class_ns));
            $path = $ns_path . strtolower(str_replace('\\', '/', $pseudo_path)) . '.php';
            if (file_exists($path)) {
                require_once $path;
            } else { //load fail
//                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);trigger_error("cannot load $class", E_USER_ERROR);
            }
        }
    }
    return true;
}

spl_autoload_register('autoload_synclist', true);