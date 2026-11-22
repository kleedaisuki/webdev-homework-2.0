<?php

/**
 * 用途：W21 演示脚本的回归脚本，按八幕顺序用真实表单走一遍演示流程，
 *       断言每一步在页面上应该看到的结果，并在开始与结束时重置演示数据。
 * 用法：php tests/w21-demo.php [站点地址]
 * 说明：需要先启动站点（php yii serve 127.0.0.1 --port=8080）与 MySQL。
 *       只清理本脚本自己产生的演示数据，不会删除其它留言或赛程。
 * 作者：孙锘梵（2410868，成员3）
 */

use app\models\Fixture;
use app\models\ProjectTeam;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$cookies = [];
$failures = 0;

const DEMO_HOME = 39;    // ARG Argentina
const DEMO_AWAY = 33;    // BEL Belgium
const DEMO_VENUE = 12;   // Dallas Stadium
const DEMO_GROUP = 'F';
const DEMO_KICKOFF = '2026-06-11 20:00:00'; // 北京时间，落库为 12:00Z
const DEMO_MARK = '演示访客';

function check(bool $passed, string $description): void
{
    global $failures;
    if (!$passed) {
        $failures++;
    }
    echo ($passed ? 'PASS ' : 'FAIL ') . $description . PHP_EOL;
}

function request(string $path, string $method = 'GET', array $data = []): array
{
    global $baseUrl, $cookies;
    $headers = ['Accept: text/html'];
    if ($cookies) {
        $headers[] = 'Cookie: ' . implode('; ', $cookies);
    }
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $method === 'POST' ? http_build_query($data) : '',
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10,
    ]]);
    $body = (string) @file_get_contents($baseUrl . $path, false, $context);
    $status = 0;
    $location = '';
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
            $status = (int) $m[1];
        }
        if (stripos($header, 'Set-Cookie: ') === 0) {
            $cookie = explode(';', substr($header, 12), 2)[0];
            $cookies[explode('=', $cookie, 2)[0]] = $cookie;
        }
        if (stripos($header, 'Location: ') === 0) {
            $location = substr($header, 10);
        }
    }
    return ['status' => $status, 'body' => $body, 'location' => $location];
}

function pageText(string $html): string
{
    $stripped = preg_replace('~<script.*?</script>|<style.*?</style>~s', ' ', $html);
    return preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $stripped), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function csrfToken(string $html): string
{
    if (!preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) {
        throw new RuntimeException('页面中没有 csrf-token');
    }
    return $m[1];
}

function hasDetailLink(string $html, int $id): bool
{
    return str_contains($html, '/world-cup/matches/' . $id . '"');
}

function resetDemoData(bool $verbose = false): array
{
    $db = Yii::$app->db;
    $demoFixtures = (int) $db->createCommand(
        "delete from fixture where sample_key regexp '^demo-[0-9]{8}-'"
    )->execute();
    // guest_review 以 RESTRICT 引用 guest_message，必须先删审核记录再删留言
    $db->createCommand(
        "delete from guest_review where message_id in (select id from guest_message where nickname like :mark)",
        [':mark' => DEMO_MARK . '%']
    )->execute();
    $demoMessages = (int) $db->createCommand(
        'delete from guest_message where nickname like :mark',
        [':mark' => DEMO_MARK . '%']
    )->execute();
    $original = ProjectTeam::findOne(1);
    if ($original !== null && str_contains((string) $original->summary, '（演示改写 ')) {
        $original->summary = preg_replace('/（演示改写 [0-9a-f]{6}）$/u', '', (string) $original->summary);
        $original->save(false);
    }
    if ($verbose) {
        echo "重置：演示比赛 {$demoFixtures} 条、演示留言 {$demoMessages} 条已清理，团队简介已还原" . PHP_EOL;
    }
    return [$demoFixtures, $demoMessages];
}

$db = Yii::$app->db;
$originalSummary = (string) ProjectTeam::findOne(1)->summary;
resetDemoData();
$tag = substr(bin2hex(random_bytes(4)), 0, 6);
$newSummary = $originalSummary . '（演示改写 ' . $tag . '）';
$bodyApproved = '这是一条将在演示中通过的留言。编号 ' . $tag;
$bodyRejected = '这是一条将在演示中被拒绝的留言。编号 ' . $tag;
echo "本次运行标记 {$tag}" . PHP_EOL;

