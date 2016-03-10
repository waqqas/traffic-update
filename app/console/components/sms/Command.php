<?php

namespace console\components\sms;

use Yii;

class Command
{
    const DUMMY = 0;
    const NUMERIC = 1;
    const REGEX = 2;
    const MULTI = 3;


    public $commandNumber = 1;
/*
 * Each entry in the shortcuts is an array with following attributes
 * 'regex' : Regular expression to validate that the parameters are valid
 * 'command': command to run if the regex
 * 'replace': (optional) replace the parameters passed by the regex
 */
    public $shortcuts = [];

    // regex must be such that $matches[1] catches all the parameters after keyword (e.g. TUP)

    public static $availableCommands = [
        'daily' => [
            'default' => [
                'type' => Command::REGEX,
                'command' => 'daily',
                'regex' => '((0?\d|1[0-2]):*(0\d|[0-5]\d)*\s+(0?\d|1[0-2]):*(0\d|[0-5]\d)*)',
                'replace' => '',
                'shortInfo' => 'To get daily notifications now, send {message}\nEx: {example}',
                'short' => [
                    'message' => '<AM time> <PM time>',
                    'example' => '8:00 5:30',
                ],
                'fullInfo' => 'To get daily notifications, send {message}\nEx: {example}',
                'full' => [
                    'message' => 'DAILY <AM time> <PM time>',
                    'example' => 'DAILY 8:00 5:30',
                ],
            ],
        ],
        'language' => [
            'default' => [
                'type' => Command::REGEX,
                'command' => 'language',
                'regex' => '((urdu|english)\s*)$',
                'replace' => '',
                'shortInfo' => 'To select language now, send {message}\nEx: {example}',
                'short' => [
                    'message' => '<urdu/english>',
                    'example' => 'urdu',
                ],
                'fullInfo' => 'To select language, send {message}\nEx: {example}',
                'full' => [
                    'message' => 'LANGUAGE <urdu/english>',
                    'example' => 'LANGUAGE urdu',
                ],
            ],
        ],
        'route' => [
            'default' => [
                'type' => Command::REGEX,
                'command' => 'route',
                'regex' => '',
                'replace' => '',
                'fullInfo' => 'To get best route, send {message}\nEx: {example}',
                'full' => [
                    'message' => 'ROUTE <source> TO <destination>',
                    'example' => 'ROUTE F-6, Islamabad TO F-10, Islamabad',
                ],
            ],
        ],
        'now' => [
            'default' => [
                'type' => Command::NUMERIC,
                'command' => 'now',
                'replace' => '',
                'shortInfo' => 'To get current traffic situation now, send {message}',
                'fullInfo' => 'To get current traffic situation, send {message}',
                'full' => [
                    'message' => 'NOW',
                ],
            ],
        ],
        'stop' => [
            'default' => [
                'type' => Command::NUMERIC,
                'command' => 'stop',
                'replace' => '',
                'shortInfo' => 'To stop receiving daily notifications, send {message}',
                'fullInfo' => 'To stop receiving daily notifications, send {message}',
                'full' => [
                    'message' => 'STOP'
                ],
            ],
        ],
        'report' => [
            'default' => [
                'type' => Command::REGEX,
                'command' => 'report',
                'regex' => '((accident|congestion|construction|blockade|open)\s+at\s+.*)',
                'replace' => '',
                'shortInfo' => 'To report traffic incident now, {message}\nEx: {example}',
                'fullInfo' => 'To report traffic incident, {message}\nEx: {example}',
                'short' => [
                    'message' => '<congestion/accident/blockade/construction/open> AT <location>',
                    'example' => 'accident AT Faizabad Interchange',
                ],
                'full' => [
                    'message' => 'REPORT <congestion/accident/blockade/construction/open> AT <location>',
                    'example' => 'REPORT accident AT Faizabad Interchange',
                ],
            ],

        ],
        'city' => [
            'default' => [
                'type' => Command::REGEX,
                'command' => 'city',
                'regex' => '',
                'replace' => '',
                'fullInfo' => 'To set your current city, send {message}\nEx: {example}',
                'full' => [
                    'message' => 'CITY <city-name>',
                    'example' => 'CITY ISLAMABAD',
                ],
            ],
            'single_digit' => [
                'type' => Command::MULTI,
                'options' => [
                    [
                        'type' => Command::NUMERIC,
                        'command' => 'city',
                        'replace' => 'CITY ISLAMABAD',
                        'shortInfo' => '{message} for Islamabad',
                        'fullInfo' => '{message} for Islamabad',
                        'full' => [
                            'message' => 'CITY ISLAMABAD',
                        ],
                    ],
                    [
                        'type' => Command::NUMERIC,
                        'command' => 'city',
                        'replace' => 'CITY RAWALPINDI',
                        'shortInfo' => '{message} for Rawalpindi',
                        'fullInfo' => '{message} for Rawalpindi',
                        'full' => [
                            'message' => 'CITY RAWALPINDI',
                        ],
                    ],
                    [
                        'type' => Command::NUMERIC,
                        'command' => 'city',
                        'replace' => 'CITY LAHORE',
                        'shortInfo' => '{message} for Lahore',
                        'fullInfo' => '{message} for Lahore',
                        'full' => [
                            'message' => 'CITY LAHORE',
                        ],

                    ],
                    [
                        'type' => Command::NUMERIC,
                        'command' => 'city',
                        'replace' => 'CITY KARACHI',
                        'shortInfo' => '{message} for Karachi',
                        'fullInfo' => '{message} for Karachi',
                        'full' => [
                            'message' => 'CITY KARACHI',
                        ],
                    ],
                ],
            ],
        ],
        'help' => [
            'default' => [
                'type' => Command::REGEX,
                'regex' => '',
                'replace' => '',
                'fullInfo' => 'To get help on command: Send {message}\nEx: {example}',
                'full' => [
                    'message' => 'HELP <command>',
                    'example' => 'HELP REPORT',
                ],
            ],
        ],
        '$' => [
            'default' => [
                'type' => Command::DUMMY,
            ],
        ],
    ];

