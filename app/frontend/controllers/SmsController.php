<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\web\ServerErrorHttpException;


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

        Yii::$app->consoleRunner->run('sms/mo ' . $mo->id );

    }

    public function actionMt(){
        $text = Yii::$app->request->get('text', null);
        $to = Yii::$app->request->get('to', null);

        if( !is_null($to) && !is_null($text)){
            $mt = new Smsmt();


            $mt->recipient = $to;
            $mt->status = "queued";
            $mt->text = $text;

            if($mt->save() ){
                Yii::$app->consoleRunner->run('sms/mt '. $mt->id);
            }
            else{
                throw new ServerErrorHttpException();
            }

        }
    }


}
