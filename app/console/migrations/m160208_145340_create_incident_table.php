<?php

use yii\db\Schema;
use yii\db\Migration;

class m160208_145340_create_incident_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%incident}}', [
            'id' => $this->primaryKey(),
            'lat' => $this->float(6)->notNull(),
            'lng' => $this->float(6)->notNull(),
            'location' => $this->string(200),
            'type' => $this->integer(),
            'description' => $this->string(500),
            'eventCode' => $this->integer(),
            'startTime' => $this->integer(),
            'endTime' => $this->integer(),
            'delayFromTypical' => $this->integer(),
            'delayFromFreeFlow' => $this->integer(),
            'enabled' => $this->boolean(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropTable('{{%incident}}');
    }
}
