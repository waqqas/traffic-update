<?php

use yii\db\Migration;

class m160229_175137_create_session_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%session}}', [
            'id' => $this->primaryKey(),
            'phone_num' => $this->string(32)->notNull(),
            'data' => $this->string(16364)->notNull(),

            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('phone_num_index', '{{%session}}', 'phone_num');
    }

    public function safeDown()
    {
        $this->dropTable('{{%session}}');
    }
}
