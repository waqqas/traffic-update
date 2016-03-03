<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host=127.0.01;port=3306;dbname=trafficupdate",
            'username' => 'trafficupdate',
            'password' => 'trafficupdate',
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
        'mapQuest' => [
            'apiKey' => 'GJVt2ixNbY9BPoGYNDQItCAPQyE9G6L5',
        ],
        'graphHopper' => [
            'apiKey' => '09762de2-9c20-40d0-a799-a8c1157a491f',
        ],
        'googleMaps' => [
            'apiKey' => 'AIzaSyAfqjLZ0Dwhb8_U82T2Yji3BP-O5E54kHI',
        ],
        'translate' => [
            'key' => 'AIzaSyAfqjLZ0Dwhb8_U82T2Yji3BP-O5E54kHI',
        ],
        'ga' => [
            'trackingId' => 'UA-73119238-4',
            'clientId' => '8314bf1b-6f7b-4582-a3fc-57fdbcaaf76d',
        ],
    ],
];
