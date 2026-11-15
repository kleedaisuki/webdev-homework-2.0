<?php
/**
 * 用途：世界杯届次模型 Tournament（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 一期只维护一届精选小组赛（默认 2026），届次是公开查询的过滤条件之一。
 *
 * @property int $id
 * @property int $edition
 * @property string $name
 * @property string $summary
 * @property string $created_at
 * @property string $updated_at
 */
class Tournament extends ActiveRecord
{
    /** 默认展示届次 */
    public const DEFAULT_EDITION = 2026;

    public static function tableName()
    {
        return '{{%tournament}}';
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
            [['edition', 'name', 'summary'], 'required'],
            [['edition'], 'integer', 'min' => 1900, 'max' => 2999],
            [['edition'], 'unique'],
            [['name'], 'string', 'max' => 120],
            [['summary'], 'string', 'max' => 5000],
            [['summary'], 'trim'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'edition' => '届次',
            'name' => '名称',
            'summary' => '简介',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 本届全部比赛（不做发布状态过滤，公开页请用 Fixture::findPublished()）。
     */
    public function getFixtures(): ActiveQuery
    {
        return $this->hasMany(Fixture::class, ['tournament_id' => 'id']);
    }
}
