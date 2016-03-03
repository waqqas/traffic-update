<?php

namespace console\controllers;


use common\components\SmsSender;
use common\models\Incident;
use common\models\Language;
use common\models\User;
use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\data\ActiveDataProvider;

class SmsController extends Controller
{
    /**
     * @var string JSON encoded string to set $_GET global variable
     */
    public $get = '{}';

    /**
     * @var string JSON encoded string to set $_POST global variable
     */
    public $post = '{}';

    /**
     * @var string JSON encoded string to set $_COOKIE global variable
     */
    public $cookie = '{}';

    /**
     * @var string JSON encoded string to set $_COOKIE global variable
     */
    public $session = '{}';

    public function options($actionID)
    {
        return ['get', 'post', 'cookie', 'session'];
    }

    private function getUserCity()
    {
        return $this->getPropByType('long_name', 'locality');
    }

    private function getUserCountry()
    {
        return $this->getPropByType('long_name', 'country');
    }

    private function getPropByType($prop, $type)
    {
        $location = Yii::$app->user->identity->getPreference('location')->one();

        if (!isset($location)) {
            $value = null;

            switch ($type) {
                case 'locality':
                    $value = Yii::$app->params['defaultCity'];
                    break;
                case 'country':
                    $value = Yii::$app->params['defaultCountry'];
                    break;
            }

            return $value;
        }

        foreach ($location->address_components as $address) {
            foreach ($address->types as $userType) {
                if ($userType == $type) {
                    return $address->{$prop};
                }
            }
        }
    }

    private function matchUserLocation($location)
    {
        $match = false;

        $userCity = $this->getUserCity();
        foreach ($location->address_components as $address) {
            foreach ($address->types as $type) {
                if ($type == 'locality' && $address->long_name == $userCity) {
                    $match = true;
                }
                break;
            }
            if ($match == true) {
                break;
            }
        }

        return $match;
    }

    private function createUser($phoneNumber)
    {
        $user = new User();
        $user->username = $phoneNumber;
        $user->email = '';
        $user->setPassword('');
        $user->generateAuthKey();
        if ($user->save()) {
            return $user;
        }
        return null;

    }

    public function beforeAction($action)
    {
        $_GET = json_decode($this->get, true);
        $_POST = json_decode($this->post, true);
        $_COOKIE = json_decode($this->cookie, true);
        $_SESSION = json_decode($this->session, true);

        Yii::$app->session->open();

        $phoneNumber = isset($_GET['X-PHONE-NUMBER']) ? $_GET['X-PHONE-NUMBER']: '';

        if(empty($phoneNumber)) return false;

        $identity = User::findIdentityByPhoneNumber($phoneNumber);

        if( !$identity ){
            $identity = $this->createUser($phoneNumber);
        }
        Yii::$app->user->setIdentity($identity);

        // get user's language preference
        $language = Yii::$app->user->identity->getPreference('language')->one();

        if(!$language)
            $language = Yii::$app->sourceLanguage;
        else
            $language = $language->value;

        Yii::$app->language = $language;

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        Yii::$app->session->close();
        return parent::afterAction($action, $result);
    }

    public static function getCommand($command, $phoneNumber, $params = [])
    {
        // convert string to array
        if (is_scalar($params)) {
            $params = [$params];
        }

        $get = [
            'X-PHONE-NUMBER' => $phoneNumber,
        ];

        $cookie = [
            'PHPSESSID' => substr($phoneNumber, 1),     // remove + sign
        ];

        return implode(' ', array_merge(
            [
                'sms/' . $command,
            ],
            $params,
            [
                "--get='" . json_encode($get) . "'",
                "--cookie='" . json_encode($cookie) . "'",
            ]
        ));

    }

