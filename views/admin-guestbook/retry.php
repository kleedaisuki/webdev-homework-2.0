<?php

use yii\helpers\Html;

$this->title = '留言审核';
$this->registerCssFile('@web/css/admin-guestbook.css');
?>
<section class="admin-page review-page">
    <h1><?= Html::encode($this->title) ?></h1>
    <p><?= Html::a('返回留言列表', ['index', 'status' => $message['status']]) ?></p>
    <?= $this->render('_message', ['message' => $message, 'form' => $form]) ?>
</section>
