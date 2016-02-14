<?php

namespace console\controllers;


use common\components\SmsSender;
use common\models\Incident;
use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\data\ActiveDataProvider;

class SmsController extends Controller
{

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo->text,true));
                $regex = "/" . Yii::$app->params['smsKeyword'] . "([route|i|urdu|\\s]*)(.*)/i";

//                Yii::info('regex: '. $regex);

                if (preg_match($regex, $mo->text, $output_array)) {

                    // remove the all matching
                    array_shift($output_array);

                    // remove leading and trailing spaces
                    $output_array = array_map('trim', $output_array);

                    $command = strtolower(array_shift($output_array));

                    Yii::info('command: ' . $command);

                    switch ($command) {
                        case 'urdu':
//                            Yii::$app->language = 'ur-PK';
//                            Yii::info(Yii::t('sms', "Pakistan"));

                            break;
                        case 'i':
                        case '':

                            Yii::info('incident command');

                            //command has one optional parameter
                            if(preg_match('/(.*)/i', $output_array[0], $commandParams)) {
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
                                    'and' ,[ '<=', 'startTime' , $currentTime ], [ '>', 'endTime' , $currentTime ],
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

                                if ( !empty($sms) && SmsSender::queueSend($mo->msisdn, $sms)) {
                                    $mo->status = 'processed';
                                } else {
                                    $mo->status = 'queue_error';
                                }

                            }

                            break;
                        case 'route':

                            // route command and three parameters
                            if(preg_match('/(.*)( to )(.*)/i', $output_array[0], $commandParams)) {
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

                                        if (SmsSender::queueSend($mo->msisdn, $sms)) {
                                            $mo->status = 'processed';
                                        } else {
                                            $mo->status = 'queue_error';
                                        }
                                    }
                                    else{
                                        //TODO: send sms that route could not be found
                                    }


                                } else {
                                    // TODO send route format SMS
                                    $mo->status = 'invalid';
                                }
                            }
                            else{
                                // TODO send route format SMS
                                $mo->status = 'invalid';
                            }

                            break;
                        default:
                            //TODO send possible formats SMS
                            $mo->status = 'invalid';

                    }

                    /*
                    $fromCoord = json_decode(Yii::$app->graphHopper->geocode($from));
                    $toCoord = json_decode(Yii::$app->graphHopper->geocode($to));

                    $responseJson = Yii::$app->graphHopper->route(GraphHopper::getLanLongFromGeocode($fromCoord), GraphHopper::getLanLongFromGeocode($toCoord));

                    if ($responseJson) {
                        $response = json_decode($responseJson, true);

                        $result = (new JSONPath($response))->find('$..text');

                        Yii::info(print_r($result, true));

                        // Compose SMS

                        $landmarks = array_reduce($result->data(), function ($landmarks, $text) {
                            Yii::info('current landmarks = ' . print_r($landmarks, true));

                            if (preg_match("/onto (.*)/", $text, $textComp)) {
                                $currentLandmark = trim($textComp[1]);

                                $index = 0;
                                $totalLandmarks = count($landmarks);
                                for ($index = 0; $index < $totalLandmarks; $index++) {

                                    if ($landmarks[$index] == $currentLandmark) {
                                        $landmarks = array_slice($landmarks, 0, $index + 1);
                                        break;
                                    }
                                }
                                if ($index == $totalLandmarks) {
                                    array_push($landmarks, $currentLandmark);
                                }

                            }
                            return $landmarks;

                        }, []);

                        $sms = implode(" > ", $landmarks);
                        $sms .= " [www.roadez.com]";

                        Yii::info($sms);

                        // Send SMS

                        // TODO check return value and update status
                        SmsSender::queueSend($mo->msisdn, $sms);

                        $mo->status = 'processed';
                    } else {
                        SmsSender::queueSend($mo->msisdn, "I am sorry, source or destination address can be determined");
                        $mo->status = 'processing_error';
                    }
                } else {
                    $mo->status = 'error';
                    SmsSender::queueSend($mo->msisdn, "Format: TJAM source address > destination address");
                }
                    */
                } else {
                    $mo->status = 'invalid';
//                    SmsSender::queueSend($mo->msisdn, "Format: TJAM source address > destination address");
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
}