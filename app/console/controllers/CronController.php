<?php

namespace console\controllers;


use common\models\Cron;
use pheme\settings\models\Setting;
use Yii;
use yii\console\Controller;
use Cron\CronExpression;

class CronController extends Controller{

    public function actionRun(){

        $settings = Setting::findAll([
            'key' => 'events',
            'active' => 1
        ]);

        foreach($settings as $setting){

            $events = unserialize(base64_decode($setting->value));

            /** @var \common\components\Schedule $schedule */
            $schedule = Yii::$app->schedule;

            $schedule->setEvents($events);

            $dueEvents = $schedule->dueEvents(Yii::$app);

//            Yii::info( print_r($dueEvents, true));

            foreach( $dueEvents as $event){
                $event->run(Yii::$app);
            }
        }
    }

}