<?php

require_once __DIR__ . '/init.php';

/** @return SyncListApi */
return new_synclist_kernel()->load->module('app.api');