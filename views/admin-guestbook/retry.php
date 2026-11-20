<?php
/**
 * 用途：W12 留言审核表单校验失败重填页；W17 补充后台路径导航。
 * 作者：Clash727（W12 / W17）
 */

use yii\helpers\Html;

$this->title = '留言审核';
$this->registerCssFile('@web/css/admin-guestbook.css');
?>
<section class="admin-page review-page">
    <nav class="admin-breadcrumb" aria-label="后台路径">
        <?= Html::a('管理工作台', ['admin/index']) ?>
        <span aria-hidden="true">/</span>
        <?= Html::a('留言审核', ['index', 'status' => $message['status']]) ?>
        <span aria-hidden="true">/</span>
        <span>重新提交</span>
    </nav>
    <h1><?= Html::encode($this->title) ?></h1>
    <p><?= Html::a('返回留言列表', ['index', 'status' => $message['status']]) ?></p>
    <?= $this->render('_message', ['message' => $message, 'form' => $form]) ?>
</section>
