<?php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'session' => [
            'expirySeconds' => 600,    // ten minutes
        ]
    ],
];
