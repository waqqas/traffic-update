<?php

namespace console\controllers;

use yii\console\Controller;

class SessionController extends Controller{

    public function actionGc()
    {

        \Yii::$app->session->gcSession(\Yii::$app->params['sessionExpirySeconds']);
    }
}