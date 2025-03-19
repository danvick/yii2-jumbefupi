<?php

namespace danvick\jumbefupi\migrations;

use yii\db\Migration;

/**
 * Class m250303_105213_change_sms_message_blameable_column_type
 */
class m250303_105213_change_sms_message_blameable_column_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%sms_message}}', 'created_by', $this->string());
        $this->alterColumn('{{%sms_message}}', 'updated_by', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250303_105213_change_sms_message_blameable_column_type cannot be reverted.\n";
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250303_105213_change_sms_message_blameable_column_type cannot be reverted.\n";

        return false;
    }
    */
}

