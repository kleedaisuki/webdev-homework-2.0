<?php

/**
 * 用途：W11 演示赛程发布流程的回归脚本，覆盖草稿→发布→录比分的完整闭环，
 *       以及正式样本只读、非法比分、主客相同等拒绝路径。
 * 用法：php tests/w11-fixtures.php [站点地址]
 * 作者：孙锘梵（2410868，成员3）
 */

use app\models\Account;
use app\models\Fixture;
use app\models\NationalTeam;
use app\models\Tournament;
use app\models\Venue;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$cookies = [];

function check(bool $passed, string $description): void
{
    if (!$passed) {
        throw new RuntimeException($description);
    }
    echo 'PASS ' . $description . PHP_EOL;
}

function request(string $path, string $method = 'GET', array $data = []): array
{
    global $baseUrl, $cookies;
    $headers = ['Accept: text/html', 'Cookie: ' . implode('; ', $cookies)];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $method === 'POST' ? http_build_query($data) : '',
        'ignore_errors' => true,
        'follow_location' => 0,
    ]]);
    $body = file_get_contents($baseUrl . $path, false, $context);
    $location = '';
    foreach ($http_response_header as $header) {
        if (stripos($header, 'Set-Cookie: ') === 0) {
            $cookie = explode(';', substr($header, 12), 2)[0];
            $cookies[explode('=', $cookie, 2)[0]] = $cookie;
        }
        if (stripos($header, 'Location: ') === 0) {
            $location = substr($header, 10);
        }
    }
    return [
        'status' => (int) substr($http_response_header[0], 9, 3),
        'body' => $body,
        'location' => $location,
    ];
}

function csrf(array $response): string
{
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $match);
    return html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8');
}

function form(Fixture $fixture): array
{
    return [
        'FixtureForm' => [
            'tournament_id' => $fixture->tournament_id,
            'home_team_id' => $fixture->home_team_id,
            'away_team_id' => $fixture->away_team_id,
            'venue_id' => $fixture->venue_id,
            'group_name' => $fixture->group_name,
            'kickoff_at' => $fixture->kickoffAtBeijing()->format('Y-m-d H:i:s'),
            'match_status' => $fixture->match_status,
            'home_score' => $fixture->home_score,
            'away_score' => $fixture->away_score,
        ],
    ];
}

$password = Yii::$app->security->generateRandomString(24);
$account = new Account();
$account->username = 'w11_check_' . bin2hex(random_bytes(4));
$account->display_name = 'W11 校验账号';
$account->auth_key = Yii::$app->security->generateRandomString(32);
$account->setPassword($password);
check($account->save(), 'create temporary administrator');

$tournament = Tournament::find()->orderBy(['id' => SORT_ASC])->one();
$teams = NationalTeam::find()->orderBy(['id' => SORT_ASC])->limit(2)->all();
$venue = Venue::find()->orderBy(['id' => SORT_ASC])->one();

$fixtureId = null;

