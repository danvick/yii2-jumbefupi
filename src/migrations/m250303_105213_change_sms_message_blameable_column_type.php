<?php

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
        $this->alterTable('{{%sms_message}}', [
            'created_by' => $this->string(20),
            'updated_by' => $this->string(20),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterTable('{{%sms_message}}', [
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);
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
