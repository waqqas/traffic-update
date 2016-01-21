<?php

namespace console\controllers;


use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;

use common\components\GraphHopper;
use Flow\JSONPath\JSONPath;
use frontend\controllers\SmsController as SController;

class SmsController extends Controller
{

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo,true));
                if (preg_match("/[tT][jJ][aA][mM] (.*),(.*)/", $mo->text, $output_array)) {
                    array_shift($output_array);

                    $output_array = array_map('trim', $output_array);

                    Yii::info(print_r($output_array, true));

                    if (count($output_array) == 2) {
                        $fromCoord = json_decode(Yii::$app->graphHopper->geocode($output_array[0]));
                        $toCoord = json_decode(Yii::$app->graphHopper->geocode($output_array[1]));

                        $responseJson = Yii::$app->graphHopper->route(GraphHopper::getLanLongFromGeocode($fromCoord), GraphHopper::getLanLongFromGeocode($toCoord));

                        if ($responseJson) {
                            $response = json_decode($responseJson, true);

                            $result = (new JSONPath($response))->find('$..text');

//                            Yii::info(print_r($result, true));

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

                            Yii::info($sms);

                            // Send SMS

                            $query = http_build_query([
                               'to' => $mo->msisdn,
                                'text' => $sms,
                            ]);
                            $url = http_build_url(Yii::$app->params['serverName'],[
                                'path' => '/sms/mt',
                                'query' => $query,

                            ]);

                            $ch = curl_init();

                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                            //TODO check response and update status
                            curl_exec($ch);

                            curl_close($ch);
                        }


                        $mo->status = 'processed';
                    } else {
                        $mo->status = 'error';
                    }
                } else {
                    $mo->status = 'ignored';
                }
                $mo->save();
            }
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    public
    function actionMt(array $ids)
    {
        foreach ($ids as $id) {
            $mt = Smsmt::findOne(['id' => $id, 'status' => 'queued']);
            if ($mt) {
                Yii::$app->sms->send($mt->recipient, $mt->text);

                //TODO: check for status
                $mt->status = "sent";
                $mt->save();
            }
        }
        return Controller::EXIT_CODE_NORMAL;
    }
}