<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logVars' => [],
                ],
            ],
        ],
        'session' => [
            'class' => 'yii\web\DbSession',
             'sessionTable' => '{{%smssession}}',
        ],
        'user' => [
            'class' => 'common\components\sms\User',
            'identityClass' => 'common\models\User',
        ],
        'response' => [
            'class' => 'console\components\sms\Response',
        ]
    ],
    'params' => $params,
];
