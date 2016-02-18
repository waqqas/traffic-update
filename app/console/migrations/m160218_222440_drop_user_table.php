<?php

use yii\db\Schema;
use yii\db\Migration;

class m160218_222440_drop_user_table extends Migration
{
    public function safeUp()
    {
        $this->dropTable('{{%user}}');
    }

    public function safeDown()
    {
        return false;
    }
}
