<?php

namespace console\controllers;

use common\models\Session;
use Yii;
use yii\console\Controller;

class SessionController extends Controller{

    public function actionFlushAll(){

        $expiryTime = Yii::$app->session->expirySeconds;

        $sessionDeleted = Session::deleteAll(['<', 'updated_at', time() - $expiryTime]);

        Yii::info('Number of sessions deleted:' . $sessionDeleted);


        return Controller::EXIT_CODE_NORMAL;
    }
}