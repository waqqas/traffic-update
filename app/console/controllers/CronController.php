<?php

namespace console\controllers;


use common\components\sms\User;
use common\models\Cron;
use common\models\UserPreference;
use Yii;
use yii\console\Controller;

class CronController extends Controller{

    public function actionRun(){

        $preferences = UserPreference::findAll([
            'name' => User::PREF_SCHEDULE_SMS,
        ]);

        $schedule = Yii::$app->schedule;

        foreach($preferences as $preference){

            $schedule->setEvents($preference->value);

            $dueEvents = $schedule->dueEvents(Yii::$app);

            foreach( $dueEvents as $event){
                $event->run(Yii::$app);
            }
        }
    }

}