try {
    $response = request('/admin/login');
    $token = csrf($response);
    $response = request('/admin/login', 'POST', ['_csrf' => $token,
        'LoginForm' => ['username' => $account->username, 'password' => $password]]);
    check(in_array($response['status'], [302, 303], true), 'sign in as administrator');

    $response = request('/admin/matches');
    check($response['status'] === 200, 'render fixture index');
    check(str_contains($response['body'], '赛事管理'), 'index shows its heading');
    check(str_contains($response['body'], '演示') && str_contains($response['body'], '只读'), 'index separates demo rows from read-only samples');

    $response = request('/admin/matches/create');
    check($response['status'] === 200 && str_contains($response['body'], '新建演示比赛'), 'render create form');

    $payload = ['FixtureForm' => [
        'tournament_id' => $tournament->id,
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'venue_id' => $venue->id,
        'group_name' => 'C',
        'kickoff_at' => '2026-06-20 20:30:00',
        'match_status' => Fixture::STATUS_SCHEDULED,
        'home_score' => '',
        'away_score' => '',
        'is_demo' => '0',
        'sample_key' => 'official-tampered',
    ]];
    $response = request('/admin/matches/create', 'POST', $payload + ['_csrf' => csrf(request('/admin/matches/create'))]);
    check(in_array($response['status'], [302, 303], true), 'save draft and redirect');
    preg_match('#/admin/matches/(\d+)/edit#', $response['location'], $match);
    check(isset($match[1]), 'redirect to the editor of the new fixture');
    $fixtureId = (int) $match[1];

    $fixture = Fixture::findOne($fixtureId);
    check($fixture !== null && $fixture->isDemo(), 'new record is always demo');
    check(str_starts_with($fixture->sample_key, 'demo-'), 'sample key is generated server side');
    check($fixture->publication_status === Fixture::PUBLICATION_DRAFT, 'new record starts as draft');
    check($fixture->match_status === Fixture::STATUS_SCHEDULED && $fixture->home_score === null, 'new record has no score');
    check($fixture->kickoff_at === '2026-06-20 12:30:00', 'kickoff is stored as UTC');

    $response = request('/admin/matches/' . $fixtureId . '/edit');
    check($response['status'] === 200 && str_contains($response['body'], '2026-06-20 20:30:00'), 'editor shows Beijing time');
    check(str_contains($response['body'], '发布'), 'draft editor offers the publish action');

    $response = request('/world-cup/matches');
    check(!str_contains($response['body'], '/world-cup/matches/' . $fixtureId . '"'), 'draft is hidden from the public list');
    $response = request('/world-cup/matches/' . $fixtureId);
    check($response['status'] === 404, 'draft detail returns 404');

    $response = request('/admin/matches/' . $fixtureId . '/publish', 'POST', ['_csrf' => csrf(request('/admin/matches/' . $fixtureId . '/edit'))]);
    check(in_array($response['status'], [302, 303], true), 'publish draft');
    $fixture->refresh();
    check($fixture->publication_status === Fixture::PUBLICATION_PUBLISHED && $fixture->published_at !== null, 'published with a timestamp');

    $response = request('/world-cup/matches');
    check(str_contains($response['body'], '/world-cup/matches/' . $fixtureId . '"'), 'published demo appears in the public list');

    $publishedAt = $fixture->published_at;
    request('/admin/matches/' . $fixtureId . '/publish', 'POST', ['_csrf' => csrf(request('/admin/matches/' . $fixtureId . '/edit'))]);
    $fixture->refresh();
    check($fixture->published_at === $publishedAt, 'republishing keeps the original timestamp');

    $response = request('/admin/matches/' . $fixtureId . '/edit');
    check(str_contains($response['body'], '已发布') && !str_contains($response['body'], 'fx-publish'), 'published editor hides the publish action');

    $finished = form($fixture);
    $finished['FixtureForm']['match_status'] = Fixture::STATUS_FINISHED;
    $finished['FixtureForm']['home_score'] = '0';
    $finished['FixtureForm']['away_score'] = '0';
    $finished['_csrf'] = csrf(request('/admin/matches/' . $fixtureId . '/edit'));
    $response = request('/admin/matches/' . $fixtureId . '/edit', 'POST', $finished);
    check(in_array($response['status'], [302, 303], true), 'record a 0:0 result');
    $fixture->refresh();
    check($fixture->match_status === Fixture::STATUS_FINISHED && $fixture->home_score === 0 && $fixture->away_score === 0, '0:0 is a valid score');
    check($fixture->score_updated_at !== null, 'score timestamp is refreshed');

    $response = request('/world-cup/matches?status=finished');
    check(str_contains($response['body'], '/world-cup/matches/' . $fixtureId . '"') && str_contains($response['body'], '0 : 0'), '0:0 appears under finished');
    $response = request('/world-cup/matches?status=scheduled');
    check(!str_contains($response['body'], '/world-cup/matches/' . $fixtureId . '"'), 'finished demo is absent from scheduled');

    $venueOnly = form($fixture);
    $venueOnly['FixtureForm']['group_name'] = 'D';
    $venueOnly['_csrf'] = csrf(request('/admin/matches/' . $fixtureId . '/edit'));
    $stamp = $fixture->score_updated_at;
    request('/admin/matches/' . $fixtureId . '/edit', 'POST', $venueOnly);
    $fixture->refresh();
    check($fixture->score_updated_at === $stamp, 'editing without score change keeps the score timestamp');

    $invalid = form($fixture);
    $invalid['FixtureForm']['match_status'] = Fixture::STATUS_FINISHED;
    $invalid['FixtureForm']['home_score'] = '';
    $invalid['FixtureForm']['away_score'] = '';
    $invalid['_csrf'] = csrf(request('/admin/matches/' . $fixtureId . '/edit'));
    $response = request('/admin/matches/' . $fixtureId . '/edit', 'POST', $invalid);
    check($response['status'] === 200 && str_contains($response['body'], '必须填写双方比分'), 'finished without scores is rejected');

    $invalid = form($fixture);
    $invalid['FixtureForm']['match_status'] = Fixture::STATUS_SCHEDULED;
    $invalid['FixtureForm']['home_score'] = '1';
    $invalid['FixtureForm']['away_score'] = '0';
    $invalid['_csrf'] = csrf(request('/admin/matches/' . $fixtureId . '/edit'));
    $response = request('/admin/matches/' . $fixtureId . '/edit', 'POST', $invalid);
    check($response['status'] === 200 && str_contains($response['body'], '未开赛的比赛不能填写比分'), 'scheduled with scores is rejected');

    $invalid = form($fixture);
    $invalid['FixtureForm']['away_team_id'] = $invalid['FixtureForm']['home_team_id'];
    $invalid['_csrf'] = csrf(request('/admin/matches/' . $fixtureId . '/edit'));
    $response = request('/admin/matches/' . $fixtureId . '/edit', 'POST', $invalid);
    check($response['status'] === 200 && str_contains($response['body'], '主队与客队不能相同'), 'same team on both sides is rejected');

    $official = Fixture::find()->where(['is_demo' => 0])->one();
    $response = request('/admin/matches/' . $official->id . '/edit');
    check($response['status'] === 403, 'official sample cannot be edited');
    $response = request('/admin/matches/' . $official->id . '/publish', 'POST', ['_csrf' => csrf(request('/admin/matches'))]);
    check($response['status'] === 403, 'official sample cannot be published');
    $response = request('/admin/matches/999999/edit');
    check($response['status'] === 404, 'unknown fixture returns 404');
} finally {
    if ($fixtureId !== null) {
        Fixture::deleteAll(['id' => $fixtureId]);
    }
    $account->delete();
}

echo 'ALL W11 CHECKS PASSED' . PHP_EOL;
