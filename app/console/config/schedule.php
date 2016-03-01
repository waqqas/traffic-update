<?php

$logDir = getenv('OPENSHIFT_PHP_LOG_DIR');


/** @var \omnilight\scheduling\Schedule $schedule */
$schedule->command('cron/run')->everyMinute()->sendOutputTo($logDir . '/cron.log');
$schedule->command('session/flush-all')->everyMinute()->sendOutputTo($logDir . '/session.log');

