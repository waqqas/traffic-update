#!/usr/bin/env bash

$OPENSHIFT_REPO_DIR/app/yii schedule/run --scheduleFile=@console/config/schedule.php  >> ${OPENSHIFT_PHP_LOG_DIR}/schedule.log
