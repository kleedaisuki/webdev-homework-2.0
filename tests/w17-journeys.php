<?php
/**
 * 用途：W17 端到端双用户旅程与前后台导航可达性回归脚本；所有跳转均从页面真实链接提取，结束清理临时账号。
 * 作者：Clash727（W17）
 * 用法：php tests/w17-journeys.php [http://127.0.0.1:8080]
 */

use app\models\Account;
use app\models\Fixture;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
if (!in_array(parse_url($baseUrl, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('仅允许连接本机验收服务。');
}

final class JourneyClient
{
    /** @var array<string, string> */
    private array $cookies = [];

    public function __construct(private string $baseUrl)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status: int, location: string, contentType: string, disposition: string, body: string}
     */
    public function request(string $path, string $method = 'GET', array $data = []): array
    {
        $headers = ['Accept: text/html,application/pdf,*/*', 'Cookie: ' . implode('; ', $this->cookies)];
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $method === 'POST' ? http_build_query($data) : '',
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 15,
        ]]);
        $body = file_get_contents($this->baseUrl . $path, false, $context);
        if ($body === false || !isset($http_response_header[0])) {
            throw new RuntimeException("HTTP 请求失败：{$method} {$path}");
        }

        $location = '';
        $contentType = '';
        $disposition = '';
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Set-Cookie: ') === 0) {
                $cookie = explode(';', substr($header, 12), 2)[0];
                $this->cookies[explode('=', $cookie, 2)[0]] = $cookie;
            } elseif (stripos($header, 'Location: ') === 0) {
                $location = trim(substr($header, 10));
            } elseif (stripos($header, 'Content-Type: ') === 0) {
                $contentType = trim(substr($header, 14));
            } elseif (stripos($header, 'Content-Disposition: ') === 0) {
                $disposition = trim(substr($header, 21));
            }
        }

        return [
            'status' => (int) substr($http_response_header[0], 9, 3),
            'location' => $location,
            'contentType' => $contentType,
            'disposition' => $disposition,
            'body' => $body,
        ];
    }
}

$checks = 0;

function verify(bool $passed, string $label): void
{
    global $checks;
    if (!$passed) {
        throw new RuntimeException('FAIL ' . $label);
    }
    $checks++;
    echo 'PASS ' . $label . PHP_EOL;
}

function parseDoc(string $html): DOMXPath
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    return new DOMXPath($dom);
}

/**
 * 提取页面中所有 <a> 的 href 列表（保持站内路径形式）。
 *
 * @return list<string>
 */
function linksOf(DOMXPath $xpath): array
{
    $hrefs = [];
    foreach ($xpath->query('//a[@href]') as $node) {
        $href = trim((string) $node->getAttribute('href'));
        if ($href !== '') {
            $hrefs[] = $href;
        }
    }
    return $hrefs;
}

function findHref(DOMXPath $xpath, string $pattern, string $label): string
{
    foreach (linksOf($xpath) as $href) {
        if (preg_match($pattern, $href) === 1) {
            verify(true, $label . ' (' . $href . ')');
            return $href;
        }
    }
    verify(false, $label . '（页面未找到匹配链接）');
    return '';
}

function assertHtmlPage(array $res, string $path, bool $requireH1 = true): DOMXPath
{
    verify($res['status'] === 200, "{$path} 返回 200");
    $xpath = parseDoc($res['body']);
    $titles = $xpath->query('//title');
    verify($titles->length === 1 && trim($titles[0]->textContent) !== '', "{$path} 具有非空页面标题");
    if ($requireH1) {
        verify($xpath->query('//h1')->length === 1, "{$path} 有唯一 h1 主标题");
    }
    foreach ($xpath->query('//link[@rel="stylesheet"] | //script[@src] | //img[@src] | //iframe') as $asset) {
        $url = (string) ($asset->getAttribute('href') ?: $asset->getAttribute('src'));
        verify(!preg_match('~^(https?:)?//~i', $url), "{$path} 不依赖外部静态资源");
    }
    return $xpath;
}

function csrfToken(string $html): string
{
    if (!preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $match)) {
        throw new RuntimeException('页面缺少 CSRF token。');
    }
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}

$visitor = new JourneyClient($baseUrl);
$admin = new JourneyClient($baseUrl);

$password = Yii::$app->security->generateRandomString(32);
$account = new Account([
    'username' => 'w17_check_' . bin2hex(random_bytes(5)),
    'display_name' => 'W17 走查管理员',
    'auth_key' => Yii::$app->security->generateRandomString(32),
]);
$account->setPassword($password);
verify($account->save(), '创建临时管理员账号');

