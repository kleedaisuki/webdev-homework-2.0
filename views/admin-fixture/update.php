<?php

/**
 * 用途：W11 编辑演示比赛页面，保存与发布是两个独立动作。
 * 作者：孙锘梵（2410868，成员3）
 */

use app\models\Fixture;
use yii\helpers\Html;

/** @var app\models\Fixture $fixture */

$this->title = '编辑演示比赛';
$this->registerCssFile('@web/css/admin-fixture.css');
?>
<section class="admin-page fixture-admin">
    <p class="eyebrow">演示数据 · <?= $fixture->publication_status === Fixture::PUBLICATION_PUBLISHED ? '已发布' : '草稿' ?></p>
    <h1>编辑演示比赛</h1>

    <?= $this->render('_notice') ?>
    <?= $this->render('_form', [
        'model' => $model,
        'tournaments' => $tournaments,
        'teams' => $teams,
        'venues' => $venues,
    ]) ?>

    <?php if ($fixture->publication_status === Fixture::PUBLICATION_DRAFT): ?>
        <?= Html::beginForm(['admin-fixture/publish', 'id' => $fixture->id], 'post', ['class' => 'fx-publish']) ?>
        <?= Html::submitButton('发布', ['class' => 'button']) ?>
        <?= Html::endForm() ?>
    <?php endif; ?>
</section>
