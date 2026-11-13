<?php
/**
 * 用途：Web 入口配置，启用严格的 pretty URL 与 CSRF。
 * 作者：Clash727（W01）
 */

$local = require __DIR__ . '/web.local.php';

return yii\helpers\ArrayHelper::merge(require __DIR__ . '/common.php', [
    'id' => 'world-cup-web',
    'name' => '世界杯专题站',
    'defaultRoute' => 'site/index',
    'components' => [
        'request' => [
            'cookieValidationKey' => $local['cookieValidationKey'],
            'enableCsrfValidation' => true,
        ],
        // W05 实现 Account 后启用共享会话认证；W01 页面不访问 user 组件。
        'user' => [
            'identityClass' => app\models\Account::class,
            'loginUrl' => ['auth/login'],
            'enableAutoLogin' => false,
        ],
        'errorHandler' => ['errorAction' => 'site/error'],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => require __DIR__ . '/routes.php',
        ],
    ],
]);
