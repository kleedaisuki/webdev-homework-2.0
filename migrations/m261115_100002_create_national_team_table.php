<?php
/**
 * 用途：创建国家队表 national_team（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\db\Migration;

/**
 * 参赛国家队：三位大写代码唯一，演示队使用保留的本地代码 DMA/DMB。
 */
class m261115_100002_create_national_team_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%national_team}}', [
            'id' => $this->primaryKey()->unsigned(),
            'code' => $this->char(3)->notNull()->comment('三位大写代码，正式样本沿用来源代码'),
            'name' => $this->string(100)->notNull()->comment('国家队展示名称'),
            'created_at' => $this->dateTime()->notNull()->comment('UTC'),
            'updated_at' => $this->dateTime()->notNull()->comment('UTC'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_national_team_code', '{{%national_team}}', 'code', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%national_team}}');
    }
}