/* 第 1 幕：首页与官方赛果的来源 */
$home = request('/')['body'];
$homeText = pageText($home);
check(str_contains($homeText, '球场之上，热爱无界'), '首页显示主视觉文案');
check(str_contains($homeText, '北京时间'), '首页赛果标注北京时间');

$finished = request('/world-cup/matches?status=finished');
check($finished['status'] === 200 && str_contains(pageText($finished['body']), '已完赛'), '已完赛筛选页可访问');

$official = Fixture::findOne(['sample_key' => 'official-2026-001']);
$officialPage = request('/world-cup/matches/' . $official->id);
$officialText = pageText($officialPage['body']);
check($officialPage['status'] === 200, '官方场次详情页可访问');
check(str_contains($officialText, '2 : 0'), '官方场次详情显示 2 : 0');
check(str_contains($officialText, 'FIFA 官方来源'), '官方场次详情显示数据来源');
check(!str_contains($officialText, '演示数据'), '官方场次没有演示角标');

/* 登录后台 */
$loginPage = request('/admin/login');
$login = request('/admin/login', 'POST', [
    '_csrf' => csrfToken($loginPage['body']),
    'LoginForm[username]' => 'preview',
    'LoginForm[password]' => 'Preview2026!',
]);
check(in_array($login['status'], [302, 303], true), '管理员可以登录');

/* 第 2 幕：新建演示草稿 */
$createPage = request('/admin/matches/create');
$token = csrfToken($createPage['body']);
$created = request('/admin/matches/create', 'POST', [
    '_csrf' => $token,
    'FixtureForm[tournament_id]' => '1',
    'FixtureForm[home_team_id]' => (string) DEMO_HOME,
    'FixtureForm[away_team_id]' => (string) DEMO_AWAY,
    'FixtureForm[venue_id]' => (string) DEMO_VENUE,
    'FixtureForm[group_name]' => DEMO_GROUP,
    'FixtureForm[kickoff_at]' => DEMO_KICKOFF,
    'FixtureForm[match_status]' => 'scheduled',
    'FixtureForm[home_score]' => '',
    'FixtureForm[away_score]' => '',
]);
check(in_array($created['status'], [302, 303], true), '新建演示比赛后回到列表');
$demo = Fixture::find()->where(['like', 'sample_key', 'demo-2026'])->orderBy(['id' => SORT_DESC])->one();
check($demo !== null && $demo->is_demo === 1 && $demo->publication_status === 'draft', '新记录是演示草稿');
$front = request('/world-cup/matches');
check(!hasDetailLink($front['body'], $demo->id), '草稿不出现在前台赛程');
check(request('/world-cup/matches/' . $demo->id)['status'] === 404, '草稿详情页直接访问返回 404');

/* 第 3 幕：发布为未开赛 */
$published = request('/admin/matches/' . $demo->id . '/publish', 'POST', ['_csrf' => $token]);
check(in_array($published['status'], [302, 303], true), '发布动作被接受');
$demo->refresh();
check($demo->publication_status === 'published' && $demo->published_at !== null, '数据库记录已发布');
$scheduled = request('/world-cup/matches?status=scheduled');
check(hasDetailLink($scheduled['body'], $demo->id), '发布后出现在未开赛筛选');
$demoText = pageText(request('/world-cup/matches/' . $demo->id)['body']);
check(str_contains($demoText, '演示数据'), '演示场次详情带演示角标');
check(str_contains($demoText, '未开赛'), '演示场次显示未开赛');
check(!str_contains($demoText, '0 : 0'), '未开赛不显示 0 : 0');

