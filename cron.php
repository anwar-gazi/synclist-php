<?php

/** @var Registry $registry */
$registry = require __DIR__ . '/boot.php';
$cronlog_file = join_path(DIR_LOGS, $registry->get('config')->get('cron_error_log'));
file_put_contents($cronlog_file, '');

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('log_errors', 1);
ini_set('error_log', $cronlog_file);

$config = require __DIR__ . '/config/cron.php';

if ($config['disable_all']) {
    die("no cron allowed to run");
}

$cron_name = 'main';
/** @var SyncListApi $api */
$api = require __DIR__ . '/SyncList/synclist.php';
$cronstate = $api->CronState;
$cronstate->reset($cron_name);
$start = \Carbon\Carbon::now();
foreach ($config['crons'] as $i => $settings) {
    if (!$settings['enabled']) {
        continue;
    }

    /** @var \resgef\synclist\system\interfaces\croninterface\CronInterface $controller */
    $controller = new $settings['class_name']($registry);
    $controller->execute();

    $pc = floor(100 / count($config['crons']));
    $cronstate->set_value($cron_name, ($i + 1) * $pc);
}
$cron_dur = \Carbon\Carbon::now()->diffForHumans($start);
$parts = explode(' ', $cron_dur);
array_splice($parts, -1);
$duration = implode(' ', $parts);
print("cron finished in $duration\n");
$cronstate->set_value($cron_name, 100);
