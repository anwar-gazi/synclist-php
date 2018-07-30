<?php
/** @var Registry $registry */
$registry = require __DIR__ . '/boot.php';

// Session
$registry->set('session', new Session());

// Document
$registry->set('document', new Document());

// Encryption
$registry->set('encryption', new Encryption($registry->get('config')->get('config_encryption')));

$registry->set('portal_user', new SyncList_user($registry));

// Front Controller
$controller = new Front($registry);

$controller->addPreAction(new Action('user/login/preaction_check_logged'));
// Router
if (isset($request->get['route'])) {
    $action = new Action($request->get['route']);
} else {
    $action = new Action('common/home');
}

// Dispatch
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();
