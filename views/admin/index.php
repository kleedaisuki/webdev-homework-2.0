<?php
/**
 * 用途：展示当前管理员身份和后台工作台。
 * 作者：Clash727（W05）；W10 加入团队资料入口，W11 加入赛事管理入口。
 */

use yii\helpers\Html;

$this->title = '管理工作台';
?>
<section class="admin-page">
    <p class="eyebrow">WORLD CUP 2026 · 后台</p>
    <h1>管理工作台</h1>
    <p class="intro">你好，<?= Html::encode(Yii::$app->user->identity->display_name) ?>。</p>
    <div class="admin-entry-list">
        <?= Html::a('赛事管理 <span aria-hidden="true">↗</span>', ['admin-fixture/index'], ['class' => 'primary-link']) ?>
        <?= Html::a('团队资料管理 <span aria-hidden="true">↗</span>', ['admin-team/update'], ['class' => 'primary-link']) ?>
        <?= Html::a('查看公开团队页', ['team/index'], ['class' => 'text-link']) ?>
    </div>
</section>
