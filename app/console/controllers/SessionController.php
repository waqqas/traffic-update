<?php

namespace console\controllers;

use common\models\Session;
use Yii;
use yii\console\Controller;

class SessionController extends Controller{

    public function actionFlushAll(){

        $expiryTime = Yii::$app->session->expirySeconds;

        $query = Session::find()->where(['<', 'updated_at', time() - $expiryTime]);

        foreach($query->all() as $expiredSession){
            $expiredSession->delete();
        }

        return Controller::EXIT_CODE_NORMAL;
    }
}