<?php

/** prepare synclist */
$ROOT_DIR = dirname(__FILE__);
require_once $ROOT_DIR . '/autoload/autoload.php';
require_once dirname($ROOT_DIR) . '/vendor/autoload.php';

\spl_autoload_register('ResGef\SyncList\autoload_synclist');
//ResGef\SyncList\register_autoload_paths("$ROOT_DIR/system/engine", "$ROOT_DIR/system/helper");

if (!function_exists('new_synclist_kernel')) { // to enable user including this file blindly
    /**
     * 
     * @return \SyncListKernel
     */
    function new_synclist_kernel() {
        $ROOT_DIR = dirname(__FILE__);

        $Kernel = new \SyncListKernel();
        $Kernel->inject('config', \YamlWrapper::toobject("$ROOT_DIR/synclist.yaml"));
        $Kernel->inject('load', new SyncListLoader($Kernel));

        $Kernel->config->path->root = "$ROOT_DIR";
        $Kernel->config->path->module = "$ROOT_DIR/modules/";
        $Kernel->config->path->library = "$ROOT_DIR/system/lib/";
        return $Kernel;
    }

}

/* @var $Kernel SyncListKernel */
$Kernel = new_synclist_kernel();
return $Kernel;
