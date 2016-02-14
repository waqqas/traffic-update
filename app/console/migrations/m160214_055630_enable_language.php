<?php

use yii\db\Migration;

class m160214_055630_enable_language extends Migration
{
    public function safeUp()
    {
        $this->update('{{%language}}',
            [
                'status' => 1
            ],
            [
                'language_id' => 'ur-PK'
            ]
        );

        print "Running translation scan" . PHP_EOL;
        \Yii::$app->consoleRunner->run('translate/scan', true);

    }

    public function safeDown()
    {
        $this->update('{{%language}}',
            [
                'status' => 0
            ],
            [
                'language_id' => 'ur-PK'
            ]
        );
    }
}
