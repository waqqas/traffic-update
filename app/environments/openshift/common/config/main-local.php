<?php

$mysqlHost = getenv('OPENSHIFT_MYSQL_DB_HOST');
$mysqlPort = getenv('OPENSHIFT_MYSQL_DB_PORT');

$dsn = "mysql:host=" . $mysqlHost . ";port=" . $mysqlPort .";dbname=trafficupdate";


return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => 'adminnYXGEh1',
            'password' => 'r6dLBGRqDtuM',
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 0,
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
        'mapQuest' => [
            'apiKey' => 'unjfqBcvRsGFg7L9GCv9uvDCzG0gZpAp',
        ],
        'graphHopper' => [
            'apiKey' => '3090ca2b-a4b6-422b-be18-24f7a68422d9',
        ],
        'googleMaps' => [
            'apiKey' => 'AIzaSyAzaE9evW4DocDLJ105k9YsnTYJuZ2LI4s',
        ],
        'translate' => [
            'key' => 'AIzaSyAzaE9evW4DocDLJ105k9YsnTYJuZ2LI4s',
        ],
        'ga' => [
            'trackingId' => 'UA-73119238-3',
            'clientId' => '5edd66fc-c3ae-4ff8-92a4-484aaec2085d',
        ],
    ],
];