    public function actionMo($id)
    {
        /** @var \common\models\Smsmo $mo */
        $mo = Smsmo::findOne(['id' => $id]);
        if ($mo) {

            $regex = "/" . Yii::$app->params['smsKeyword'] . "\\s*([route|language|sub|now|unsub|report|city|help]*)(.*)/i";

            if (preg_match($regex, $mo->text, $output_array)) {

                // remove the all matching
                array_shift($output_array);

                // remove leading and trailing spaces
                $output_array = array_map('trim', $output_array);

                $command = strtolower(array_shift($output_array));

                // default command
                if (empty($command)) {
                    $command = 'help';
                }

                Yii::info('command: ' . $command);

                if (in_array($command, ['sub', 'language', 'route', 'now', 'unsub', 'report', 'city', 'help'])) {

                    /** @var \libphonenumber\PhoneNumber $phone */
                    $phone = Yii::$app->phone->parse($mo->msisdn, 'PK');

                    Yii::$app->ga->track([
                        'ec' => 'sms',
                        'ea' => $command,
                        'uid' => $mo->msisdn,
                        'geoid' => Yii::$app->phone->getRegionCodeForNumber($phone),
                    ]);

                    $runCommand = self::getCommand($command, $mo->msisdn, '"' . $output_array[0] . '"');

                    Yii::$app->consoleRunner->run($runCommand);

                    $mo->status = 'processed';

                } else {
                    //TODO send possible formats SMS
                    $mo->status = 'invalid';

                }
            } else {
                $mo->status = 'invalid';
            }
            $mo->save();
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionMt(array $ids)
    {
        foreach ($ids as $id) {
            $mt = Smsmt::findOne(['id' => $id, 'status' => 'queued']);
            if ($mt) {
                if (Yii::$app->sms->send($mt->recipient, $mt->text)) {
                    $mt->message_id = Yii::$app->sms->lastMsgInfo['messageid'];
                    $mt->status = "sent";
                } else {
                    $mt->status = 'error';
                }

                $mt->save();
            }
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionSendAll($status = 'queued')
    {
        $mts = Smsmt::findAll(['status' => $status]);
        foreach ($mts as $mt) {
            if (Yii::$app->sms->send($mt->recipient, $mt->text)) {
                $mt->message_id = Yii::$app->sms->lastMsgInfo['messageid'];
                $mt->status = "sent";
            } else {
                $mt->status = 'error';
            }
            $mt->save();
        }
    }

    public function actionSub($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        if (preg_match('/(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)\s*(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)/i', $paramString,
            $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            if (empty($commandParams[0])) {
                $commandParams[0] = '8';  // 8 am
            }
            if (empty($commandParams[1])) {
                $commandParams[1] = '00';  // 0 minutes
            }

            if (empty($commandParams[2])) {
                $commandParams[2] = '5';  // 5 pm
            }
            if (empty($commandParams[3])) {
                $commandParams[3] = '00';  // 0 minutes
            }


            $amTime = date('G:i', strtotime($commandParams[0] . ":" . $commandParams[1] . "am"));
            $pmTime = date('G:i', strtotime($commandParams[2] . ":" . $commandParams[3] . "pm"));


            $command = self::getCommand('now', Yii::$app->user->getPhoneNumber());

            Yii::info("SMS sending times: " . $amTime . " and " . $pmTime);

            /** @var \common\components\Schedule $schedule */
            $schedule = Yii::$app->schedule;

            $schedule->command($command)->dailyAt($amTime);
            $schedule->command($command)->dailyAt($pmTime);

            Yii::$app->user->setEvents($schedule->getEvents());

            $sms = Yii::t('sms', 'You will receive SMS daily at {amTime} and {pmTime}', [
                'amTime' => $amTime,
                'pmTime' => $pmTime,
            ]);

            $sms .= "\n";

            $sms .= Yii::t('sms', 'Send {message} at {shortCode} to unsubscribe', [
                'message' => Yii::$app->params['smsKeyword'] . " UNSUB",
                'shortCode' => Yii::$app->params['smsShortCode'],
            ]);

            SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

        }

        return $status;
    }

    public function actionLanguage($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        if (preg_match('/(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            if (count($commandParams) == 1) {

                switch (strtolower($commandParams[0])) {
                    case 'english':
                        $language = 'English (US)';
                        break;
                    default:
                        $language = ucfirst($commandParams[0]);
                }

                /** @var \common\models\Language $lang */
                $lang = Language::findOne([
                    'name_ascii' => $language,
                    'status' => 1,
                ]);

                if ($lang) {
                    Yii::$app->user->setLanguage($lang->language_id);

                    Yii::$app->language = $lang->language_id;

                    $sms = Yii::t('sms', 'You will now receive SMS in {language}', [
                        'language' => $lang->name,
                    ]);

                    SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);
                } else {
                    //TODO: send error SMS
                    $status = Controller::EXIT_CODE_ERROR;
                }
            } else {
                // TODO: send error SMS
                $status = Controller::EXIT_CODE_ERROR;
            }
        }
    }

    public function actionNow($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        //command has one optional parameter
        if (preg_match('/(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);


            // one optional parameter to route command
            if (count($commandParams) == 1) {
                $location = $commandParams[0];
            } else {
                $location = $this->getUserCity();
            }

            $currentTime = time();

            $query = Incident::find()
                ->where([
                    'enabled' => 1,
                ])
                ->andWhere([
                    'and',
                    ['<=', 'startTime', $currentTime],
                    ['>', 'endTime', $currentTime],
                ])->orderBy(['severity' => SORT_DESC]);

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 5,
                ]
            ]);

            $incidents = $dataProvider->getModels();

            $sms = Yii::$app->formatter->asSMS($incidents);

            Yii::info("sms:" . $sms);

            SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

        }

        return $status;
    }

    public function actionRoute($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        // route command and three parameters
        if (preg_match('/(.*)( to )(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            // three parameters to route command
            if (count($commandParams) == 3) {

                $from = $commandParams[0];
                $to = $commandParams[2];

                $userCountry = $this->getUserCountry();
                $fromAddresses = Yii::$app->googleMaps->geocode($from, $userCountry);

                $toAddresses = Yii::$app->googleMaps->geocode($to, $userCountry);

                $from = $fromAddresses[0]->geometry->location->lat . "," . $fromAddresses[0]->geometry->location->lng;
                $to = $toAddresses[0]->geometry->location->lat . "," . $toAddresses[0]->geometry->location->lng;

                Yii::info('from lat,lng =' . $from);
                Yii::info('to lat, lng =' . $to);

                $routeResponse = Yii::$app->mapQuest->route($from, $to);

                if ($routeResponse) {

                    $prefix = '[' . $fromAddresses[0]->formatted_address . ' to ' . $toAddresses[0]->formatted_address . '] ';
                    $sms = Yii::$app->formatter->asSMS($routeResponse, $prefix);

                    Yii::info($sms);

                    SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);
                } else {
                    //TODO: send sms that route could not be found
                    $status = Controller::EXIT_CODE_ERROR;
                }


            } else {
                // TODO send route format SMS
                $status = Controller::EXIT_CODE_ERROR;
            }
        } else {
            // TODO send route format SMS
            $status = Controller::EXIT_CODE_ERROR;

        }

        return $status;
    }

    public function actionUnsub($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        Yii::$app->user->setEvents(null);

        $sms = Yii::t('sms', 'You will not receive daily SMS');

        SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

        return $status;
    }

    public function actionReport($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        // route command and three parameters
        if (preg_match('/(.*)( at )(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            // three parameters to route command
            if (count($commandParams) == 3) {
                $incidentType = $commandParams[0];
                $location = $commandParams[2];

                $incident = new Incident();

                $incidentText = 'unknown event';

                switch ($incidentType) {
                    case 'construction':
                        $incident->type = 1;
                        $incidentText = 'construction';
                        break;
                    case 'blockade':
                        $incident->type = 2;
                        $incidentText = 'blockade';
                        break;
                    case 'congestion':
                        $incident->type = 3;
                        $incidentText = 'congestion';
                        break;
                    case 'accident':
                        $incident->type = 4;
                        $incidentText = 'accident';
                        break;
                    default:
                        $incident->type = 0;
                        $incidentText = 'unknown event';
                }

                $incidentLocation = Yii::$app->googleMaps->geocode($location);

                if (count($incidentLocation) > 0) {

                    if ($this->matchUserLocation($incidentLocation[0])) {

                        $incident->lat = $incidentLocation[0]->geometry->location->lat;
                        $incident->lng = $incidentLocation[0]->geometry->location->lng;
                        $incident->location = $incidentLocation[0]->formatted_address;
                        $incident->description = ucfirst($incidentText);
                        $incident->severity = 1;
                        $incident->eventCode = 1;
                        $incident->startTime = time();
                        $incident->endTime = $incident->startTime + (30 * 60);  // 30 minutes
                        $incident->delayFromFreeFlow = 10;
                        $incident->delayFromTypical = 10;
                        $incident->created_at = 0;
                        $incident->updated_at = 0;

                        //disable it, till it is confirmed by the admin
                        $incident->enabled = 0;

                        $incident->save();

                        $sms = Yii::t('sms', 'Thank you for reporting {incident} at {location}', [
                            'incident' => Yii::t('sms', $incidentText),
                            'location' => Yii::t('sms', $incidentLocation[0]->formatted_address)
                        ]);

                        SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);
                    } else {
                        $sms = Yii::t('sms', 'Sorry, you can not report in {location}', [
                            'location' => Yii::t('sms', $incidentLocation[0]->formatted_address),
                        ]);
                        $sms .= "\n";
                        $sms .= Yii::t('sms', 'Please change your city by sending {message} to {shortCode}', [
                            'message' => 'TUP CITY <city-name>',
                            'shortCode' => Yii::$app->params['smsShortCode'],
                        ]);

                        SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);
                    }

                } else {
                    $sms = Yii::t('sms', 'Sorry, {location} is not correct', [
                        'location' => $location,
                    ]);

                    SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

                }
            } else {
                $status = Controller::EXIT_CODE_ERROR;
            }
        } else {
            $status = Controller::EXIT_CODE_ERROR;
        }

        return $status;
    }

    public function actionCity($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        //command has one optional parameter
        if (preg_match('/(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            if (count($commandParams) > 0) {

                $location = Yii::$app->googleMaps->geocode($commandParams[0]);

                if (count($location) > 0) {

                    $localityFound = null;
                    foreach ($location[0]->address_components as $address) {
                        foreach ($address->types as $type) {
                            if ($type == 'locality') {
                                $localityFound = $address;
                            }
                            break;
                        }
                        if ($localityFound != null) {
                            break;
                        }
                    }

                    if ($localityFound) {
                        Yii::$app->user->setLocation($localityFound);

                        $sms = Yii::t('sms', 'You will receive notifications of {city}', [
                            'city' => Yii::t('sms', $localityFound->long_name),
                        ]);

                        SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

                    } else {
                        $status = Controller::EXIT_CODE_ERROR;
                    }

                } else {
                    $status = Controller::EXIT_CODE_ERROR;
                }
            }
        }

        return $status;
    }

    public function actionHelp($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $sms = Yii::t('sms', 'Help Menu:\n');

        if (empty($paramString)) {
            $paramString = 'now city';
        }

        foreach (explode(' ', $paramString) as $command) {
            switch (strtolower($command)) {
                case 'language':
                    $sms .= Yii::t('sms', 'To select language: Send {message}\nEx: {example}', [
                        'message' => 'TUP LANGUAGE <urdu/english> ',
                        'example' => 'TUP LANGUAGE URDU',
                    ]);
                    break;
                case 'sub':
                    $sms .= Yii::t('sms', 'To subscribe to daily notifications: Send {message}\nEx: {example}', [
                        'message' => 'TUP SUB <AM time> <PM time>',
                        'example' => 'TUP SUB 8:30 5:00'
                    ]);
                    break;
                case 'now':
                    $sms .= Yii::t('sms', 'To get current traffic situation: Send {message}', [
                        'message' => 'TUP NOW',
                    ]);
                    break;
                case 'unsub':
                    $sms .= Yii::t('sms', 'To unsubscribe from daily notifications: Send {message}', [
                        'message' => 'TUP UNSUB'
                    ]);
                    break;
                case 'report':
                    $sms .= Yii::t('sms', 'To report traffic problem: Send {message}\nEx: {example}', [
                        'message' => 'TUP REPORT <congestion/accident/blockade/construction> AT <location>',
                        'example' => 'TUP REPORT accident AT Faizabad Interchange',
                    ]);
                    break;
                case 'city':
                    $sms .= Yii::t('sms', 'To set your city: Send {message}\nEx: {example}', [
                        'message' => 'TUP CITY <city-name>',
                        'example' => 'TUP CITY ISLAMABAD',

                    ]);
                    break;
                case 'help':
                    $sms .= Yii::t('sms', 'To get help on command: Send {message}\nEx: {example}', [
                        'message' => 'TUP HELP <command>',
                        'example' => 'TUP HELP SUB',

                    ]);
                    break;
            }
            $sms .= "\n\n";

        }
        SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);

        return $status;
    }

    public function actionGlobal()
    {
        Yii::info('get: ' . print_r($_GET, true));
        Yii::info('post: ' . print_r($_POST, true));
        Yii::info('cookie: ' . print_r($_COOKIE, true));
        Yii::info('session: ' . print_r($_SESSION, true));

        @session_start();

    }
}