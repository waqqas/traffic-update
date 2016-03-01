<?php
namespace frontend\controllers;

use libphonenumber\PhoneNumberFormat;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use common\models\Smsmo;
use common\models\Smsmt;
use yii\web\ServerErrorHttpException;


class SmsController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionMo(){

        $mo = new Smsmo();

        /** @var \libphonenumber\PhoneNumber $phone */
        $phone = Yii::$app->phone->parse(Yii::$app->request->get('msisdn', ''), 'PK');

        if($phone != null) {

            $mo->msisdn = Yii::$app->phone->format($phone, PhoneNumberFormat::E164);
            $mo->operator = Yii::$app->request->get('operator', '');
            $mo->text = Yii::$app->request->get('text', '');
            $mo->status = 'received';

            $mo->save();

            $command = \console\controllers\SmsController::getCommand('mo', $mo->msisdn, $mo->id);

            Yii::$app->consoleRunner->run($command);
        }
        else{
            throw new BadRequestHttpException();
        }

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
                $command = \console\controllers\SmsController::getCommand('mt', $to, $mt->id);

                Yii::$app->consoleRunner->run($command);

            }
            else{
                throw new ServerErrorHttpException();
            }

        }
    }


}
