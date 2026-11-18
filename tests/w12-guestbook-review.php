<?php

use app\models\Account;
use app\models\GuestMessage;
use yii\db\Query;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');

final class Browser
{
    private array $cookies = [];

    public function __construct(private string $baseUrl)
    {
    }

    public function request(string $path, string $method = 'GET', array $form = []): array
    {
        $headers = ['Accept: text/html', 'Cookie: ' . implode('; ', $this->cookies)];
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $body = file_get_contents($this->baseUrl . $path, false, stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => http_build_query($form),
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 10,
        ]]));
        if ($body === false || !isset($http_response_header[0])) {
            throw new RuntimeException("$method $path: no HTTP response");
        }

        $location = '';
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Set-Cookie: ') === 0) {
                $cookie = explode(';', substr($header, 12), 2)[0];
                $this->cookies[explode('=', $cookie, 2)[0]] = $cookie;
            } elseif (stripos($header, 'Location: ') === 0) {
                $location = substr($header, 10);
            }
        }

        return ['status' => (int) substr($http_response_header[0], 9, 3), 'location' => $location, 'body' => $body];
    }
}

function csrf(array $response): string
{
    if (!preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $match)) {
        throw new RuntimeException('Missing CSRF token in HTTP ' . $response['status']);
    }
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}

function check(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL ' . $description);
    }
    echo 'PASS ' . $description . PHP_EOL;
}

function reviews(int|string $id): array
{
    return (new Query())->from('{{%guest_review}}')->where(['message_id' => $id])->orderBy('id')->all();
}

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$visitor = new Browser($baseUrl);
$admin = new Browser($baseUrl);
$marker = 'w12_' . bin2hex(random_bytes(6));
$names = [$marker . '_pass', $marker . '_reject'];
$password = Yii::$app->security->generateRandomString(32);
$account = new Account([
    'username' => $marker,
    'display_name' => 'W12 Check',
    'auth_key' => Yii::$app->security->generateRandomString(32),
]);
$account->setPassword($password);
check($account->save(), 'create temporary administrator');

