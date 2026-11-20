<?php

/**
 * 用途：W11 编辑演示比赛页面，保存、发布与删除是三个独立动作；W17 补充后台路径与已发布公开详情入口。
 * 作者：孙锘梵（2410868，成员3）；W17 接线（Clash727）
 */

use app\models\Fixture;
use yii\helpers\Html;

/** @var app\models\Fixture $fixture */

$this->title = '编辑演示比赛';
$this->registerCssFile('@web/css/admin-fixture.css');
?>
<section class="admin-page fixture-admin">
    <nav class="admin-breadcrumb" aria-label="后台路径">
        <?= Html::a('管理工作台', ['admin/index']) ?>
        <span aria-hidden="true">/</span>
        <?= Html::a('赛事管理', ['admin-fixture/index']) ?>
        <span aria-hidden="true">/</span>
        <span>编辑演示比赛</span>
    </nav>
    <div class="fx-head">
        <div>
            <p class="eyebrow">演示数据 · <?= $fixture->publication_status === Fixture::PUBLICATION_PUBLISHED ? '已发布' : '草稿' ?></p>
            <h1>编辑演示比赛</h1>
        </div>
        <?php if ($fixture->publication_status === Fixture::PUBLICATION_PUBLISHED): ?>
            <?= Html::a('查看公开详情 ↗', ['world-cup/view', 'id' => $fixture->id], ['class' => 'admin-preview-link', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
        <?php endif; ?>
    </div>

    <?= $this->render('_notice') ?>
    <?= $this->render('_form', [
        'model' => $model,
        'tournaments' => $tournaments,
        'teams' => $teams,
        'venues' => $venues,
    ]) ?>

    <div class="fx-footer">
        <?php if ($fixture->publication_status === Fixture::PUBLICATION_DRAFT): ?>
            <?= Html::beginForm(['admin-fixture/publish', 'id' => $fixture->id], 'post', ['class' => 'fx-publish']) ?>
            <?= Html::submitButton('发布', ['class' => 'button']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <?= Html::beginForm(['admin-fixture/delete', 'id' => $fixture->id], 'post', ['class' => 'fx-delete']) ?>
        <?= Html::submitButton('删除', [
            'class' => 'button-link is-danger',
            'onclick' => "return confirm('确定删除这条演示比赛？');",
        ]) ?>
        <?= Html::endForm() ?>
    </div>
</section>
