<?php

use yii\db\Schema;
use yii\db\Migration;

class m160124_065630_add_messageid_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%smsmt}}', 'message_id', $this->string(40));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%smsmt}}', 'message_id');
    }
}
