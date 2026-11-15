<?php
/**
 * 用途：国家队模型 NationalTeam（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 参赛国家队。code 为三位大写 ASCII 代码，演示队使用保留的本地代码 DMA/DMB。
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 */
class NationalTeam extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%national_team}}';
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
            [['code', 'name'], 'required'],
            [['code'], 'trim'],
            [['code'], 'string', 'length' => 3],
            [['code'], 'match', 'pattern' => '/^[A-Z0-9]{3}$/', 'message' => '代码必须是 3 位大写 ASCII 字母或数字。'],
            [['code'], 'unique'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => '代码',
            'name' => '名称',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 该队作为主队的比赛。
     */
    public function getHomeFixtures(): ActiveQuery
    {
        return $this->hasMany(Fixture::class, ['home_team_id' => 'id']);
    }

    /**
     * 该队作为客队的比赛。
     */
    public function getAwayFixtures(): ActiveQuery
    {
        return $this->hasMany(Fixture::class, ['away_team_id' => 'id']);
    }
}
