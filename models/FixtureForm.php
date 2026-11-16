<?php

/**
 * 用途：W11 后台演示赛程的编辑表单与发布动作，负责输入校验、北京时间与 UTC 换算，
 *       并把结果写入 fixture 表（新建草稿、维护已发布演示记录、发布草稿）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\models;

use DateTimeImmutable;
use DateTimeZone;
use Yii;
use yii\base\Model;
use yii\db\Exception;
use yii\db\Transaction;

class FixtureForm extends Model
{
    /** 本课程范围只做小组赛阶段。 */
    public const STAGE_GROUP = 'group';

    /** 表单里的开球时间按北京时间填写。 */
    public const KICKOFF_FORMAT = 'Y-m-d H:i:s';

    public const DISPLAY_TIMEZONE = 'Asia/Shanghai';

    /** @var int|null 届次 */
    public $tournament_id;
    /** @var int|null 主队 */
    public $home_team_id;
    /** @var int|null 客队 */
    public $away_team_id;
    /** @var int|null 场馆 */
    public $venue_id;
    /** @var string|null 小组名，可空 */
    public $group_name;
    /** @var string|null 北京时间，格式 Y-m-d H:i:s */
    public $kickoff_at;
    /** @var string 比赛状态：scheduled / finished */
    public $match_status = Fixture::STATUS_SCHEDULED;
    /** @var int|string|null 主队比分，仅完赛时填写 */
    public $home_score;
    /** @var int|string|null 客队比分，仅完赛时填写 */
    public $away_score;

    /** @var Fixture|null 正在维护的记录；新建时为 null */
    private ?Fixture $fixture = null;

    public function rules(): array
    {
        return [
            [['tournament_id', 'home_team_id', 'away_team_id', 'venue_id', 'kickoff_at', 'match_status'], 'required'],
            [['tournament_id', 'home_team_id', 'away_team_id', 'venue_id'], 'integer', 'min' => 1],
            [['tournament_id'], 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id'],
            [['home_team_id'], 'exist', 'targetClass' => NationalTeam::class, 'targetAttribute' => 'id'],
            [['away_team_id'], 'exist', 'targetClass' => NationalTeam::class, 'targetAttribute' => 'id'],
            [['venue_id'], 'exist', 'targetClass' => Venue::class, 'targetAttribute' => 'id'],
            [['away_team_id'], 'compare', 'compareAttribute' => 'home_team_id', 'operator' => '!=', 'message' => '主队与客队不能相同。'],
            [['group_name'], 'string', 'max' => 8],
            [['kickoff_at'], 'validateKickoff'],
            [['match_status'], 'in', 'range' => [Fixture::STATUS_SCHEDULED, Fixture::STATUS_FINISHED]],
            [['home_score', 'away_score'], 'integer', 'min' => 0, 'max' => 99, 'message' => '比分须为 0–99 的整数。'],
            [['match_status'], 'validateScores'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'tournament_id' => '本届赛事',
            'home_team_id' => '主队',
            'away_team_id' => '客队',
            'venue_id' => '场馆',
            'group_name' => '小组',
            'kickoff_at' => '开球时间',
            'match_status' => '比赛状态',
            'home_score' => '主队比分',
            'away_score' => '客队比分',
        ];
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->group_name = trim((string) $this->group_name) === '' ? null : trim((string) $this->group_name);
        $this->kickoff_at = trim((string) $this->kickoff_at);

        foreach (['home_score', 'away_score'] as $attribute) {
            if ($this->{$attribute} === '' || $this->{$attribute} === null) {
                $this->{$attribute} = null;
            }
        }

        return true;
    }

    /**
     * 开球时间必须是合法的北京时间。
     */
    public function validateKickoff(string $attribute): void
    {
        if ($this->parseBeijing($this->{$attribute}) === null) {
            $this->addError($attribute, '开球时间格式应为 YYYY-MM-DD HH:mm:ss（北京时间）。');
        }
    }

    /**
     * 未开赛不得有比分；已完赛必须是两个非负整数（0:0 合法）。
     */
    public function validateScores(string $attribute): void
    {
        $hasHome = $this->home_score !== null;
        $hasAway = $this->away_score !== null;

        if ($this->match_status === Fixture::STATUS_FINISHED) {
            if (!$hasHome || !$hasAway) {
                $this->addError('home_score', '已完赛的比赛必须填写双方比分。');
            }
            return;
        }

        if ($hasHome || $hasAway) {
            $this->addError('home_score', '未开赛的比赛不能填写比分。');
        }
    }

    /**
     * 把一条已有记录载入表单（编辑前调用）。
     */
    public function loadFixture(Fixture $fixture): void
    {
        $this->fixture = $fixture;
        $this->tournament_id = $fixture->tournament_id;
        $this->home_team_id = $fixture->home_team_id;
        $this->away_team_id = $fixture->away_team_id;
        $this->venue_id = $fixture->venue_id;
        $this->group_name = $fixture->group_name;
        $this->kickoff_at = $this->toBeijing($fixture->kickoff_at)->format(self::KICKOFF_FORMAT);
        $this->match_status = $fixture->match_status;
        $this->home_score = $fixture->home_score;
        $this->away_score = $fixture->away_score;
    }

    public function getFixture(): ?Fixture
    {
        return $this->fixture;
    }

    public function isNewRecord(): bool
    {
        return $this->fixture === null || $this->fixture->isNewRecord;
    }

    public function canPublish(): bool
    {
        return $this->fixture !== null
            && $this->fixture->isDemo()
            && $this->fixture->publication_status === Fixture::PUBLICATION_DRAFT;
    }

    /**
     * 新建演示草稿，或保存对已有演示记录的修改。
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $fixture = $this->fixture ?? new Fixture();
        $isNew = $fixture->isNewRecord;

        if (!$isNew && !$fixture->isDemo()) {
            $this->addError('home_team_id', '正式样本只读，不能在后台修改。');
            return false;
        }

        $previousStatus = $fixture->match_status;
        $previousHome = $fixture->home_score;
        $previousAway = $fixture->away_score;

        if ($isNew) {
            $fixture->sample_key = $this->generateSampleKey();
            $fixture->is_demo = 1;
            $fixture->stage = self::STAGE_GROUP;
            $fixture->publication_status = Fixture::PUBLICATION_DRAFT;
        }

        $fixture->tournament_id = (int) $this->tournament_id;
        $fixture->home_team_id = (int) $this->home_team_id;
        $fixture->away_team_id = (int) $this->away_team_id;
        $fixture->venue_id = (int) $this->venue_id;
        $fixture->group_name = $this->group_name;
        $fixture->kickoff_at = $this->toUtc($this->kickoff_at);
        $fixture->match_status = $this->match_status;

        if ($this->match_status === Fixture::STATUS_FINISHED) {
            $fixture->home_score = (int) $this->home_score;
            $fixture->away_score = (int) $this->away_score;
            $scoreChanged = $previousHome !== $fixture->home_score || $previousAway !== $fixture->away_score;
            if ($isNew || $scoreChanged || $previousStatus !== Fixture::STATUS_FINISHED) {
                $fixture->score_updated_at = self::now();
            }
        } else {
            $fixture->home_score = null;
            $fixture->away_score = null;
            $fixture->score_updated_at = null;
        }

        /** @var Transaction $transaction */
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$fixture->save()) {
                $transaction->rollBack();
                $this->copyErrors($fixture);
                return false;
            }
            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();
            $this->addError('match_status', '保存失败，请稍后再试。');
            Yii::error($exception->getMessage(), __METHOD__);
            return false;
        }

        $this->fixture = $fixture;
        return true;
    }

    /**
     * 把草稿发布到前台；重复发布不改变原发布时间。
     */
    public function publish(): bool
    {
        if (!$this->canPublish()) {
            $this->addError('match_status', '只有演示草稿可以发布。');
            return false;
        }

        $fixture = $this->fixture;
        $fixture->publication_status = Fixture::PUBLICATION_PUBLISHED;
        if ($fixture->published_at === null || $fixture->published_at === '') {
            $fixture->published_at = self::now();
        }

        if (!$fixture->save()) {
            $this->copyErrors($fixture);
            return false;
        }

        return true;
    }

    private function copyErrors(Fixture $fixture): void
    {
        foreach ($fixture->getErrors() as $attribute => $messages) {
            foreach ($messages as $message) {
                $this->addError($attribute, $message);
            }
        }
    }

    private function generateSampleKey(): string
    {
        do {
            $key = 'demo-' . gmdate('Ymd') . '-' . bin2hex(random_bytes(3));
        } while (Fixture::find()->where(['sample_key' => $key])->exists());

        return $key;
    }

    private function toUtc(string $beijing): string
    {
        return (new DateTimeImmutable($beijing, new DateTimeZone(self::DISPLAY_TIMEZONE)))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::KICKOFF_FORMAT);
    }

    private function toBeijing(string $utc): DateTimeImmutable
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
    }

    private function parseBeijing(string $value): ?DateTimeImmutable
    {
        foreach ([self::KICKOFF_FORMAT, 'Y-m-d H:i'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone(self::DISPLAY_TIMEZONE));
            if ($parsed !== false) {
                return $parsed;
            }
        }

        return null;
    }

    private static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
