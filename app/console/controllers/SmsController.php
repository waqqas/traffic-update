<?php

namespace console\controllers;

use common\models\Smsmo;
use Yii;
use yii\console\Controller;

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
}