<?php
/**
 * 用途：输出经过 HTML 编码的统一错误提示。
 * 作者：Clash727（W01）
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
?>
<section class="error-page">
    <h1><?= Html::encode($name) ?></h1>
    <p><?= nl2br(Html::encode($message)) ?></p>
    <a href="<?= Html::encode(Url::to(['site/index'])) ?>">返回首页</a>
</section>
