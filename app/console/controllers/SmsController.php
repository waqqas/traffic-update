<?php

namespace console\controllers;


use common\models\Incident;
use common\models\Language;
use console\components\sms\Command;
use Yii;
use console\components\sms\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\data\ActiveDataProvider;

class SmsController extends Controller
{


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

        $address = $location->value;
        foreach ($address->types as $userType) {
            if ($userType == $type) {
                return $address->{$prop};
            }
        }
        return null;
    }

    public function init()
    {
        parent::init();


        // action to state mapping
        $this->userTransitions = [
            'daily' => 'daily',
            'now' => 'demand',
            'stop' => 'demand',
        ];

        $this->sessionActions = ['mo', 'mt', 'send-all'];
    }


    private function matchUserLocation($location)
    {
        // FIXME: disabled user's location mapping
        $match = true;

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

    public function runCommand($command, $paramString)
    {

        $success = true;

        Yii::info('command: ' . $command);

        $keywords = array_keys(Command::$availableCommands);

        if (in_array($command, $keywords)) {

            $phoneNumber = Yii::$app->user->getPhoneNumber();
            /** @var \libphonenumber\PhoneNumber $phone */
            $phone = Yii::$app->phone->parse($phoneNumber, 'PK');

            Yii::$app->ga->track([
                'ec' => 'sms',
                'ea' => $command,
                'uid' => $phoneNumber,
                'geoid' => Yii::$app->phone->getRegionCodeForNumber($phone),
            ]);

            $this->runAction($command, [$paramString]);

            $success = true;
        } else {
            $success = false;

        }

        return $success;

    }

    public function actionMo($id)
    {
        /** @var \common\models\Smsmo $mo */
        $mo = Smsmo::findOne(['id' => $id]);
        if ($mo) {
            foreach (array_keys(Command::$availableCommands) as $keyword) {
                $regex = "/" . Yii::$app->params['smsKeyword'] . "\\s*($keyword)(.*)/i";


                if (preg_match($regex, $mo->text, $output_array)) {

                    // remove the all matching
                    array_shift($output_array);

                    // remove leading and trailing spaces
                    $output_array = array_map('trim', $output_array);

                    $command = strtolower(array_shift($output_array));

                    // default command
                    if (empty($command)) {
                        $command = 'daily';
                    }


                    if ($this->runCommand($command, $output_array[0])) {
                        $mo->status = 'processed';
                    } else {
                        $mo->status = 'invalid';
                    }
                    break;

                }
            }

            $shortCuts = Yii::$app->session->get('shortcuts', []);

            if (!empty($shortCuts)) {
                foreach ($shortCuts as $info) {

                    $regex = "/" . Yii::$app->params['smsKeyword'] . "\\s*" . $info['regex'] . "/i";

                    if (preg_match($regex, $mo->text, $output_array)) {

                        // remove the all matching
                        array_shift($output_array);

                        $params = empty($info['replace']) ? array_shift($output_array) : $info['replace'];

                        if ($this->runCommand($info['command'], $params)) {
                            $mo->status = 'processed';
                        } else {
                            $mo->status = 'invalid';
                        }
                        break;

                    }
                }
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


    // SMS commands

    public function actionDaily($paramString = '')
    {
        $status = Controller::EXIT_CODE_NORMAL;

        if (preg_match('/(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)\s*(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)/i', $paramString,
            $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            if (empty($commandParams[0])) {
                $commandParams[0] = Yii::$app->params['daily']['amHours'];
            }
            if (empty($commandParams[1])) {
                $commandParams[1] = Yii::$app->params['daily']['amMinutes'];
            }

            if (empty($commandParams[2])) {
                $commandParams[2] = Yii::$app->params['daily']['pmHours'];
            }
            if (empty($commandParams[3])) {
                $commandParams[3] = Yii::$app->params['daily']['pmMinutes'];
            }


            $amTime = date('G:i', strtotime($commandParams[0] . ":" . $commandParams[1] . "am"));
            $pmTime = date('G:i', strtotime($commandParams[2] . ":" . $commandParams[3] . "pm"));


            $command = self::getCommand('now', Yii::$app->user->getPhoneNumber());

            /** @var \common\components\Schedule $schedule */
            $schedule = Yii::$app->schedule;

            $schedule->command($command)->dailyAt($amTime);
            $schedule->command($command)->dailyAt($pmTime);

            Yii::$app->user->setSmsSchedule($schedule->getEvents());

            /** @var \console\components\sms\Response $response */
            $response = Yii::$app->response;

            $response->addContent(Yii::t('sms', 'You will now receive SMS daily at {amTime} and {pmTime}', [
                'amTime' => date('g:i A', strtotime($amTime)),
                'pmTime' => date('g:i A', strtotime($pmTime)),
            ]));

            $smsCommand = new Command();
            $response->addContent($smsCommand->generateInfo('daily', 'change'));
            $response->addContent($smsCommand->generateInfo('stop', 'default', false));
            $response->addContent($smsCommand->generateInfo('now', 'default', false));

            $response->addSession('shortcuts', $smsCommand->shortcuts);
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

                    /** @var \console\components\sms\Response $response */
                    $response = Yii::$app->response;

                    $response->addContent(Yii::t('sms', 'You will now receive SMS in {language}', [
                        'language' => $lang->name,
                    ]));

                    $smsCommand = new Command();
                    foreach (['now', 'daily', 'city'] as $command) {
                        $response->addContent($smsCommand->generateInfo($command));
                    }

                    $response->addSession('shortcuts', $smsCommand->shortcuts);

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

    public function actionNow($paramString = '')
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


            /** @var \console\components\sms\Response $response */
            $response = Yii::$app->response;

            $response->addContent(Yii::$app->formatter->asSMS($incidents));

            $smsCommand = new Command();

            $response->addContent($smsCommand->generateInfo('report'));

            if (Yii::$app->user->getState() != 'UserWorkflow/daily') {
                $response->addContent($smsCommand->generateInfo('daily'));
            } else {
                // user already subscribed to daily SMS
                // give user option to change the timings
                $response->addContent($smsCommand->generateInfo('daily', 'change'));
            }

            $response->addSession('shortcuts', $smsCommand->shortcuts);

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

//                Yii::info('from lat,lng =' . $from);
//                Yii::info('to lat, lng =' . $to);

                $routeResponse = Yii::$app->mapQuest->route($from, $to);

                /** @var \console\components\sms\Response $response */
                $response = Yii::$app->response;

                if ($routeResponse) {

                    $prefix = '[' . $fromAddresses[0]->formatted_address . ' to ' . $toAddresses[0]->formatted_address . '] ';

                    $response->addContent(Yii::$app->formatter->asSMS($routeResponse, $prefix));

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

    public function actionStop($paramString = '')
    {
        $status = Controller::EXIT_CODE_NORMAL;

        Yii::$app->user->setSmsSchedule(null);

        /** @var \console\components\sms\Response $response */
        $response = Yii::$app->response;

        $response->addContent(Yii::t('sms', 'You will not receive daily SMS'));

        $smsCommand = new Command();
        $response->addContent($smsCommand->generateInfo('daily'));

        $response->addSession('shortcuts', $smsCommand->shortcuts);

        return $status;
    }

    public function actionReport($paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        // route command and three parameters
        if (preg_match('/(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $incidentType = 'congestion';
            $location = $commandParams[0];

            $incident = new Incident();

            $incidentText = 'unknown';

            switch ($incidentType) {
                case 'open':
                    $incident->type = 0;
                    $incidentText = 'open';
                    break;
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
                    $incident->type = 100;
                    $incidentText = 'unknown event';
            }

            $incident->lat = 0;
            $incident->lng = 0;
            $incident->location = $location;
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

            /** @var \console\components\sms\Response $response */
            $response = Yii::$app->response;

            $response->addContent(Yii::t('sms',
                'Thank you for reporting.'));

            $response->addContent(Yii::t('sms',
                'Your report will be verified from other sources before being sent to others.'));

//                    $smsCommand = new Command();
//                    $response->addContent($smsCommand->generateInfo('report', 'default', false));
//
//                    $response->addSession('shortcuts', $smsCommand->shortcuts);


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

                        /** @var \console\components\sms\Response $response */
                        $response = Yii::$app->response;

                        $response->addContent(Yii::t('sms', 'You will receive notifications of {city}', [
                            'city' => Yii::t('sms', $localityFound->long_name),
                        ]));

                        $smsCommand = new Command();
                        $response->addContent($smsCommand->generateInfo('now'));
                        $response->addContent($smsCommand->generateInfo('daily'));

                        $response->addSession('shortcuts', $smsCommand->shortcuts);

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

    public function actionHelp($paramString = '')
    {
        $status = Controller::EXIT_CODE_NORMAL;

        /** @var \console\components\sms\Response $response */
        $response = Yii::$app->response;

        $response->addContent(Yii::t('sms', 'Menu:'));

        if (empty($paramString)) {
            $paramString = 'now daily city';
        }

        $smsCommand = new Command();

        foreach (explode(' ', $paramString) as $command) {
            $response->addContent($smsCommand->generateInfo(strtolower($command)));
        }

        $response->addSession('shortcuts', $smsCommand->shortcuts);

        return $status;
    }

    public function actionTest($keyword, $type = 'default', $shorten = true)
    {

        $smsCommand = new Command();

        print "output: $keyword \n ";
        print_r($smsCommand->generateInfo($keyword, $type, $shorten));

        print "shortcuts: $keyword \n ";
        print_r($smsCommand->shortcuts);


        return Controller::EXIT_CODE_NORMAL;
    }
}