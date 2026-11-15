<?php
/**
 * 用途：创建比赛场馆表 venue（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\db\Migration;

/**
 * 比赛场馆：名称 + 城市唯一，timezone 保存真实 IANA 时区便于核对原始事实。
 */
class m261115_100003_create_venue_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%venue}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(150)->notNull()->comment('场馆名称'),
            'city' => $this->string(100)->notNull()->comment('所在城市'),
            'timezone' => $this->string(64)->notNull()->comment('IANA 时区名，如 America/Mexico_City'),
            'created_at' => $this->dateTime()->notNull()->comment('UTC'),
            'updated_at' => $this->dateTime()->notNull()->comment('UTC'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_venue_name_city', '{{%venue}}', ['name', 'city'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%venue}}');
    }
}
