<?php
namespace frontend\controllers;

use common\components\sms\User;
use common\models\UserPreference;
use Cron\CronExpression;
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

    public function actionMo()
    {

        $mo = new Smsmo();

        /** @var \libphonenumber\PhoneNumber $phone */
        $phone = Yii::$app->phone->parse(Yii::$app->request->get('msisdn', ''), 'PK');

        if ($phone != null) {

            $mo->msisdn = Yii::$app->phone->format($phone, PhoneNumberFormat::E164);
            $mo->operator = Yii::$app->request->get('operator', '');
            $mo->text = Yii::$app->request->get('text', '');
            $mo->status = 'received';

            $mo->save();

            $command = \console\controllers\SmsController::getCommand('mo', $mo->msisdn, $mo->id);

            Yii::$app->consoleRunner->run($command);
        } else {
            throw new BadRequestHttpException();
        }

    }

    public function actionMt()
    {
        $text = Yii::$app->request->get('text', null);
        $to = Yii::$app->request->get('to', null);

        if (!is_null($to) && !is_null($text)) {
            $mt = new Smsmt();


            $mt->recipient = $to;
            $mt->status = "queued";
            $mt->text = $text;

            if ($mt->save()) {
                $command = \console\controllers\SmsController::getCommand('mt', $to, $mt->id);

                Yii::$app->consoleRunner->run($command);

            } else {
                throw new ServerErrorHttpException();
            }

        }
    }

    public function actionList()
    {

        $preferences = UserPreference::findAll([
            'name' => User::PREF_SCHEDULE_SMS,
        ]);

        /** @var \common\components\Schedule $schedule */
        $schedule = Yii::$app->schedule;

        print "<pre>";
        foreach ($preferences as $preference) {
            /** @var \omnilight\scheduling\Event $event */
            print "\nUser:" . $preference->getUser()->one()->username . "\n";

            foreach($preference->value as $event){
                $cron = CronExpression::factory($event->getExpression());
                print $cron->getNextRunDate()->format('Y-m-d H:i:s');
                print "\n";
            }
        }
        print "</pre>";

    }

}
