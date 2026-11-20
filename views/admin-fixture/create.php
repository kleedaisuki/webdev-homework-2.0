<?php

/**
 * 用途：W11 新建演示比赛页面。
 * 作者：孙锘梵（2410868，成员3）；W17 补充后台路径导航（Clash727）
 */

use yii\helpers\Html;

$this->title = '新建演示比赛';
$this->registerCssFile('@web/css/admin-fixture.css');
?>
<section class="admin-page fixture-admin">
    <nav class="admin-breadcrumb" aria-label="后台路径">
        <?= Html::a('管理工作台', ['admin/index']) ?>
        <span aria-hidden="true">/</span>
        <?= Html::a('赛事管理', ['admin-fixture/index']) ?>
        <span aria-hidden="true">/</span>
        <span>新建演示比赛</span>
    </nav>
    <p class="eyebrow">演示数据 · 草稿</p>
    <h1>新建演示比赛</h1>

    <?= $this->render('_notice') ?>
    <?= $this->render('_form', [
        'model' => $model,
        'tournaments' => $tournaments,
        'teams' => $teams,
        'venues' => $venues,
    ]) ?>
</section>
