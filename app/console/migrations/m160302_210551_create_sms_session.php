<?php

use yii\db\Migration;

class m160302_210551_create_sms_session extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%smssession}}', [
            'id' => $this->string(40)->notNull(),
            'expire' => $this->integer(),
            'data' => $this->text(),
        ]);

        $this->addPrimaryKey('PK', '{{%smssession}}', 'id');
        $this->createIndex('expire_index','{{%smssession}}', 'expire');
    }

    public function safeDown()
    {
        $this->dropTable('{{%smssession}}');
    }
}
