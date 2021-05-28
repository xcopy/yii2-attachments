<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%attachment_type}}`.
 */
class m201015_103349_create_attachment_type_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%attachment_type}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'object_name' => $this->string(100)->notNull(),
            'file_extensions' => ' varchar[]',

            'active' => $this->boolean()->defaultValue(true)->notNull(),
            'created_by' => $this->integer()->notNull()->defaultValue(0),
            'created_time' => ' timestamp with time zone not null default now()',
            'modified_by' => $this->integer(),
            'modified_time' => ' timestamp with time zone'
        ]);

        // creates unique index for columns `name, object_name`
        $this->createIndex(
            'idx-unique-attachment_type-name_and_object_name',
            'attachment_type',
            'name, object_name',
            true
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): bool
    {
        $this->dropTable('{{%attachment_type}}');

        return true;
    }
}
