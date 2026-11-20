<?php
/**
 * 用途：W12 后台留言审核列表；W17 补充后台路径与公开页核对入口。
 * 作者：Clash727（W12 / W17）
 */

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '留言审核';
$this->registerCssFile('@web/css/admin-guestbook.css');
?>
<section class="admin-page review-page">
    <nav class="admin-breadcrumb" aria-label="后台路径">
        <?= Html::a('管理工作台', ['admin/index']) ?>
        <span aria-hidden="true">/</span>
        <span>留言审核</span>
    </nav>
    <div class="admin-page-head">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="admin-head-actions">
            <?= Html::a('查看公开留言 ↗', ['guestbook/index'], ['class' => 'admin-preview-link', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
            <?= Html::a('查看球迷讨论 ↗', ['world-cup-hub/fans'], ['class' => 'admin-preview-link', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
        </div>
    </div>
    <nav class="review-tabs" aria-label="留言状态">
        <?php foreach ($statuses as $value => $label): ?>
            <?= Html::a($label, ['index', 'status' => $value], [
                'aria-current' => $value === $status ? 'page' : null,
            ]) ?>
        <?php endforeach; ?>
    </nav>
    <?php if (Yii::$app->session->hasFlash('guestReview')): ?>
        <p role="status"><?= Html::encode(Yii::$app->session->getFlash('guestReview')) ?></p>
    <?php endif; ?>
    <?php if (!$messages): ?>
        <p>暂无<?= Html::encode($statuses[$status]) ?>的留言。</p>
    <?php endif; ?>
    <?php foreach ($messages as $message): ?>
        <?= $this->render('_message', ['message' => $message, 'form' => null]) ?>
    <?php endforeach; ?>
    <?= LinkPager::widget(['pagination' => $pagination]) ?>
</section>
