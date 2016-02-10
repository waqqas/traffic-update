#!/usr/bin/env bash

$OPENSHIFT_REPO_DIR/app/yii schedule/run --scheduleFile=@console/config/schedule.php 1>> /dev/null 2>&1
