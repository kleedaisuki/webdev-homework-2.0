<?php
/**
 * 用途：W07 赛程赛果页面的 HTTP 回归检查（需先 php yii serve 并导入样本）。
 * 作者：孙锘梵（2410868，成员3）
 *
 * 用法：php tests/w07-matches.php [http://127.0.0.1:8080]
 * 退出码：0 全部通过，1 有失败项。
 */

$base = $argv[1] ?? 'http://127.0.0.1:8080';
$base = rtrim($base, '/');

$failures = 0;
$checks = 0;

/**
 * 发起一次 GET，返回 [状态码, 响应体]；连接失败时状态码为 -1。
 */
function httpGet(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$status, (string) $body];
}

/**
 * 断言状态码等于期望值。
 */
function expectStatus(string $label, string $url, int $expected): string
{
    global $failures, $checks;
    $checks++;
    [$status, $body] = httpGet($url);
    if ($status === $expected) {
        echo "PASS  {$label}  ({$status})\n";
        return $body;
    }
    $failures++;
    echo "FAIL  {$label}  期望 {$expected}，实际 {$status}\n";

    return $body;
}

/**
 * 断言响应体包含（或不包含）某段文本。
 */
function expectContains(string $label, string $body, string $needle, bool $shouldContain = true): void
{
    global $failures, $checks;
    $checks++;
    $found = str_contains($body, $needle);
    if ($found === $shouldContain) {
        echo 'PASS  ' . $label . "\n";
        return;
    }
    $failures++;
    echo 'FAIL  ' . $label . '（' . ($shouldContain ? '缺少' : '不该出现') . '「' . $needle . '」）' . "\n";
}

/** 统计某段文本出现次数 */
function countOf(string $body, string $needle): int
{
    return substr_count($body, $needle);
}

echo "目标站点：{$base}\n\n";

// 1. 列表页
$list = expectStatus('列表页 200', "{$base}/world-cup/matches", 200);
expectContains('列表页含官方样本队名 Mexico', $list, 'Mexico');
if (countOf($list, '官方来源') !== 4) {
    $failures++;
    echo 'FAIL  列表页应有 4 个「官方来源」徽标，实际 ' . countOf($list, '官方来源') . "\n";
} else {
    echo "PASS  列表页恰好 4 个「官方来源」徽标\n";
}
if (countOf($list, 'wc-card-main') !== 4) {
    $failures++;
    echo 'FAIL  列表页应只有 4 张卡片，实际 ' . countOf($list, 'wc-card-main') . "\n";
} else {
    echo "PASS  列表页恰好 4 张卡片\n";
}
expectContains('列表页不泄漏草稿 demo-classroom', $list, '演示甲队', false);

// 2. 筛选
$finished = expectStatus('筛选 finished 200', "{$base}/world-cup/matches?status=finished", 200);
expectContains('finished 页不含「未开赛」比分', $finished, 'is-scheduled', false);

$scheduled = expectStatus('筛选 scheduled 200', "{$base}/world-cup/matches?status=scheduled", 200);
expectContains('scheduled 页显示空态', $scheduled, '暂无符合条件的比赛');
expectContains('scheduled 页不出现官方样本', $scheduled, 'Mexico', false);

expectStatus('非法筛选 400', "{$base}/world-cup/matches?status=abc", 400);

// 3. 详情
$detail = expectStatus('详情 200', "{$base}/world-cup/matches/1", 200);
expectContains('详情显示 2 : 0', $detail, '2 : 0');
expectContains('详情显示北京时间换算结果', $detail, '2026-06-12 03:00');
expectContains('详情标注 UTC+08:00', $detail, 'UTC+08:00');
expectContains('详情给出来源链接', $detail, 'FIFA 官方来源');

// 4. 不存在与非法 ID
expectStatus('不存在的比赛 404', "{$base}/world-cup/matches/9999", 404);
expectStatus('非数字 ID 404', "{$base}/world-cup/matches/abc", 404);

// 5. 草稿不可见（从本机配置读 DSN）
$dbLocal = dirname(__DIR__) . '/config/db.local.php';
if (is_file($dbLocal)) {
    $db = require $dbLocal;
    try {
        $pdo = new PDO($db['dsn'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SELECT id FROM fixture WHERE sample_key = 'demo-classroom' LIMIT 1");
        $demoId = $stmt->fetchColumn();
        if ($demoId === false) {
            $failures++;
            echo "FAIL  数据库里找不到 demo-classroom（先执行 php yii seed/fixtures）\n";
        } else {
            expectStatus('草稿详情 404', "{$base}/world-cup/matches/{$demoId}", 404);
        }
    } catch (Throwable $e) {
        $failures++;
        echo 'FAIL  无法连接数据库核对草稿：' . $e->getMessage() . "\n";
    }
} else {
    echo "SKIP  没有 config/db.local.php，跳过草稿不可见检查\n";
}

echo "\n共 {$checks} 项检查，失败 {$failures} 项。\n";
exit($failures === 0 ? 0 : 1);
