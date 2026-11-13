<?php
/**
 * 用途：Yii 命令行入口，供本地启动、环境检查和后续迁移使用。
 * 作者：Clash727（W01）
 */

return yii\helpers\ArrayHelper::merge(require __DIR__ . '/common.php', [
    'id' => 'world-cup-console',
    'controllerNamespace' => 'app\commands',
    'controllerMap' => [
        'serve' => [
            'class' => yii\console\controllers\ServeController::class,
            'docroot' => '@app/web',
            'port' => 8080,
        ],
    ],
]);
