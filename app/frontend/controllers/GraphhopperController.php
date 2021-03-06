<?php

namespace frontend\controllers;

use common\components\GraphHopper;
use Yii;
use yii\web\Controller;

class GraphhopperController extends Controller{

    public $enableCsrfValidation = false;

    public function actionRoute(){
        $from = Yii::$app->request->get('from', '');
        $to = Yii::$app->request->get('to', '');

        return Yii::$app->graphHopper->route($from, $to);

    }

    public function actionGeocode(){
        $name = Yii::$app->request->get('name', '');
        $reverse = Yii::$app->request->get('reverse', false);

        $response = Yii::$app->graphHopper->geocode($name, $reverse);

        print GraphHopper::getLanLongFromGeocode($response);
    }

    public function actionRoute2(){
        $from = Yii::$app->request->get('from', '');
        $to = Yii::$app->request->get('to', '');

        $fromCoord = json_decode(Yii::$app->graphHopper->geocode($from));
        $toCoord = json_decode(Yii::$app->graphHopper->geocode($to));

        return Yii::$app->graphHopper->route(GraphHopper::getLanLongFromGeocode($fromCoord), GraphHopper::getLanLongFromGeocode($toCoord));

    }
}