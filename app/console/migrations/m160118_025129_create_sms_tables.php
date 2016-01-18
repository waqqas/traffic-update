<?php

use yii\db\Schema;
use yii\db\Migration;

class m160118_025129_create_sms_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%smsmo}}', [
            'id' => $this->primaryKey(),
            'msisdn' => $this->string(32)->notNull(),
            'operator' => $this->string(32)->notNull(),
            'text' => $this->text()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('default'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%smsmt}}', [
            'id' => $this->primaryKey(),
            'recipient' => $this->string(32)->notNull(),
            'text' => $this->text()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('default'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropTable('{{%smsmo}}');
        $this->dropTable('{{%smsmt}}');
    }
}
