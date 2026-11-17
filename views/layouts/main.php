<?php
/**
 * 用途：四个一级页面的公共布局与后台会话入口。
 * 作者：Clash727（身份入口）、zhixiu-feng（公共导航与布局）
 */

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
$route = Yii::$app->controller->getRoute();
$navigation = [
    ['主页', ['site/index'], $route === 'site/index'],
    ['团队成员', ['team/index'], str_starts_with($route, 'team/')],
    ['项目经历', ['site/project'], $route === 'site/project'],
    ['世界杯专题', ['world-cup/index'], str_starts_with($route, 'world-cup/') || str_starts_with($route, 'world-cup-hub/')],
    ['作业下载', ['download/index'], $route === 'download/index'],
];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title . ' · ' . Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<a class="skip-link" href="#main-content">跳到正文</a>
<header class="site-header">
    <a class="brand" href="<?= Html::encode(Url::to(['site/index'])) ?>"><span class="brand-ball" aria-hidden="true"><svg viewBox="0 0 40 40"><circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 11 9 6-3 11H14l-3-11Z" fill="currentColor"/><path d="M20 3v8M36 15l-7 2M29 34l-3-6M11 34l3-6M4 15l7 2" stroke="currentColor" stroke-width="2"/></svg></span><span>世界杯<span class="brand-year">2026</span></span></a>
    <nav class="site-nav" aria-label="主导航">
        <?php foreach ($navigation as [$label, $url, $active]): ?>
            <?= Html::a($label, $url, ['class' => $active ? 'is-active' : null, 'aria-current' => $active ? 'page' : null]) ?>
        <?php endforeach; ?>
    </nav>
    <div class="account-nav">
        <?php if (Yii::$app->user->isGuest): ?>
            <?= Html::a('管理员登录', ['auth/login']) ?>
        <?php else: ?>
            <?= Html::a('管理工作台', ['admin/index']) ?>
            <?= Html::beginForm(['auth/logout'], 'post', ['class' => 'logout-form']) ?>
                <?= Html::submitButton('退出登录', ['class' => 'button-link']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</header>
<main id="main-content" class="container">
    <?= $content ?>
</main>
<footer class="site-footer"><span>2026 世界杯专题</span><span>课程项目 · 非 FIFA 官方网站</span><?= Html::a('项目仓库 ↗', 'https://github.com/kleedaisuki/webdev-homework-2.0', ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?></footer>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
