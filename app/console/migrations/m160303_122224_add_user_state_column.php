<?php

use yii\db\Migration;

class m160303_122224_add_user_state_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'state', 'varchar(40) NOT NULL DEFAULT \'init\'');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'state');
    }
}
