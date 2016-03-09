<?php

namespace console\components\sms;

use Yii;

class Command
{
    const DUMMY = 0;
    const NUMERIC = 1;
    const REGEX = 2;


    public $commandNumber = 1;

    public $shortcuts = [];

    // regex must be such that $matches[1] catches all the parameters after keyword (e.g. TUP)

    public static $availableCommands = [
        'daily' => [
            'type' => Command::REGEX,
            'regex' => '((0?\d|1[0-2]):*(0\d|[0-5]\d)*\s+(0?\d|1[0-2]):*(0\d|[0-5]\d)*)',
            'shortcutInfo' => 'To get daily notifications now, send {message}\nEx: {example}',
            'fullInfo' => 'To get daily notifications, send {message}\nEx: {example}',
            'shortcut' => [
                'message' => '<AM time> <PM time>',
                'example' => '8:00 5:30',
            ],
            'full' => [
                'message' => 'DAILY <AM time> <PM time>',
                'example' => 'DAILY 8:00 5:30',
            ],
        ],
        'language' => [
            'type' => Command::REGEX,
            'regex' => '((urdu|english)\s*)$',
            'shortcutInfo' => 'To select language now, send {message}\nEx: {example}',
            'fullInfo' => 'To select language, send {message}\nEx: {example}',
            'shortcut' => [
                'message' => '<urdu/english>',
                'example' => 'urdu',
            ],
            'full' => [
                'message' => 'LANGUAGE <urdu/english>',
                'example' => 'LANGUAGE urdu',
            ]
        ],
        'route' => [
            'type' => Command::REGEX,
            'regex' => '',
            'fullInfo' => 'To get best route, send {message}\nEx: {example}',
            'full' => [
                'message' => 'ROUTE <source> TO <destination>',
                'example' => 'ROUTE F-6, Islamabad TO F-10, Islamabad',
            ]
        ],
        'now' => [
            'type' => Command::NUMERIC,
            'shortcutInfo' => 'To get current traffic situation now, send {message}',
            'fullInfo' => 'To get current traffic situation, send {message}',
            'full' => [
                'message' => 'NOW',
            ],
        ],
        'stop' => [
            'type' => Command::NUMERIC,
            'shortcutInfo' => 'To stop receiving daily notifications, send {message}',
            'fullInfo' => 'To stop receiving daily notifications, send {message}',
            'full' => [
                'message' => 'STOP'
            ],
        ],
        'report' => [
            'type' => Command::REGEX,
            'regex' => '((accident|congestion|construction|blockade|open)\s+at\s+.*)',
            'shortcutInfo' => 'To report traffic incident now, {message}\nEx: {example}',
            'fullInfo' => 'To report traffic incident, {message}\nEx: {example}',
            'shortcut' => [
                'message' => '<congestion/accident/blockade/construction/open> AT <location>',
                'example' => 'accident AT Faizabad Interchange',
            ],
            'full' => [
                'message' => 'REPORT <congestion/accident/blockade/construction/open> AT <location>',
                'example' => 'REPORT accident AT Faizabad Interchange',
            ]

        ],
        'city' => [
            'type' => Command::REGEX,
            'regex' => '',
            'fullInfo' => 'To set your current city, send {message}\nEx: {example}',
            'full' => [
                'message' => 'CITY <city-name>',
                'example' => 'CITY ISLAMABAD',
            ],
        ],
        'help' => [
            'type' => Command::REGEX,
            'regex' => '',
            'fullInfo' => 'To get help on command: Send {message}\nEx: {example}',
            'full' => [
                'message' => 'HELP <command>',
                'example' => 'HELP REPORT',
            ],
        ],
        '$' => [
            'type' => Command::DUMMY,
        ],
    ];

    public function generateMessage($keyword, $shorten = true)
    {
        $command = self::$availableCommands[$keyword];

        $message = [];
        switch ($command['type']) {
            case Command::REGEX:
                if ($shorten && !empty($command['regex'])) {
                    $message = [
                        'info' => $command['shortcutInfo'],
                        'params' => array_map(
                            function ($param) {
                                return Yii::$app->params['smsKeyword'] . " " . $param;
                            }
                            , $command['shortcut']),
                    ];

                    $this->shortcuts[$keyword] = $command['regex'];
                } else {
                    $message = [
                        'info' => $command['fullInfo'],
                        'params' => array_map(
                            function ($param) {
                                return Yii::$app->params['smsKeyword'] . " " . $param;
                            }
                            , $command['full'])
                    ];
                }
                break;
            case Command::NUMERIC:
                if ($shorten) {
                    $message = [
                        'info' => $command['shortcutInfo'],
                        'params' => [
                            'message' => Yii::$app->params['smsKeyword'] . " " . $this->commandNumber,
                            'example' => Yii::$app->params['smsKeyword'] . " " . $this->commandNumber,
                        ]
                    ];

                    $this->shortcuts[$keyword] = "\\b($this->commandNumber)$";

                    $this->commandNumber++;
                } else {
                    $message = [
                        'info' => $command['fullInfo'],
                        'params' => array_map(
                            function ($param) {
                                return Yii::$app->params['smsKeyword'] . " " . $param;
                            }
                            , $command['full'])
                    ];

                }
                break;
        }
        return $message;


    }

    public function generateInfo($keyword, $shorten = true)
    {

        $message = $this->generateMessage($keyword, $shorten);

        if (!empty($message)) {
            return Yii::t('sms', $message['info'], $message['params']);
        }
        return null;
    }
}