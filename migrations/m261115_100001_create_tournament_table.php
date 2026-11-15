<?php
/**
 * 用途：创建世界杯届次表 tournament（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\db\Migration;

/**
 * 世界杯届次：一期只导入一届精选小组赛。
 */
class m261115_100001_create_tournament_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%tournament}}', [
            'id' => $this->primaryKey()->unsigned(),
            'edition' => $this->smallInteger()->unsigned()->notNull()->comment('届次年份，本期 2026'),
            'name' => $this->string(120)->notNull()->comment('届次展示名称'),
            'summary' => $this->text()->notNull()->comment('纯文本简介，0-5000 字符'),
            'created_at' => $this->dateTime()->notNull()->comment('UTC'),
            'updated_at' => $this->dateTime()->notNull()->comment('UTC'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_tournament_edition', '{{%tournament}}', 'edition', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%tournament}}');
    }
}
