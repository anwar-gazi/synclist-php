<?php

/*
 * each cron runner must implement this interface
 */
interface CronTask {
    function run();
}