try {
    $visitorToken = csrf($visitor->request('/guestbook'));
    $ids = [];
    foreach ($names as $nickname) {
        $response = $visitor->request('/guestbook', 'POST', [
            '_csrf' => $visitorToken,
            'GuestMessage' => ['nickname' => $nickname, 'body' => "世界杯留言\n<b>按纯文本展示</b>"],
        ]);
        check($response['status'] === 303 && str_ends_with($response['location'], '/guestbook'),
            'visitor can submit a message');
        $message = GuestMessage::findOne(['nickname' => $nickname]);
        check($message?->status === GuestMessage::STATUS_PENDING, 'message starts pending');
        $ids[] = $message->id;
    }
    [$passId, $rejectId] = $ids;
    [$passName, $rejectName] = $names;
    $passPath = "/admin/messages/$passId/review";
    $rejectPath = "/admin/messages/$rejectId/review";
    $publicPaths = ['/guestbook', '/world-cup/fans'];
    foreach ($publicPaths as $path) {
        $public = $visitor->request($path);
        check($public['status'] === 200 && !str_contains($public['body'], $marker), "$path hides both pending messages");
    }
    $response = $visitor->request('/admin/messages');
    check($response['status'] === 302 && str_ends_with($response['location'], '/admin/login'),
        'visitor cannot read the moderation queue');

    $response = $admin->request('/admin/login', 'POST', [
        '_csrf' => csrf($admin->request('/admin/login')),
        'LoginForm' => ['username' => $account->username, 'password' => $password],
    ]);
    check($response['status'] === 303 && str_contains($admin->request('/admin')['body'], 'href="/admin/messages"'),
        'administrator can log in and discover the review entry');
    $queue = $admin->request('/admin/messages');
    check($queue['status'] === 200 && str_contains($queue['body'], $passName) && str_contains($queue['body'], $rejectName),
        'both messages are visible in the pending queue');
    check(str_contains($queue['body'], 'action="' . $passPath . '"')
        && str_contains($queue['body'], '&lt;b&gt;按纯文本展示&lt;/b&gt;'), 'queue renders the correct action and escapes body');

    $token = csrf($queue);
    $approve = ['_csrf' => $token, 'ReviewForm' => ['decision' => 'approved']];
    check($admin->request($passPath)['status'] === 405, 'GET cannot review');
    check($admin->request($passPath, 'POST', ['ReviewForm' => ['decision' => 'approved']])['status'] === 400,
        'POST review requires CSRF');
    $invalid = $admin->request($passPath, 'POST', [
        '_csrf' => $token, 'ReviewForm' => ['decision' => 'pending', 'note' => '保留的备注<示例>'],
    ]);
    check($invalid['status'] === 200 && str_contains($invalid['body'], '请选择通过或拒绝')
        && str_contains($invalid['body'], '保留的备注&lt;示例&gt;'), 'invalid decision shows the error and preserves the note');
    foreach ([str_repeat('文', 501), ['nested' => ['value']]] as $invalidNote) {
        $invalid = $admin->request($passPath, 'POST', [
            '_csrf' => $token, 'ReviewForm' => ['decision' => 'approved', 'note' => $invalidNote],
        ]);
        check($invalid['status'] === 200 && str_contains($invalid['body'], 'error-summary'), 'invalid note shows an error');
    }
    check(reviews($passId) === [] && GuestMessage::findOne($passId)->status === 'pending',
        'invalid attempts do not write state or history');
    check($admin->request('/admin/messages/4294967296/review', 'POST', $approve)['status'] === 404,
        'missing message returns 404');

    $response = $admin->request($passPath, 'POST', [
        '_csrf' => $token, 'ReviewForm' => [
            'decision' => 'approved', 'note' => "  {$marker}_internal  ",
            'reviewer_id' => 0, 'message_id' => $rejectId, 'created_at' => '2000-01-01 00:00:00',
        ],
    ]);
    check($response['status'] === 303 && str_ends_with($response['location'], '/admin/messages'),
        'approval redirects to the queue');
    $response = $admin->request($rejectPath, 'POST', ['_csrf' => $token, 'ReviewForm' => ['decision' => 'rejected']]);
    check($response['status'] === 303, 'administrator can reject the other message');

    foreach ([$passId => 'approved', $rejectId => 'rejected'] as $id => $decision) {
        $rows = reviews($id);
        $message = GuestMessage::findOne($id);
        check(count($rows) === 1 && $rows[0]['decision'] === $decision && $message->status === $decision
            && (int) $rows[0]['reviewer_id'] === (int) $account->id
            && $rows[0]['created_at'] === $rows[0]['updated_at']
            && $rows[0]['created_at'] === $message->updated_at && $rows[0]['created_at'] >= $message->created_at,
            "$decision writes audit and state with the authenticated reviewer and server time");
    }
    check(reviews($passId)[0]['note'] === $marker . '_internal' && reviews($rejectId)[0]['note'] === null,
        'trim optional internal note and store omission as NULL');
    $queue = $admin->request('/admin/messages');
    check(!str_contains($queue['body'], $marker) && str_contains($queue['body'], '审核结果：已拒绝'),
        'processed messages leave the queue and decision receipt is shown');
    check(str_contains($admin->request('/admin/messages?status=approved')['body'], $passName)
        && str_contains($admin->request('/admin/messages?status=rejected')['body'], $rejectName),
        'processed messages are available under their matching status');
    foreach ($publicPaths as $path) {
        $public = $visitor->request($path);
        check($public['status'] === 200 && str_contains($public['body'], $passName)
            && !str_contains($public['body'], $rejectName) && !str_contains($public['body'], $marker . '_internal'),
            "$path shows only the approved message and never includes the note");
    }

    check($admin->request($passPath, 'POST', $approve)['status'] === 303 && count(reviews($passId)) === 1,
        'repeating a decision is idempotent');
    $response = $admin->request($passPath, 'POST', ['_csrf' => $token, 'ReviewForm' => ['decision' => 'rejected']]);
    check($response['status'] === 303 && array_column(reviews($passId), 'decision') === ['approved', 'rejected'],
        'changing a decision appends history instead of rewriting it');
    foreach ($publicPaths as $path) {
        $public = $visitor->request($path);
        check($public['status'] === 200 && !str_contains($public['body'], $passName), "$path hides a later rejected message");
    }

    check($admin->request('/admin/logout', 'POST', ['_csrf' => $token])['status'] === 303, 'administrator can log out');
    $response = $admin->request($passPath, 'POST', [
        '_csrf' => csrf($admin->request('/admin/login')),
        'ReviewForm' => ['decision' => 'approved'],
    ]);
    check($response['status'] === 302 && str_ends_with($response['location'], '/admin/login')
        && count(reviews($passId)) === 2 && GuestMessage::findOne($passId)->status === 'rejected',
        'valid-CSRF post after logout cannot write a review');
} finally {
    $ids = GuestMessage::find()->select('id')->where(['nickname' => $names])->column();
    Yii::$app->db->createCommand()->delete('{{%guest_review}}', ['message_id' => $ids])->execute();
    GuestMessage::deleteAll(['id' => $ids]);
    $account->delete();
}
