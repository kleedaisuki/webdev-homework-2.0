<?php

namespace app\models;

use yii\base\Model;

final class ReviewForm extends Model
{
    public $decision;
    public $note = '';

    public function rules(): array
    {
        return [
            ['decision', 'required'],
            ['decision', 'in', 'range' => [GuestMessage::STATUS_APPROVED, GuestMessage::STATUS_REJECTED],
                'strict' => true, 'message' => '请选择通过或拒绝。'],
            ['note', 'trim', 'skipOnArray' => true],
            ['note', 'string', 'max' => 500, 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return ['decision' => '审核决定', 'note' => '审核备注'];
    }
}
