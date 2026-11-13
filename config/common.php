<?php
/**
 * 用途：Web 与命令行共用应用基线。
 * 作者：Clash727（W01）
 */

return [
    'basePath' => dirname(__DIR__),
    'language' => 'zh-CN',
    'sourceLanguage' => 'en-US',
    'timeZone' => 'UTC',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => ['class' => yii\caching\FileCache::class],
        'db' => require __DIR__ . '/db.php',
        'formatter' => [
            'defaultTimeZone' => 'UTC',
            'timeZone' => 'UTC',
        ],
        'log' => [
            'targets' => [[
                'class' => yii\log\FileTarget::class,
                'levels' => ['error', 'warning'],
                'logVars' => [],
            ]],
        ],
    ],
];
