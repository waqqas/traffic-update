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

}
