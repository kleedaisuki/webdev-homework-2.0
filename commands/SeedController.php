<?php
/**
 * 用途：从 data/seed/world-cup-sample.csv 导入世界杯样本数据（W07）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\commands;

use app\models\Fixture;
use app\models\NationalTeam;
use app\models\Tournament;
use app\models\Venue;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * 样本导入命令：`php yii seed/fixtures`
 *
 * 规则：
 *  - 先整文件校验，任何一行不合格则整体回滚，并报告行号；
 *  - 以 sample_key 幂等，重复执行不会产生重复比赛；
 *  - 已被编辑过的演示记录（is_demo=1）不覆盖，避免课堂演示数据被冲掉。
 */
class SeedController extends Controller
{
    /** 固定表头，顺序必须完全一致 */
    private const HEADER = [
        'sample_key', 'tournament_edition', 'tournament_name',
        'home_team_code', 'home_team_name', 'away_team_code', 'away_team_name',
        'venue_name', 'venue_city', 'venue_timezone',
        'stage', 'group_name', 'kickoff_at', 'match_status', 'publication_status',
        'home_score', 'away_score', 'is_demo', 'source_url', 'source_checked_at',
    ];

    /**
     * 导入样本数据。
     *
     * @param string|null $file 自定义 CSV 路径（别名或绝对路径），默认 @app/data/seed/world-cup-sample.csv
     * @return int 退出码
     */
    public function actionFixtures($file = null)
    {
        $path = $file === null
            ? Yii::getAlias('@app/data/seed/world-cup-sample.csv')
            : Yii::getAlias($file);

        if (!is_file($path)) {
            $this->stderr("找不到样本文件：{$path}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $rows = $this->readRows($path);
        } catch (\RuntimeException $e) {
            $this->stderr($e->getMessage() . "\n");
            return ExitCode::DATAERR;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $stats = $this->importRows($rows);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr('导入失败，已整体回滚：' . $e->getMessage() . "\n");
            return ExitCode::DATAERR;
        }

        $this->stdout(sprintf(
            "导入完成：比赛 新建 %d / 更新 %d / 保留演示 %d；国家队 %d，场馆 %d，届次 %d。\n",
            $stats['created'], $stats['updated'], $stats['preserved'],
            $stats['teams'], $stats['venues'], $stats['tournaments']
        ));

        return ExitCode::OK;
    }

    /**
     * 读取并逐行校验 CSV，返回已规范化的行数组。
     *
     * @throws \RuntimeException 文件结构或单元格内容不合法
     */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("无法打开样本文件：{$path}");
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('样本文件是空的。');
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map('trim', $header);

        if ($header !== self::HEADER) {
            fclose($handle);
            throw new \RuntimeException(
                "表头不匹配。\n期望：" . implode(',', self::HEADER) . "\n实际：" . implode(',', $header)
            );
        }

        $rows = [];
        $lineNo = 1;
        while (($cells = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNo++;
            if ($cells === [null] || (count($cells) === 1 && trim((string) $cells[0]) === '')) {
                continue; // 跳过空行
            }
            if (count($cells) !== count(self::HEADER)) {
                fclose($handle);
                throw new \RuntimeException("第 {$lineNo} 行列数错误：期望 " . count(self::HEADER) . ' 列，实际 ' . count($cells) . ' 列。');
            }
            $row = array_combine(self::HEADER, array_map('trim', $cells));
            $rows[] = $this->normalizeRow($row, $lineNo);
        }
        fclose($handle);

        if ($rows === []) {
            throw new \RuntimeException('样本文件里没有数据行。');
        }

        return $rows;
    }

    /**
     * 校验单行并把时间统一换算成 UTC、把数字转成整型。
     *
     * @throws \RuntimeException 单元格不合法
     */
    private function normalizeRow(array $row, int $lineNo): array
    {
        $fail = static function (string $message) use ($lineNo) {
            throw new \RuntimeException("第 {$lineNo} 行：{$message}");
        };

        foreach (['sample_key', 'tournament_edition', 'home_team_code', 'away_team_code', 'venue_name', 'venue_city', 'venue_timezone', 'kickoff_at', 'match_status', 'publication_status', 'is_demo'] as $field) {
            if ($row[$field] === '') {
                $fail("字段 {$field} 不能为空。");
            }
        }

        if (!preg_match('/^[A-Z0-9]{3}$/', $row['home_team_code']) || !preg_match('/^[A-Z0-9]{3}$/', $row['away_team_code'])) {
            $fail('球队代码必须是 3 位大写 ASCII 字母或数字。');
        }
        if ($row['home_team_code'] === $row['away_team_code']) {
            $fail('主队与客队不能相同。');
        }
        if (!in_array($row['match_status'], [Fixture::STATUS_SCHEDULED, Fixture::STATUS_FINISHED], true)) {
            $fail("match_status 只能是 scheduled 或 finished，实际为 {$row['match_status']}。");
        }
        if (!in_array($row['publication_status'], [Fixture::PUBLICATION_DRAFT, Fixture::PUBLICATION_PUBLISHED], true)) {
            $fail("publication_status 只能是 draft 或 published，实际为 {$row['publication_status']}。");
        }
        if (!in_array($row['is_demo'], ['0', '1'], true)) {
            $fail('is_demo 只能是 0 或 1。');
        }

        $kickoff = $this->toUtc($row['kickoff_at']);
        if ($kickoff === null) {
            $fail("kickoff_at 不是带时区的 ISO 8601 时间：{$row['kickoff_at']}。");
        }

        $finished = $row['match_status'] === Fixture::STATUS_FINISHED;
        $homeScore = $this->toScore($row['home_score']);
        $awayScore = $this->toScore($row['away_score']);

        if ($finished) {
            if ($homeScore === null || $awayScore === null) {
                $fail('已完赛的行必须同时填写 0-99 的双方比分（0 是合法比分）。');
            }
        } elseif ($row['home_score'] !== '' || $row['away_score'] !== '') {
            $fail('未开赛的行不能填写比分。');
        }

        $sourceCheckedAt = $row['source_checked_at'] === '' ? null : $this->toUtc($row['source_checked_at']);
        if ($row['source_checked_at'] !== '' && $sourceCheckedAt === null) {
            $fail("source_checked_at 不是合法时间：{$row['source_checked_at']}。");
        }
        if ($row['is_demo'] === '0' && ($row['source_url'] === '' || $sourceCheckedAt === null)) {
            $fail('正式样本（is_demo=0）必须填写 source_url 与 source_checked_at。');
        }
        if ($row['source_url'] !== '' && filter_var($row['source_url'], FILTER_VALIDATE_URL) === false) {
            $fail("source_url 不是合法 URL：{$row['source_url']}。");
        }

        return [
            'sample_key' => $row['sample_key'],
            'edition' => (int) $row['tournament_edition'],
            'tournament_name' => $row['tournament_name'] === '' ? 'FIFA World Cup' : $row['tournament_name'],
            'home_code' => $row['home_team_code'],
            'home_name' => $row['home_team_name'] === '' ? $row['home_team_code'] : $row['home_team_name'],
            'away_code' => $row['away_team_code'],
            'away_name' => $row['away_team_name'] === '' ? $row['away_team_code'] : $row['away_team_name'],
            'venue_name' => $row['venue_name'],
            'venue_city' => $row['venue_city'],
            'venue_timezone' => $row['venue_timezone'],
            'stage' => $row['stage'] === '' ? 'group' : $row['stage'],
            'group_name' => $row['group_name'] === '' ? null : $row['group_name'],
            'kickoff_at' => $kickoff,
            'match_status' => $row['match_status'],
            'publication_status' => $row['publication_status'],
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'is_demo' => (int) $row['is_demo'],
            'source_url' => $row['source_url'] === '' ? null : $row['source_url'],
            'source_checked_at' => $sourceCheckedAt,
        ];
    }

    /**
     * 数据库导入，调用方负责事务。
     */
    private function importRows(array $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'preserved' => 0, 'teams' => 0, 'venues' => 0, 'tournaments' => 0];
        $now = gmdate('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $tournament = Tournament::findOne(['edition' => $row['edition']]);
            if ($tournament === null) {
                $tournament = new Tournament([
                    'edition' => $row['edition'],
                    'name' => $row['tournament_name'],
                    'summary' => $row['tournament_name'] . ' 精选小组赛，仅收录课程演示所需的场次。',
                ]);
                $this->saveOrFail($tournament, $row['sample_key']);
                $stats['tournaments']++;
            }

            $homeTeam = $this->ensureTeam($row['home_code'], $row['home_name'], $stats);
            $awayTeam = $this->ensureTeam($row['away_code'], $row['away_name'], $stats);

            $venue = Venue::findOne(['name' => $row['venue_name'], 'city' => $row['venue_city']]);
            if ($venue === null) {
                $venue = new Venue([
                    'name' => $row['venue_name'],
                    'city' => $row['venue_city'],
                    'timezone' => $row['venue_timezone'],
                ]);
                $this->saveOrFail($venue, $row['sample_key']);
                $stats['venues']++;
            }

            $fixture = Fixture::findOne(['sample_key' => $row['sample_key']]);
            if ($fixture !== null && $fixture->isDemo()) {
                $stats['preserved']++;
                continue;
            }
            $isNew = $fixture === null;
            if ($isNew) {
                $fixture = new Fixture(['sample_key' => $row['sample_key']]);
            }

            $finished = $row['match_status'] === Fixture::STATUS_FINISHED;
            // 比分更新时间取开球后 2 小时，保证重复导入结果稳定（真实录入由 W11 覆盖）。
            $scoreUpdatedAt = $finished
                ? (new \DateTimeImmutable($row['kickoff_at'], new \DateTimeZone('UTC')))->modify('+2 hours')->format('Y-m-d H:i:s')
                : null;

            $fixture->setAttributes([
                'tournament_id' => $tournament->id,
                'venue_id' => $venue->id,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'stage' => $row['stage'],
                'group_name' => $row['group_name'],
                'kickoff_at' => $row['kickoff_at'],
                'match_status' => $row['match_status'],
                'publication_status' => $row['publication_status'],
                'home_score' => $row['home_score'],
                'away_score' => $row['away_score'],
                'is_demo' => $row['is_demo'],
                'source_url' => $row['source_url'],
                'source_checked_at' => $row['source_checked_at'],
                'published_at' => $fixture->published_at ?: ($row['publication_status'] === Fixture::PUBLICATION_PUBLISHED
                    ? ($row['source_checked_at'] ?? $now)
                    : null),
                'score_updated_at' => $scoreUpdatedAt,
            ], false);

            $this->saveOrFail($fixture, $row['sample_key']);
            $stats[$isNew ? 'created' : 'updated']++;
        }

        return $stats;
    }

