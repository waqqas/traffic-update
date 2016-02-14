<?php
return [
    'language' => 'en-US',
    'sourceLanguage' => 'en-US',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'modules' => [
        'translatemanager' => [
            'class' => 'lajax\translatemanager\Module',
        ],
    ],
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
            'class' => 'common\components\ConsoleRunner',
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
        'translate' => [
            'class' => 'richweber\google\translate\Translation',
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\DbMessageSource',
                    'db' => 'db',
                    'sourceLanguage' => 'en-US',
                    'sourceMessageTable' => '{{%language_source}}',
                    'messageTable' => '{{%language_translate}}',
                    'cachingDuration' => 86400,
                    'enableCaching' => true,
                ],
            ],
        ],
    ],
];
