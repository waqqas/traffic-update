<?php

namespace console\controllers;


use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;

use common\components\GraphHopper;
use Flow\JSONPath\JSONPath;

class SmsController extends Controller
{

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            /** @var \common\models\Smsmo $mo */
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
//                Yii::info(print_r($mo,true));
                if(preg_match("/[tT][jJ][aA][mM] (.*),(.*)/", $mo->text, $output_array))
                {
                    array_shift($output_array);

                    $output_array = array_map('trim', $output_array);

                    Yii::info(print_r($output_array, true));

                    if( count($output_array) == 2) {
                        $fromCoord = json_decode(Yii::$app->graphHopper->geocode($output_array[0]));
                        $toCoord = json_decode(Yii::$app->graphHopper->geocode($output_array[1]));

                        $responseJson = Yii::$app->graphHopper->route(GraphHopper::getLanLongFromGeocode($fromCoord), GraphHopper::getLanLongFromGeocode($toCoord));

                        if($responseJson){
                            $response = json_decode($responseJson, true);

                            $result = (new JSONPath($response))->find('$..text');

                            Yii::info(print_r($result, true));

                            // TODO Compose SMS
                            // TODO Send SMS

                        }


                        $mo->status = 'processed';
                    }
                    else{
                        $mo->status = 'error';
                    }
                }
                else{
                    $mo->status = 'ignored';
                }
                $mo->save();
            }
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionMt(array $ids){
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