try {
    // 1. 访客旅程：从首页出发，不手输 URL 完成比赛 -> 留言 -> 团队 -> 成员 -> 下载 -> 回首页
    $home = assertHtmlPage($visitor->request('/'), '/');
    $matchesHref = findHref($home, '#^/world-cup(?:/matches)?$#', '首页链接到赛程赛果列表');
    $homeDetailHref = findHref($home, '#^/world-cup/matches/\d+$#', '首页精选赛果卡片链接到比赛详情');
    $teamHref = findHref($home, '#^/team$#', '首页链接到我们的团队主页');
    $fansHref = findHref($home, '#^/world-cup/fans$#', '首页链接到球迷讨论');
    $downloadsHref = findHref($home, '#^/downloads$#', '首页顶栏链接到作业下载');
    $loginHref = findHref($home, '#^/admin/login$#', '首页顶栏链接到管理员登录');

    $matchesPage = assertHtmlPage($visitor->request($matchesHref), $matchesHref);
    assertHtmlPage($visitor->request('/world-cup/matches'), '/world-cup/matches');
    $finishedHref = findHref($matchesPage, '#^/world-cup(?:/matches)?\?status=finished$#', '赛程页含已完赛筛选');
    $groupsHref = findHref($matchesPage, '#^/world-cup/groups$#', '赛程页含小组对阵视图');
    $bracketHref = findHref($matchesPage, '#^/world-cup/bracket$#', '赛程页含淘汰赛视图');
    $listDetailHref = findHref($matchesPage, '#^/world-cup/matches/\d+$#', '赛程页含比赛详情链接');
    findHref($matchesPage, '#^/world-cup/fans$#', '赛程页专题导航含球迷讨论');

    assertHtmlPage($visitor->request($finishedHref), $finishedHref);
    assertHtmlPage($visitor->request($groupsHref), $groupsHref);
    assertHtmlPage($visitor->request($bracketHref), $bracketHref);

    $detailPage = assertHtmlPage($visitor->request($homeDetailHref), $homeDetailHref, false);
    findHref($detailPage, '#^/world-cup(?:/matches)?$#', '比赛详情页可返回赛程赛果列表');
    if ($listDetailHref !== $homeDetailHref) {
        assertHtmlPage($visitor->request($listDetailHref), $listDetailHref, false);
    }

    $fansPage = assertHtmlPage($visitor->request($fansHref), $fansHref);
    $guestbookHref = findHref($fansPage, '#^/guestbook$#', '球迷讨论页链接到写留言页');
    $guestbookPage = assertHtmlPage($visitor->request($guestbookHref), $guestbookHref);
    findHref($guestbookPage, '#^/$#', '留言页可返回首页');

    $teamPage = assertHtmlPage($visitor->request($teamHref), $teamHref);
    verify(!str_contains($visitor->request($teamHref)['body'], '参赛球队'), '团队页不把课设团队称为参赛球队');
    findHref($teamPage, '#^/$#', '团队页可返回世界杯首页');
    $teamDownloadsHref = findHref($teamPage, '#^/downloads$#', '团队页链接到作业下载');
    $memberHref = findHref($teamPage, '#^/team/members/\d+$#', '团队页链接到课设成员详情');

    $memberPage = assertHtmlPage($visitor->request($memberHref), $memberHref);
    findHref($memberPage, '#^/team$#', '成员详情页可返回我们的团队');

    $downloadsPage = assertHtmlPage($visitor->request($teamDownloadsHref), $downloadsHref);
    $pdfHref = findHref($downloadsPage, '#^/downloads/team/[^/]+\.pdf$#i', '下载页列出真实团队需求文档 PDF');
    $pdfRes = $visitor->request($pdfHref);
    verify($pdfRes['status'] === 200, '点击团队 PDF 下载返回 200');
    verify(str_starts_with($pdfRes['body'], '%PDF-'), '下载内容为有效 PDF 文件');
    verify(str_contains(strtolower($pdfRes['disposition']), 'attachment'), '响应头以附件方式下载');

    // 2. 管理员旅程：登录 -> 后台工作台 -> 赛事/团队/审核及全部返回与公开核对路径 -> 退出登录
    $loginRes = $admin->request($loginHref);
    assertHtmlPage($loginRes, $loginHref);
    $postLogin = $admin->request($loginHref, 'POST', [
        '_csrf' => csrfToken($loginRes['body']),
        'LoginForm' => ['username' => $account->username, 'password' => $password],
    ]);
    verify($postLogin['status'] === 303 && str_ends_with($postLogin['location'], '/admin'), '管理员登录跳转到管理工作台');

    $adminRes = $admin->request('/admin');
    $adminHome = assertHtmlPage($adminRes, '/admin');
    $adminMatchesHref = findHref($adminHome, '#^/admin/matches$#', '工作台链接到赛事管理');
    $adminTeamHref = findHref($adminHome, '#^/admin/team$#', '工作台链接到团队资料管理');
    $adminMessagesHref = findHref($adminHome, '#^/admin/messages$#', '工作台链接到留言审核');
    findHref($adminHome, '#^/$#', '工作台含世界杯首页核对入口');
    findHref($adminHome, '#^/world-cup(?:/matches)?$#', '工作台含公开赛程核对入口');
    findHref($adminHome, '#^/team$#', '工作台含公开团队页核对入口');
    findHref($adminHome, '#^/guestbook$#', '工作台含公开留言核对入口');
    findHref($adminHome, '#^/downloads$#', '工作台含作业下载核对入口');

    $adminMatches = assertHtmlPage($admin->request($adminMatchesHref), $adminMatchesHref);
    findHref($adminMatches, '#^/admin$#', '赛事管理页含返回管理工作台链接');
    findHref($adminMatches, '#^/world-cup(?:/matches)?$#', '赛事管理页含查看公开赛程链接');
    $createFixtureHref = findHref($adminMatches, '#^/admin/matches/create$#', '赛事管理页含新建演示比赛入口');
    $createFixture = assertHtmlPage($admin->request($createFixtureHref), $createFixtureHref);
    findHref($createFixture, '#^/admin$#', '新建演示比赛页含返回工作台链接');
    findHref($createFixture, '#^/admin/matches$#', '新建演示比赛页含返回赛事列表链接');

    $demoFixture = Fixture::find()->where(['is_demo' => 1])->orderBy(['id' => SORT_ASC])->one();
    if ($demoFixture !== null) {
        $editFixtureHref = '/admin/matches/' . $demoFixture->id . '/edit';
        $editFixtureRes = $admin->request($editFixtureHref);
        $editFixture = assertHtmlPage($editFixtureRes, $editFixtureHref);
        findHref($editFixture, '#^/admin$#', '编辑演示比赛页含返回工作台链接');
        findHref($editFixture, '#^/admin/matches$#', '编辑演示比赛页含返回赛事列表链接');
        $hasPublicDetail = str_contains($editFixtureRes['body'], '/world-cup/matches/' . $demoFixture->id);
        if ($demoFixture->publication_status === Fixture::PUBLICATION_DRAFT) {
            verify(!$hasPublicDetail, '草稿编辑页不输出公开详情链接（避免导向 404）');
        } else {
            verify($hasPublicDetail, '已发布演示编辑页提供公开详情核对链接');
        }
    }

    $adminTeam = assertHtmlPage($admin->request($adminTeamHref), $adminTeamHref);
    findHref($adminTeam, '#^/admin$#', '团队资料管理页含返回管理工作台链接');
    findHref($adminTeam, '#^/team$#', '团队资料管理页含查看公开团队页链接');
    $editMemberHref = findHref($adminTeam, '#^/admin/members/\d+/edit$#', '团队资料管理页含成员编辑入口');
    $editMember = assertHtmlPage($admin->request($editMemberHref), $editMemberHref);
    findHref($editMember, '#^/admin$#', '编辑成员资料页含返回管理工作台链接');
    findHref($editMember, '#^/admin/team$#', '编辑成员资料页含返回团队资料管理链接');
    findHref($editMember, '#^/team/members/\d+$#', '编辑成员资料页含查看公开成员页链接');

    $adminMessages = assertHtmlPage($admin->request($adminMessagesHref), $adminMessagesHref);
    findHref($adminMessages, '#^/admin$#', '留言审核页含返回管理工作台链接');
    findHref($adminMessages, '#^/guestbook$#', '留言审核页含查看公开留言链接');
    findHref($adminMessages, '#^/world-cup/fans$#', '留言审核页含查看球迷讨论链接');
    $approvedTab = findHref($adminMessages, '#^/admin/messages\?status=approved$#', '留言审核页含已通过标签');
    $rejectedTab = findHref($adminMessages, '#^/admin/messages\?status=rejected$#', '留言审核页含已拒绝标签');
    assertHtmlPage($admin->request($approvedTab), $approvedTab);
    assertHtmlPage($admin->request($rejectedTab), $rejectedTab);

    $logoutRes = $admin->request('/admin/logout', 'POST', ['_csrf' => csrfToken($adminRes['body'])]);
    verify($logoutRes['status'] === 303 && str_ends_with($logoutRes['location'], '/admin/login'), '管理员退出登录返回登录页');
    foreach (['/admin', '/admin/matches', '/admin/team', '/admin/messages'] as $protected) {
        $denied = $admin->request($protected);
        verify($denied['status'] === 302 && str_ends_with($denied['location'], '/admin/login'), "退出后访问 {$protected} 跳转登录");
    }

    echo "{$checks} 项 W17 旅程检查全部通过。\n";
} finally {
    $account->delete();
}
