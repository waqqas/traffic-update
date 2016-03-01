<?php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'session' => [
            'expirySeconds' => 120,    // 2 minutes
        ]
    ],
];
