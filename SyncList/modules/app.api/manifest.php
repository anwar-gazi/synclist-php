<?php
$root_conf = require __DIR__ . '/../../../config/config.php';
$_config = [
    'version' => '1',
    'title' => '',
    'description' => '',
    'index_file' => 'synclistapi.php',
    'index_class' => 'SyncListApi',
    'cron_error_log' => $root_conf['cron_error_log'],
    'require' => [
        'synclist_module' => null,
        'composer' => null
    ],
    'path' => [
        'root' => __DIR__
    ],
    'dbmysqli' => [
        'ISO8601' => $root_conf['sqlISO8601'],
        'default' => $root_conf['dbmysqli']['default'],
    ]
];

return $_config;
