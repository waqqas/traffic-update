<?php

namespace console\controllers;


use common\components\SmsSender;
use common\models\Incident;
use common\models\Language;
use pheme\settings\models\Setting;
use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\data\ActiveDataProvider;

class SmsController extends Controller
{


    private function loadSettings($msisdn)
    {

        // set user's preferred language as application language

//        $language = Yii::$app->settings->get("$msisdn.language");


        // @var \pheme\settings\models\Setting $setting
        $setting = Setting::findOne([
            'key' => 'language',
            'section' => $msisdn,
            'active' => 1,
        ]);

        if ($setting == null) {
            $language = Yii::$app->sourceLanguage;
        } else {
            $language = $setting->value;
        }

        Yii::$app->language = $language;

        Yii::$app->setComponents([
           'smsUser' => [
               'class' => 'StdClass'
           ]
        ]);

        $city = Yii::$app->settings->get("$msisdn.city") != null? Yii::$app->settings->get("$msisdn.city") : Yii::$app->params['defaultLocation'];

        Yii::$app->smsUser->city = $city;

    }

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo->text,true));
                $regex = "/" . Yii::$app->params['smsKeyword'] . "\\s*([route|lang|sub|now|unsub|report|city]*)(.*)/i";

//                Yii::info('regex: '. $regex);

                if (preg_match($regex, $mo->text, $output_array)) {

                    // remove the all matching
                    array_shift($output_array);

                    // remove leading and trailing spaces
                    $output_array = array_map('trim', $output_array);

                    $command = strtolower(array_shift($output_array));

                    // default command
                    if (empty($command)) {
                        $command = 'now';
                    }

                    Yii::info('command: ' . $command);

                    if (in_array($command, ['sub', 'lang', 'route', 'now', 'unsub', 'report', 'city'])) {
                        $runCommand = implode(' ', [
                            'sms/' . $command,
                            $mo->msisdn,
                            '"' . $output_array[0] . '"',
                        ]);

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

    public function actionSub($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

        if (preg_match('/(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)\s*(0?\d*|1*[0-2]*):*(0\d*|[0-5]*\d*)/i', $paramString, $commandParams)) {
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


            // TODO: change city in command when user changes city
            $command = implode(' ', [
                'sms/now',
                $msisdn,
                Yii::$app->smsUser->city
            ]);

            Yii::info("SMS sending times: " . $amTime . " and " . $pmTime);

            /** @var \common\components\Schedule $schedule */
            $schedule = Yii::$app->schedule;

            $schedule->command($command)->dailyAt($amTime);
            $schedule->command($command)->dailyAt($pmTime);

            $events = serialize($schedule->getEvents());

            Yii::$app->settings->set("$msisdn.events", base64_encode($events));

            $sms = Yii::t('sms', 'You will receive SMS daily at {amTime} and {pmTime}', [
                'amTime' => $amTime,
                'pmTime' => $pmTime,
            ]);

            $sms .= "\n";

            $sms .= Yii::t('sms', 'Send {message} at {shortCode} to unsubscribe', [
                'message' => Yii::$app->params['smsKeyword'] . " UNSUB",
                'shortCode' => Yii::$app->params['smsShortCode'],
            ]);

            SmsSender::queueSend($msisdn, $sms);

        }

        return $status;
    }

    public function actionLang($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

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
                    Yii::$app->settings->set("$msisdn.language", $lang->language_id);

                    Yii::$app->language = $lang->language_id;

                    $sms = Yii::t('sms', 'You will now receive SMS in {language}', [
                        'language' => $lang->name,
                    ]);

                    SmsSender::queueSend($msisdn, $sms);
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

    public function actionNow($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

        //command has one optional parameter
        if (preg_match('/(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            $location = Yii::$app->smsUser->city;

            // one optional parameter to route command
            if (count($commandParams) == 1) {
                $location = $commandParams[0];
            }

            $currentTime = time();

            $query = Incident::find()
                ->where([
                    'enabled' => 1,
                ])
                ->andWhere([
                    'and', ['<=', 'startTime', $currentTime], ['>', 'endTime', $currentTime],
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

            // Send SMS

            if (SmsSender::queueSend($msisdn, $sms)) {
//                $mo->status = 'processed';
            } else {
//                $mo->status = 'queue_error';
                $status = Controller::EXIT_CODE_ERROR;

            }

        }

        return $status;
    }

    public function actionRoute($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

        // route command and three parameters
        if (preg_match('/(.*)( to )(.*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            // three parameters to route command
            if (count($commandParams) == 3) {

                $from = $commandParams[0];
                $to = $commandParams[2];

                $fromAddresses = Yii::$app->googleMaps->geocode($from, Yii::$app->smsUser->city);

                $toAddresses = Yii::$app->googleMaps->geocode($to, Yii::$app->smsUser->city);

                $from = $fromAddresses[0]->geometry->location->lat . "," . $fromAddresses[0]->geometry->location->lng;
                $to = $toAddresses[0]->geometry->location->lat . "," . $toAddresses[0]->geometry->location->lng;

                Yii::info('from lat,lng =' . $from);
                Yii::info('to lat, lng =' . $to);

                $routeResponse = Yii::$app->mapQuest->route($from, $to);

                if ($routeResponse) {

                    $prefix = '[' . $fromAddresses[0]->formatted_address . ' to ' . $toAddresses[0]->formatted_address . '] ';
                    $sms = Yii::$app->formatter->asSMS($routeResponse, $prefix);

                    Yii::info($sms);

                    // Send SMS

                    if (SmsSender::queueSend($msisdn, $sms)) {
//                        $mo->status = 'processed';
                    } else {
//                        $mo->status = 'queue_error';
                        $status = Controller::EXIT_CODE_ERROR;
                    }
                } else {
                    //TODO: send sms that route could not be found
                    $status = Controller::EXIT_CODE_ERROR;
                }


            } else {
                // TODO send route format SMS
//                $mo->status = 'invalid';
                $status = Controller::EXIT_CODE_ERROR;
            }
        } else {
            // TODO send route format SMS
//            $mo->status = 'invalid';
            $status = Controller::EXIT_CODE_ERROR;

        }

        return $status;
    }

    public function actionUnsub($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

        /** @var \pheme\settings\components\Settings $settings */
        $settings = Yii::$app->settings;

        $settings->delete("$msisdn.events");

        $sms = Yii::t('sms', 'You will not receive daily SMS');

        SmsSender::queueSend($msisdn, $sms);

        return $status;
    }

    public function actionReport($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

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
                    case 'event':
                        $incident->type = 2;
                        $incidentText = 'event';
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

                $incidentLocation = Yii::$app->googleMaps->geocode($location, Yii::$app->smsUser->city);

                if (count($incidentLocation) > 0) {

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

                    SmsSender::queueSend($msisdn, $sms);
                } else {
                    $sms = Yii::t('Sorry, {location} is not a valid location', [
                        'location' => $location,
                    ]);

                    SmsSender::queueSend($msisdn, $sms);

                }
            } else {
                $status = Controller::EXIT_CODE_ERROR;
            }
        } else {
            $status = Controller::EXIT_CODE_ERROR;
        }

        return $status;
    }

    public function actionCity($msisdn, $paramString)
    {
        $status = Controller::EXIT_CODE_NORMAL;

        $this->loadSettings($msisdn);

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
                            if ($type == 'locality')
                                $localityFound = $address;
                            break;
                        }
                        if ($localityFound != null)
                            break;
                    }

                    if ($localityFound) {
                        Yii::$app->settings->set("$msisdn.city", $localityFound->long_name);

                        $sms = Yii::t('sms', 'You will receive notifications of {city}', [
                            'city' => Yii::t('sms', $localityFound->long_name),
                        ]);

                        SmsSender::queueSend($msisdn, $sms);

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
}