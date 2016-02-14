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

        \Yii::$app->consoleRunner->run('translate/scan', true);
        \Yii::$app->consoleRunner->run('language/translate ur-PK', true);

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
