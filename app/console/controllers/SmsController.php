<?php

namespace console\controllers;


use Yii;
use yii\console\Controller;
use common\models\Smsmo;
use common\models\Smsmt;

class SmsController extends Controller
{

    public function actionMo(array $ids)
    {
        foreach ($ids as $id) {
            $mo = Smsmo::findOne(['id' => $id]);
            if ($mo) {
                Yii::info(print_r($mo,true));
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