<?php
namespace frontend\controllers;

use common\models\Smsmo;
use Yii;
use yii\web\Controller;

class SmsController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionMo(){

        $mo = new Smsmo();

        $mo->msisdn = Yii::$app->request->get('msisdn', '');
        $mo->operator = Yii::$app->request->get('operator', '');
        $mo->text = Yii::$app->request->get('text', '');
        $mo->status = 'received';

        $mo->save();
    }

    public function actionMt(){
        $text = Yii::$app->request->get('text', null);
        $to = Yii::$app->request->get('to', null);

        if( !is_null($to) && !is_null($text)){
            Yii::$app->sms->send($to, $text);
        }
    }


}
