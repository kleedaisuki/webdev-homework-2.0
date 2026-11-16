<?php

/**
 * 用途：W11 后台提示条，显示保存与发布的结果。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\helpers\Html;

$flashes = Yii::$app->session->getAllFlashes();

if ($flashes === []) {
    return;
}
?>
<div class="fx-notice">
    <?php foreach ($flashes as $type => $message): ?>
        <p class="fx-notice-item <?= $type === 'error' ? 'is-error' : 'is-success' ?>">
            <?= Html::encode(is_array($message) ? implode(' ', $message) : (string) $message) ?>
        </p>
    <?php endforeach; ?>
</div>