    public function generateMessage($command, $shorten = true)
    {
        $messages = [];
        switch ($command['type']) {
            case Command::REGEX:
                if ($shorten && !empty($command['regex'])) {
                    array_push($messages, [
                        'info' => $command['shortInfo'],
                        'params' => array_map(
                            function ($param) {
                                return trim(Yii::$app->params['smsKeyword'] . " " . $param);
                            }
                            , $command['short']),
                    ]);

                    array_push($this->shortcuts, ['command' => $command['command'], 'regex' => $command['regex']]);
                } else {
                    array_push($messages, [
                        'info' => $command['fullInfo'],
                        'params' => array_map(
                            function ($param) {
                                return trim(Yii::$app->params['smsKeyword'] . " " . $param);
                            }
                            , $command['full'])
                    ]);
                }
                break;
            case Command::NUMERIC:
                if ($shorten) {
                    array_push($messages, [
                        'info' => $command['shortInfo'],
                        'params' => [
                            'message' => trim(Yii::$app->params['smsKeyword'] . " " . $this->commandNumber),
                            'example' => trim(Yii::$app->params['smsKeyword'] . " " . $this->commandNumber),
                        ]
                    ]);

                    array_push($this->shortcuts,
                        [
                            'command' => $command['command'],
                            'regex' => "\\b($this->commandNumber)$",
                            'replace' => $command['replace'],
                        ]);
                    $this->commandNumber++;
                } else {
                    array_push($messages, [
                        'info' => $command['fullInfo'],
                        'params' => array_map(
                            function ($param) {
                                return trim(Yii::$app->params['smsKeyword'] . " " . $param);
                            }
                            , $command['full'])
                    ]);

                }
                break;
            case Command::MULTI:
                foreach ($command['options'] as $optionCommand) {
                    array_splice($messages, count($messages), 0,
                        $this->generateMessage($optionCommand, $shorten));
                }

                break;
        }
        return $messages;


    }

    public function generateInfo($keyword, $type = 'default', $shorten = true)
    {

        $command = self::$availableCommands[$keyword][$type];

        $messages = $this->generateMessage($command, $shorten);

        if (!empty($messages)) {
            $output = [];
            foreach ($messages as $message) {
                array_push($output, Yii::t('sms', $message['info'], $message['params']));
            }
            return $output;
        }
        return null;
    }
}