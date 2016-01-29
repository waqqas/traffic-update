<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class GooglemapsController extends Controller{

    public $enableCsrfValidation = false;

    public function actionGeocode(){
        $name = Yii::$app->request->get('name', '');

        $response = Yii::$app->googleMaps->geocode($name);

        print "<pre>";
        print_r($response);
        print "</pre>";
    }

    public function actionRoute(){
        $from = Yii::$app->request->get('from', '');
        $to = Yii::$app->request->get('to', '');

        $response = Yii::$app->googleMaps->route($from, $to);
        print "<pre>";
        print_r($response);
        print "</pre>";

    }
}