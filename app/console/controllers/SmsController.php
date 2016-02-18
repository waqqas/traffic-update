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


    private function loadSettings($msisdn){

        // set user's preferred language as application language

//        $language = Yii::$app->settings->get("$msisdn.language");


        // @var \pheme\settings\models\Setting $setting
        $setting = Setting::findOne([
            'key' => 'language',
            'section' => $msisdn,
            'active' => 1,
        ]);

        if( $setting == null){
            $language = Yii::$app->sourceLanguage;
        }
        else{
            $language = $setting->value;
        }

        Yii::$app->language = $language;
    }

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo->text,true));
                $regex = "/" . Yii::$app->params['smsKeyword'] . "\\s*([route|lang|sub|now|unsub]*)(.*)/i";

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

                    if (in_array($command, ['sub', 'lang', 'route', 'now', 'unsub'])) {
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

        if (preg_match('/([0-9|am|pm|:]*)\s*([0-9|am|pm|:]*)/i', $paramString, $commandParams)) {
            // remove the all matching
            array_shift($commandParams);

            $commandParams = array_map('trim', $commandParams);

            if (empty($commandParams[0])) {
                $commandParams[0] = '08:00';
            } else {
                if (is_numeric($commandParams[0])) $commandParams[0] .= 'am';
                // convert user given time to 24-hour format
                $commandParams[0] = date('G:i', strtotime($commandParams[0]));
            }

            if (empty($commandParams[1])) {
                $commandParams[1] = '17:00';
            } else {
                if (is_numeric($commandParams[1])) $commandParams[1] .= 'pm';
                // convert user given time to 24-hour format
                $commandParams[1] = date('G:i', strtotime($commandParams[1]));
            }

            $command = implode( ' ', [
                'sms/now',
                $msisdn,
                "islamabad",        // TODO: get user's preferred location
            ]);

            Yii::info( "SMS sending times: " . $commandParams[0] . " and " . $commandParams[1]);

            /** @var \common\components\Schedule $schedule */
            $schedule = Yii::$app->schedule;

            $schedule->command($command)->dailyAt($commandParams[0]);
            $schedule->command($command)->dailyAt($commandParams[1]);

            $events = serialize($schedule->getEvents());

            Yii::$app->settings->set("$msisdn.events", base64_encode($events));

            $sms = Yii::t('sms', 'You will receive SMS daily at {amTime} and {pmTime}', [
                'amTime' => $commandParams[0],
                'pmTime' => $commandParams[1]
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

            $location = Yii::$app->params['defaultLocation'];

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

            if (!empty($sms) && SmsSender::queueSend($msisdn, $sms)) {
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

                $fromAddresses = Yii::$app->googleMaps->geocode($from);

                /** @var \Geocoder\1Model\AddressCollection $toAddresses */
                $toAddresses = Yii::$app->googleMaps->geocode($to);

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

}