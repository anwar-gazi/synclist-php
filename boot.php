<?php

date_default_timezone_set('UTC');
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 0);

require_once __DIR__ . '/autoload.php';
$conf_more = require(__DIR__ . '/config/config.php');
require_once(DIR_SYSTEM . 'startup.php');

$config = new Config();

//now add to the main configuration
foreach ($conf_more as $key => $value) {
    if (!$key) {
        continue;
    }
    $config->set($key, $value);
}

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

$registry->set('config', $config);

$url = new Url($config);
$registry->set('url', $url);

// Log
$log = new Log($config->get('config_error_filename'), $config->get('syslog_filename'));
$registry->set('log', $log);

require __DIR__ . '/SyncList/init.php';

$registry->set('synclist_api', new_synclist_kernel()->load->module('app.api'));
$db_creds = $registry->get('synclist_api')->db_creds();
/** @var DBMySQLi $db */
$db = $registry->get('synclist_api')->db;
$registry->set('db', $db);
$registry->set('pdo', $db->pdo->pdo);

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
//$response->addHeader('Content-Type: text/html; charset=utf-8');
$response->setCompression($registry->get('config')->get('config_compression'));
$registry->set('response', $response);

// Cache
$cache = new Cache('file');
$registry->set('cache', $cache);

$registry->set('twig', new Twig_Environment(new Twig_Loader_Filesystem(DIR_TEMPLATE), ['debug' => true]));
//$registry->get('twig')->addExtension(new Twig_Extension_Debug());
$registry->get('twig')->addFunction(new Twig_SimpleFunction('root_url', [$url, 'root_url']));
$registry->get('twig')->addFunction(new Twig_SimpleFunction('url', [$registry->get('url'), 'link']));
$registry->get('twig')->addFunction(new Twig_SimpleFunction('model', function ($route, Array $vars = []) use ($registry) {
    $method = basename($route);
    $route = str_replace("/$method", '', $route);
    $registry->load->model($route);
    $prop = 'model_' . str_replace('/', '_', $route);
    return call_user_func_array([$registry->{$prop}, $method], $vars);
}));
$registry->get('twig')->addFunction(new Twig_SimpleFunction('api', function ($api_route, Array $vars = []) use ($registry) {
    $parts = explode('/', $api_route);
    $api_name = $parts[0];
    $api_method = $parts[1];
    return call_user_func_array([$registry->get('synclist_api')->$api_name, $api_method], $vars);
}));
$registry->get('twig')->addFunction(new Twig_SimpleFunction('get_config', [$registry->get('config'), 'get']));

$registry->get('twig')->addFunction(new Twig_SimpleFunction('load_view', [$registry->get('load'), 'view']));
$registry->get('twig')->addFunction(new Twig_SimpleFunction('tpl_source', function ($tpl_route) use ($registry) {
    return file_get_contents(DIR_TEMPLATE . '/' . $tpl_route);
}));

$registry->get('twig')->addFunction(new Twig_SimpleFunction('http_get', function ($key) use ($registry) {
    return @$registry->get('request')->get[$key];
}));

$registry->get('twig')->addFunction(new Twig_SimpleFunction('url_param', function ($key) use ($registry) {
    return @$registry->get('request')->get[$key];
}));

$registry->get('twig')->addFunction(new Twig_SimpleFunction('asset_url', function ($asset_route) use ($registry) {
    return join_path($registry->get('config')->get('config_url'), '/assets/', $asset_route);
}));


return $registry;
