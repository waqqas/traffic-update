<?php

namespace console\controllers;


use common\components\SmsSender;
use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;

class SmsController extends Controller
{

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo,true));
                if (preg_match("/" . Yii::$app->params['smsKeyword'] . " (ROUTE) (.*)([<>])(.*)/i", $mo->text, $output_array)) {

//                    Yii::info(print_r($output_array, true));

                    // remove the all matching
                    array_shift($output_array);

                    $output_array = array_map('trim', $output_array);
                    $command = strtolower(array_shift($output_array));


                    switch ($command) {
                        case 'route':
                            // three parameters to route command
                            if (count($output_array) == 3) {
                                if ($output_array[1] == ">") {
                                    $from = $output_array[0];
                                    $to = $output_array[2];
                                } else {
                                    $from = $output_array[2];
                                    $to = $output_array[0];

                                }

                                $fromAddresses = Yii::$app->googleMaps->geocode($from);
//                                Yii::info(('froms: ' . print_r($fromAddresses, true)));

                                /** @var \Geocoder\Model\AddressCollection $toAddresses */
                                $toAddresses = Yii::$app->googleMaps->geocode($to);
//                                Yii::info(('tos: ' . print_r($toAddresses, true)));

                                $from = $fromAddresses[0]->geometry->location->lat . "," . $fromAddresses[0]->geometry->location->lng;
                                $to = $toAddresses[0]->geometry->location->lat . "," . $toAddresses[0]->geometry->location->lng;

                                Yii::info('from =' . $from);
                                Yii::info('to =' . $to);

                                $routeResponse = Yii::$app->mapQuest->route($from, $to);

                                if ($routeResponse) {

                                    $prefix = '['. $fromAddresses[0]->formatted_address . ' > ' . $toAddresses[0]->formatted_address .'] ';
                                    $sms = Yii::$app->formatter->asSMS($routeResponse, $prefix);

                                    Yii::info($sms);

                                    // Send SMS

                                    if (SmsSender::queueSend($mo->msisdn, $sms)) {
                                        $mo->status = 'processed';
                                    } else {
                                        $mo->status = 'queue_error';
                                    }
                                }


                            } else {
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