/* 第 4 幕：录入 0:0 */
$editPage = request('/admin/matches/' . $demo->id . '/edit');
$updated = request('/admin/matches/' . $demo->id . '/edit', 'POST', [
    '_csrf' => csrfToken($editPage['body']),
    'FixtureForm[tournament_id]' => '1',
    'FixtureForm[home_team_id]' => (string) DEMO_HOME,
    'FixtureForm[away_team_id]' => (string) DEMO_AWAY,
    'FixtureForm[venue_id]' => (string) DEMO_VENUE,
    'FixtureForm[group_name]' => DEMO_GROUP,
    'FixtureForm[kickoff_at]' => DEMO_KICKOFF,
    'FixtureForm[match_status]' => 'finished',
    'FixtureForm[home_score]' => '0',
    'FixtureForm[away_score]' => '0',
]);
check(in_array($updated['status'], [302, 303], true), '录入比分后被接受');
$demo->refresh();
check($demo->match_status === 'finished' && (int) $demo->home_score === 0 && (int) $demo->away_score === 0, '数据库记录为 0:0 完赛');
$finishedPage = request('/world-cup/matches?status=finished');
check(hasDetailLink($finishedPage['body'], $demo->id), '录分后出现在已完赛筛选');
check(str_contains(pageText($finishedPage['body']), '0 : 0'), '已完赛列表显示 0 : 0');

/* 第 5 幕：修改团队简介 */
$teamPage = request('/team');
check(str_contains(pageText($teamPage['body']), $originalSummary), '团队页显示原简介');
$adminTeam = request('/admin/team');
$savedTeam = request('/admin/team', 'POST', [
    '_csrf' => csrfToken($adminTeam['body']),
    'ProjectTeam[name]' => (string) ProjectTeam::findOne(1)->name,
    'ProjectTeam[summary]' => $newSummary,
]);
check(in_array($savedTeam['status'], [302, 303], true), '团队资料保存被接受');
check(str_contains(pageText(request('/team')['body']), $tag), '前台团队页出现改后的简介');

/* 第 6 幕：留言一过一拒 */
$messageIds = [];
foreach (['演示访客甲' => $bodyApproved, '演示访客乙' => $bodyRejected] as $nickname => $body) {
    $guestbook = request('/guestbook');
    $posted = request('/guestbook', 'POST', [
        '_csrf' => csrfToken($guestbook['body']),
        'GuestMessage[nickname]' => $nickname,
        'GuestMessage[body]' => $body,
    ]);
    check(in_array($posted['status'], [302, 303], true), '留言「' . $nickname . '」提交后回到列表');
    $messageIds[$nickname] = (int) $db->createCommand('select max(id) from guest_message')->queryScalar();
}
check(!str_contains(pageText(request('/guestbook')['body']), $tag), '待审核留言不在公开列表');

$adminMessages = request('/admin/messages');
$reviewToken = csrfToken($adminMessages['body']);
$approved = request('/admin/messages/' . $messageIds['演示访客甲'] . '/review', 'POST', [
    '_csrf' => $reviewToken,
    'ReviewForm[decision]' => 'approved',
    'ReviewForm[note]' => '演示：内容合规，予以通过。',
]);
$rejected = request('/admin/messages/' . $messageIds['演示访客乙'] . '/review', 'POST', [
    '_csrf' => $reviewToken,
    'ReviewForm[decision]' => 'rejected',
    'ReviewForm[note]' => '演示：内容不合规，拒绝展示。',
]);
check(in_array($approved['status'], [302, 303], true), '通过留言被接受');
check(in_array($rejected['status'], [302, 303], true), '拒绝留言被接受');
$guestbookFinal = pageText(request('/guestbook')['body']);
check(str_contains($guestbookFinal, $bodyApproved), '通过的留言出现在公开列表');
check(!str_contains($guestbookFinal, $bodyRejected), '拒绝的留言不公开');

/* 第 7 幕：下载 */
$downloads = pageText(request('/downloads')['body']);
check(str_contains($downloads, '团队作业'), '下载页有团队作业分区');
check(str_contains($downloads, '.pdf'), '下载页列出 PDF 文件');

/* 第 8 幕：重置演示数据 */
resetDemoData(true);
check((int) $db->createCommand("select count(*) from fixture where sample_key regexp '^demo-[0-9]{8}-'")->queryScalar() === 0, '重置后没有演示比赛残留');
check((int) $db->createCommand('select count(*) from guest_message where nickname like :mark', [':mark' => DEMO_MARK . '%'])->queryScalar() === 0, '重置后没有演示留言残留');
check((string) ProjectTeam::findOne(1)->summary === $originalSummary, '重置后团队简介还原');

echo $failures === 0 ? '全部通过' . PHP_EOL : "失败 {$failures} 项" . PHP_EOL;
exit($failures === 0 ? 0 : 1);
