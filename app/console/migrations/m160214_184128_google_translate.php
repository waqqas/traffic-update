<?php

use yii\db\Schema;
use yii\db\Migration;

class m160214_184128_google_translate extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        \Yii::$app->consoleRunner->run('language/translate ur-PK', true);
    }

    public function safeDown()
    {
        $this->delete('{{%language_translate}}', [
            'language' => 'ur-PK'
        ]);

    }
}
