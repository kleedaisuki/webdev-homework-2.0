<?php

/**
 * 用途：W11 新建演示比赛页面。
 * 作者：孙锘梵（2410868，成员3）
 */

$this->title = '新建演示比赛';
$this->registerCssFile('@web/css/admin-fixture.css');
?>
<section class="admin-page fixture-admin">
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
