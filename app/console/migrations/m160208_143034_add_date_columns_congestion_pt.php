<?php

use yii\db\Schema;
use yii\db\Migration;

class m160208_143034_add_date_columns_congestion_pt extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%congestion_pt}}', 'start_time', $this->integer());
        $this->addColumn('{{%congestion_pt}}', 'end_time', $this->integer());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%congestion_pt}}', 'start_time');
        $this->dropColumn('{{%congestion_pt}}', 'end_time');
    }
}
