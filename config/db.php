<?php
/**
 * 用途：共享 MySQL 连接配置，统一字符集与 UTC 会话时区。
 * 作者：Clash727（W01）
 */

return array_merge([
    'class' => yii\db\Connection::class,
    'charset' => 'utf8mb4',
    'tablePrefix' => '',
    'attributes' => [PDO::ATTR_EMULATE_PREPARES => false],
    'on afterOpen' => static function ($event) {
        $event->sender->createCommand("SET time_zone = '+00:00'")->execute();
    },
], require __DIR__ . '/db.local.php');
