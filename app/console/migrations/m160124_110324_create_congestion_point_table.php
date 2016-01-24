<?php

use yii\db\Schema;
use yii\db\Migration;

class m160124_110324_create_congestion_point_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%congestion_pt}}', [
            'id' => $this->primaryKey(),
            'lat' => $this->float(6)->notNull(),
            'long' => $this->float(6)->notNull(),
            'radius' => $this->float()->notNull(),
            'weight' => $this->float()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('default'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropTable('{{%congestion_pt}}');
    }
}
