<?php

namespace danvick\jumbefupi\migrations;

use yii\db\Connection;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%sms_message}}`.
 */
class m231115_142138_add_scheduled_at_col_sms_message_table extends Migration
{
    /**
     * @return array|string|Connection
     */
    /*public function getDb()
    {
        return Yii::$app->jumbefupi->db;
    }*/

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%sms_message}}', 'scheduled_at', $this->dateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return false;
    }
}
