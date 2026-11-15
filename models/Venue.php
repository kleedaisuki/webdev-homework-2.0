<?php
/**
 * 用途：比赛场馆模型 Venue（W07 比赛与赛果浏览）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 比赛场馆。timezone 保存真实 IANA 时区名，用于核对来源页面的本地开球时间。
 *
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $timezone
 * @property string $created_at
 * @property string $updated_at
 */
class Venue extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%venue}}';
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
            [['name', 'city', 'timezone'], 'required'],
            [['name', 'city', 'timezone'], 'trim'],
            [['name'], 'string', 'max' => 150],
            [['city'], 'string', 'max' => 100],
            [['timezone'], 'string', 'max' => 64],
            [['timezone'], 'match', 'pattern' => '/^[A-Za-z_]+(\/[A-Za-z0-9_+\-]+)+$|^UTC$/', 'message' => '时区必须是 IANA 名称，例如 America/Mexico_City。'],
            [['name', 'city'], 'unique', 'targetAttribute' => ['name', 'city'], 'message' => '同一城市下的场馆名称已存在。'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '场馆',
            'city' => '城市',
            'timezone' => '时区',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 在该场馆举行的比赛。
     */
    public function getFixtures(): ActiveQuery
    {
        return $this->hasMany(Fixture::class, ['venue_id' => 'id']);
    }
}