    /**
     * 按代码取国家队，名称变化时更新。
     */
    private function ensureTeam(string $code, string $name, array &$stats): NationalTeam
    {
        $team = NationalTeam::findOne(['code' => $code]);
        if ($team === null) {
            $team = new NationalTeam(['code' => $code, 'name' => $name]);
            $this->saveOrFail($team, $code);
            $stats['teams']++;
            return $team;
        }
        if ($team->name !== $name) {
            $team->name = $name;
            $this->saveOrFail($team, $code);
        }

        return $team;
    }

    /**
     * 保存失败时抛出带字段错误的异常，由上层回滚事务。
     */
    private function saveOrFail(\yii\db\ActiveRecord $model, string $label): void
    {
        if ($model->save()) {
            return;
        }
        $messages = [];
        foreach ($model->getErrors() as $attribute => $errors) {
            $messages[] = $attribute . ': ' . implode('；', $errors);
        }
        throw new \RuntimeException("[{$label}] " . implode(' | ', $messages));
    }

    /**
     * 带偏移量的 ISO 8601 → UTC "Y-m-d H:i:s"；无法解析返回 null。
     */
    private function toUtc(string $value): ?string
    {
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return null;
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * 比分单元格：空串返回 null，0 必须保留为 0。
     */
    private function toScore(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d{1,2}$/', $value)) {
            return null;
        }

        return (int) $value;
    }
}
