<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class GraphhopperController extends Controller{

    public $enableCsrfValidation = false;

    public function actionRoute(){
        $from = Yii::$app->request->get('from', '');
        $to = Yii::$app->request->get('to', '');

        return Yii::$app->graphHopper->getRoute($from, $to);

    }
}