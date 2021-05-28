<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%attachment}}`.
 */
class m201015_103350_create_attachment_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%attachment}}', [
            'id' => $this->primaryKey(),
            'type_id' => $this->integer(), // nullable
            'object_name' => $this->string(100)->notNull(),
            'object_id' => $this->integer()->notNull(),
            'path' => $this->string(200)->notNull(),
            'name' => $this->string(200)->notNull(),
            'original_name' => $this->string(200)->notNull(),
            'document_type' => $this->string(200)->notNull(),
            'mime_type' => $this->string(200)->notNull(),
            'file_size' => $this->integer()->notNull(),
            'json_data' => ' jsonb',

            'active' => $this->boolean()->defaultValue(true)->notNull(),
            'created_by' => $this->integer()->notNull()->defaultValue(0),
            'created_time' => ' timestamp with time zone not null default now()',
            'modified_by' => $this->integer(),
            'modified_time' => ' timestamp with time zone'
        ]);

        // creates index for column `type_id`
        $this->createIndex(
            'idx-attachment-type_id',
            'attachment',
            'type_id'
        );

        // add foreign key for table `attachment_type`
        $this->addForeignKey(
            'fk-attachment-type_id',
            'attachment',
            'type_id',
            'attachment_type',
            'id'
        );

        // creates index for column `active`
        $this->createIndex(
            'idx-attachment-active',
            'attachment',
            'active'
        );

        // creates index for column `object_id` and `object_name`
        $this->createIndex(
            'idx-attachment-object_id_and_object_name',
            'attachment',
            'object_id, object_name'
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): bool
    {
        $this->dropTable('{{%attachment}}');

        return true;
    }
}
