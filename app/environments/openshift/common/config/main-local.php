<?php

$mysqlHost = getenv('OPENSHIFT_MYSQL_DB_HOST');
$mysqlPort = getenv('$OPENSHIFT_MYSQL_DB_PORT');

$dsn = "mysql:host=" . $mysqlHost . ";port=" . $mysqlPort .";dbname=trafficupdate";


return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => 'adminnYXGEh1',
            'password' => 'r6dLBGRqDtuM',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'sms' => [
            'username' => '8655_traffic',
            'password' => 'AsFgT845',
            'originator' => '8655',
            'smsGatewayUrl' => 'http://smsctp1.eocean.us:24555/api'
        ],
    ],
];
