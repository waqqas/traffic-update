#!/usr/bin/env bash

date >> ${OPENSHIFT_PHP_LOG_DIR}/send_queued_sms.log
$OPENSHIFT_REPO_DIR/app/yii sms/send-all queue