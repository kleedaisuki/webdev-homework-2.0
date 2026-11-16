<?php

/**
 * 用途：W11 演示赛程的编辑表单，新建与编辑共用。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\FixtureForm $model */
/** @var array $tournaments */
/** @var array $teams */
/** @var array $venues */

$form = ActiveForm::begin([
    'id' => 'fixture-form',
    'options' => ['class' => 'fx-form'],
    'fieldConfig' => ['options' => ['class' => 'fx-field']],
]);
?>
<div class="fx-grid">
    <div class="fx-row">
        <?= $form->field($model, 'tournament_id')->dropDownList($tournaments, ['prompt' => '—']) ?>
        <?= $form->field($model, 'group_name')->textInput(['maxlength' => 8, 'placeholder' => 'A']) ?>
    </div>

    <div class="fx-row">
        <?= $form->field($model, 'home_team_id')->dropDownList($teams, ['prompt' => '—']) ?>
        <?= $form->field($model, 'away_team_id')->dropDownList($teams, ['prompt' => '—']) ?>
    </div>

    <?= $form->field($model, 'venue_id')->dropDownList($venues, ['prompt' => '—']) ?>

    <?= $form->field($model, 'kickoff_at')
        ->textInput(['placeholder' => '2026-06-12 03:00:00', 'autocomplete' => 'off'])
        ->hint('北京时间 / UTC+08:00') ?>

    <?= $form->field($model, 'match_status')->dropDownList([
        app\models\Fixture::STATUS_SCHEDULED => '未开赛',
        app\models\Fixture::STATUS_FINISHED => '已完赛',
    ]) ?>

    <div class="fx-row fx-scores">
        <?= $form->field($model, 'home_score')->textInput(['type' => 'number', 'min' => 0, 'max' => 99]) ?>
        <?= $form->field($model, 'away_score')->textInput(['type' => 'number', 'min' => 0, 'max' => 99]) ?>
    </div>
    <p class="fx-hint">未开赛时留空；已完赛时两项均须填写，0 为有效比分。</p>

    <div class="fx-actions">
        <?= Html::submitButton('保存', ['class' => 'button']) ?>
        <?= Html::a('返回列表', ['admin-fixture/index'], ['class' => 'button-link']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
