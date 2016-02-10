<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'sms' => [
            'class' => 'common\components\SmsSender',
        ],
        'consoleRunner' => [
            'class' => 'vova07\console\ConsoleRunner',
            'file' => '@app/../yii',
        ],
        'mapQuest' => [
            'class' => 'common\components\MapQuest',
            'apiServerUrl' => 'http://www.mapquestapi.com',
            'serviceName' => 'directions',
            'apiVersion' => 'v2',
        ],
        'graphHopper' => [
            'class' => 'common\components\GraphHopper',
            'apiServerUrl' => 'https://graphhopper.com/api/1',
        ],
        'googleMaps' => [
            'class' => 'common\components\GoogleMaps',
        ],
        'formatter' => [
            'class' => 'common\components\SmsFormatter',
        ],
        'schedule' => [
            'class' => 'omnilight\scheduling\Schedule',
        ],
    ],
];
