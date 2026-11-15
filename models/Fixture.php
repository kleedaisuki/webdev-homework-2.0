<?php
/**
 * 用途：比赛模型 Fixture（W07 比赛与赛果浏览），含公开查询、状态与比分约束。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 一场比赛。比赛状态（是否踢完）与发布状态（是否公开）是两条独立维度：
 *
 *  - scheduled + draft     ：比分 NULL，前台不可见
 *  - scheduled + published ：前台可见「未开赛」，不渲染比分
 *  - finished  + draft     ：比分 0-99，前台不可见
 *  - finished  + published ：前台可见，含 0:0
 *
 * 因此判断比分是否为空只能用 `=== null`，不能用 `empty()` 或 `?: 0`。
 *
 * @property int $id
 * @property string $sample_key
 * @property int $tournament_id
 * @property int $venue_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property string $stage
 * @property string|null $group_name
 * @property string $kickoff_at
 * @property string $match_status
 * @property string $publication_status
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int $is_demo
 * @property string|null $source_url
 * @property string|null $source_checked_at
 * @property string|null $published_at
 * @property string|null $score_updated_at
 * @property string $created_at
 * @property string $updated_at
 */
class Fixture extends ActiveRecord
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_FINISHED = 'finished';

    public const PUBLICATION_DRAFT = 'draft';
    public const PUBLICATION_PUBLISHED = 'published';

    /** 公开页统一使用的展示时区 */
    public const DISPLAY_TIMEZONE = 'Asia/Shanghai';

    /** 列表每页条数 */
    public const PAGE_SIZE = 20;

    public static function tableName()
    {
        return '{{%fixture}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => static function () {
                    return gmdate('Y-m-d H:i:s');
                },
            ],
        ];
    }

    public function rules()
    {
        return [
            [['sample_key', 'tournament_id', 'venue_id', 'home_team_id', 'away_team_id', 'kickoff_at', 'match_status', 'publication_status'], 'required'],
            [['tournament_id', 'venue_id', 'home_team_id', 'away_team_id'], 'integer', 'min' => 1],
            [['home_score', 'away_score'], 'integer', 'min' => 0, 'max' => 99],
            [['is_demo'], 'integer', 'min' => 0, 'max' => 1],
            [['sample_key'], 'trim'],
            [['sample_key'], 'string', 'max' => 80],
            [['stage'], 'string', 'max' => 32],
            [['group_name'], 'string', 'max' => 8],
            [['source_url'], 'string', 'max' => 2048],
            [['source_url'], 'url'],
            [['match_status'], 'in', 'range' => [self::STATUS_SCHEDULED, self::STATUS_FINISHED]],
            [['publication_status'], 'in', 'range' => [self::PUBLICATION_DRAFT, self::PUBLICATION_PUBLISHED]],
            [['tournament_id'], 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id'],
            [['venue_id'], 'exist', 'targetClass' => Venue::class, 'targetAttribute' => 'id'],
            [['home_team_id'], 'exist', 'targetClass' => NationalTeam::class, 'targetAttribute' => 'id'],
            [['away_team_id'], 'exist', 'targetClass' => NationalTeam::class, 'targetAttribute' => 'id'],
            [['away_team_id'], 'compare', 'compareAttribute' => 'home_team_id', 'operator' => '!=', 'message' => '主队与客队不能相同。'],
            [['kickoff_at', 'source_checked_at', 'published_at', 'score_updated_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['source_url', 'source_checked_at'], 'validateSource', 'skipOnEmpty' => false],
            [['match_status'], 'validateStatusAndScores', 'skipOnEmpty' => false],
            [['publication_status'], 'validatePublication', 'skipOnEmpty' => false],
        ];
    }

    /**
     * 正式样本必须能追溯来源；演示记录可以没有来源。
     */
    public function validateSource(string $attribute): void
    {
        if ($this->isDemo()) {
            return;
        }
        if ($this->source_url === null || $this->source_url === '') {
            $this->addError('source_url', '正式样本必须填写 FIFA 来源地址。');
        }
        if ($this->source_checked_at === null || $this->source_checked_at === '') {
            $this->addError('source_checked_at', '正式样本必须记录来源核对时间。');
        }
    }

    /**
     * 状态与比分必须自洽：未开赛没有比分，完赛必须有比分和比分更新时间。
     */
    public function validateStatusAndScores(string $attribute): void
    {
        if ($this->match_status === self::STATUS_FINISHED) {
            if ($this->home_score === null || $this->away_score === null) {
                $this->addError('home_score', '已完赛的比赛必须填写双方比分（0 是合法比分）。');
            }
            if ($this->score_updated_at === null || $this->score_updated_at === '') {
                $this->addError('score_updated_at', '已完赛的比赛必须有比分更新时间。');
            }
            return;
        }

        if ($this->home_score !== null || $this->away_score !== null) {
            $this->addError('home_score', '未开赛的比赛不能有比分。');
        }
        if ($this->score_updated_at !== null && $this->score_updated_at !== '') {
            $this->addError('score_updated_at', '未开赛的比赛不能有比分更新时间。');
        }
    }

    /**
     * 已发布的比赛必须有首次发布时间，草稿必须没有。
     */
    public function validatePublication(string $attribute): void
    {
        $published = $this->publication_status === self::PUBLICATION_PUBLISHED;
        $hasPublishedAt = $this->published_at !== null && $this->published_at !== '';

        if ($published && !$hasPublishedAt) {
            $this->addError('published_at', '已发布的比赛必须有首次发布时间。');
        }
        if (!$published && $hasPublishedAt) {
            $this->addError('published_at', '草稿不能有发布时间。');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sample_key' => '样本标识',
            'tournament_id' => '届次',
            'venue_id' => '场馆',
            'home_team_id' => '主队',
            'away_team_id' => '客队',
            'stage' => '阶段',
            'group_name' => '小组',
            'kickoff_at' => '开球时间（UTC）',
            'match_status' => '比赛状态',
            'publication_status' => '发布状态',
            'home_score' => '主队比分',
            'away_score' => '客队比分',
            'is_demo' => '演示数据',
            'source_url' => '来源地址',
            'source_checked_at' => '来源核对时间',
            'published_at' => '发布时间',
            'score_updated_at' => '比分更新时间',
        ];
    }

    /**
     * 公开查询入口：所有前台可见的比赛都必须经过这里，避免遗漏发布状态过滤。
     */
    public static function findPublished(): ActiveQuery
    {
        return static::find()->andWhere(['publication_status' => self::PUBLICATION_PUBLISHED]);
    }

    public function isFinished(): bool
    {
        return $this->match_status === self::STATUS_FINISHED;
    }

    public function isDemo(): bool
    {
        return (int) $this->is_demo === 1;
    }

    /**
     * 未开赛返回 null（前台显示「未开赛」），完赛返回 "2 : 0"；0:0 必须能正常显示。
     */
    public function displayScore(): ?string
    {
        if (!$this->isFinished() || $this->home_score === null || $this->away_score === null) {
            return null;
        }

        return (int) $this->home_score . ' : ' . (int) $this->away_score;
    }

    /**
     * 库里是 UTC，展示时换成北京时间。
     */
    public function kickoffAtBeijing(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable((string) $this->kickoff_at, new \DateTimeZone('UTC')))
            ->setTimezone(new \DateTimeZone(self::DISPLAY_TIMEZONE));
    }

    public function getTournament(): ActiveQuery
    {
        return $this->hasOne(Tournament::class, ['id' => 'tournament_id']);
    }

    public function getVenue(): ActiveQuery
    {
        return $this->hasOne(Venue::class, ['id' => 'venue_id']);
    }

    public function getHomeTeam(): ActiveQuery
    {
        return $this->hasOne(NationalTeam::class, ['id' => 'home_team_id']);
    }

    public function getAwayTeam(): ActiveQuery
    {
        return $this->hasOne(NationalTeam::class, ['id' => 'away_team_id']);
    }
}
