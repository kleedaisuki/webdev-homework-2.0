<?php
/**
 * 用途：展示当前管理员身份、后台工作台与前后台公开页核对入口。
 * 作者：Clash727（W05 / W17）；W10 加入团队资料入口，W11 加入赛事管理入口，W12 加入留言审核入口。
 */

use yii\helpers\Html;

$this->title = '管理工作台';
?>
<section class="admin-page">
    <h1>管理工作台</h1>
    <p class="intro">你好，<?= Html::encode(Yii::$app->user->identity->display_name) ?>。</p>
    <div class="admin-entry-list">
        <?= Html::a('赛事管理 <span aria-hidden="true">↗</span>', ['admin-fixture/index'], ['class' => 'primary-link']) ?>
        <?= Html::a('团队资料管理 <span aria-hidden="true">↗</span>', ['admin-team/update'], ['class' => 'primary-link']) ?>
        <?= Html::a('留言审核 <span aria-hidden="true">↗</span>', ['admin-guestbook/index'], ['class' => 'primary-link']) ?>
    </div>
    <nav class="admin-public-links" aria-label="公开页面核对">
        <span class="admin-public-label">公开页核对</span>
        <?= Html::a('世界杯首页', ['site/index'], ['class' => 'text-link']) ?>
        <?= Html::a('公开赛程与赛果', ['world-cup/index'], ['class' => 'text-link']) ?>
        <?= Html::a('查看公开团队页', ['team/index'], ['class' => 'text-link']) ?>
        <?= Html::a('公开留言', ['guestbook/index'], ['class' => 'text-link']) ?>
        <?= Html::a('作业下载', ['download/index'], ['class' => 'text-link']) ?>
    </nav>
</section>
