<?php

use yii\db\Migration;

class m160302_220840_create_user_preference extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_preference}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(32)->notNull(),
            'encoding' => $this->string(16)->notNull()->defaultValue('none'),
            'value' => $this->text(),
        ]);

        $this->createIndex('name_index', '{{%user_preference}}', 'name');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_preference}}');
    }
}
