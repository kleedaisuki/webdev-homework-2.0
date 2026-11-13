<?php
/**
 * 用途：首次安装时生成本机配置，重复执行保留已有配置。
 * 作者：Clash727（W01）
 */

$configPath = __DIR__ . '/config';

if (!is_file($configPath . '/db.local.php')) {
    copy($configPath . '/db.local.example.php', $configPath . '/db.local.php');
    echo "已创建 config/db.local.php，请填写本机数据库连接。\n";
}

if (!is_file($configPath . '/web.local.php')) {
    $config = ['cookieValidationKey' => bin2hex(random_bytes(32))];
    $content = "<?php\n/** 用途：本机 Cookie 密钥。作者：Clash727（W01 初始化器） */\n\nreturn "
        . var_export($config, true) . ";\n";
    file_put_contents($configPath . '/web.local.php', $content);
    echo "已生成 config/web.local.php，密钥仅保留在本机。\n";
}

echo "配置初始化完成。下一步：填写数据库配置，运行 php yii environment/check。\n";
