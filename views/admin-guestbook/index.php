<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '留言审核';
$this->registerCssFile('@web/css/admin-guestbook.css');
?>
<section class="admin-page review-page">
    <h1><?= Html::encode($this->title) ?></h1>
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
