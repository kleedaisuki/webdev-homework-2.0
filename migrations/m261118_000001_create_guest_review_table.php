<?php

use yii\db\Migration;

class m261118_000001_create_guest_review_table extends Migration
{
    public function safeUp()
    {
        $table = '{{%guest_review}}';
        $this->createTable($table, [
            'id' => $this->primaryKey()->unsigned(),
            'message_id' => $this->integer()->unsigned()->notNull(),
            'reviewer_id' => $this->integer()->unsigned()->notNull(),
            'decision' => $this->string(16)->notNull(),
            'note' => $this->string(500),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_guest_review_message_time', $table, ['message_id', 'created_at', 'id']);
        $this->createIndex('idx_guest_review_reviewer', $table, 'reviewer_id');
        $this->addForeignKey('fk_guest_review_message', $table, 'message_id',
            '{{%guest_message}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('fk_guest_review_reviewer', $table, 'reviewer_id',
            '{{%account}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->execute("ALTER TABLE $table ADD CONSTRAINT chk_guest_review_decision "
            . "CHECK (decision IN ('approved', 'rejected'))");
    }

    public function safeDown()
    {
        $this->dropTable('{{%guest_review}}');
    }
}
