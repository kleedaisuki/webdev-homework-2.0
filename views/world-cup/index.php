<?php
/**
 * 用途：赛程赛果列表页（W07），按状态筛选并分页展示已发布比赛。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var app\models\Fixture[] $fixtures */
/** @var yii\data\Pagination $pagination */
/** @var string|null $status */

$this->registerCssFile('@web/css/world-cup.css');

$filters = [
    null => '全部',
    app\models\Fixture::STATUS_SCHEDULED => '未开赛',
    app\models\Fixture::STATUS_FINISHED => '已完赛',
];
?>
<section class="wc-page">
    <header class="wc-head">
        <p class="wc-eyebrow">2026 精选小组赛</p>
        <h1>赛程赛果</h1>
        <p class="wc-note">只展示已发布的场次，未开赛的比赛不显示比分；时间统一为北京时间（UTC+08:00）。</p>
    </header>

    <nav class="wc-filters" aria-label="按比赛状态筛选">
        <?php foreach ($filters as $value => $label): ?>
            <?php $isActive = $status === $value; ?>
            <?= Html::a(
                $label,
                Url::to(array_merge(['world-cup/index'], $value === null ? [] : ['status' => $value])),
                [
                    'class' => $isActive ? 'wc-filter is-active' : 'wc-filter',
                    'aria-current' => $isActive ? 'true' : null,
                ]
            ) ?>
        <?php endforeach; ?>
    </nav>

    <?php if ($fixtures === []): ?>
        <div class="wc-empty">
            <p class="wc-empty-title">暂无符合条件的比赛</p>
            <p class="wc-note">可以切换到其他状态，或查看全部已发布场次。</p>
            <?= Html::a('查看全部', Url::to(['world-cup/index']), ['class' => 'wc-button']) ?>
        </div>
    <?php else: ?>
        <ul class="wc-list">
            <?php foreach ($fixtures as $fixture): ?>
                <li class="wc-card">
                    <div class="wc-card-main">
                        <p class="wc-meta">
                            <span><?= Html::encode($fixture->kickoffAtBeijing()->format('Y-m-d H:i')) ?> 北京时间</span>
                            <span class="wc-dot" aria-hidden="true">·</span>
                            <span><?= Html::encode($fixture->venue->name) ?></span>
                            <span class="wc-dot" aria-hidden="true">·</span>
                            <span><?= Html::encode($fixture->venue->city) ?></span>
                        </p>
                        <p class="wc-teams">
                            <span class="wc-team"><?= Html::encode($fixture->homeTeam->name) ?></span>
                            <span class="wc-score <?= $fixture->isFinished() ? 'is-finished' : 'is-scheduled' ?>">
                                <?= Html::encode($fixture->displayScore() ?? '未开赛') ?>
                            </span>
                            <span class="wc-team"><?= Html::encode($fixture->awayTeam->name) ?></span>
                        </p>
                        <p class="wc-badges">
                            <?php if ($fixture->isDemo()): ?>
                                <span class="wc-badge wc-badge-demo">演示数据</span>
                            <?php else: ?>
                                <span class="wc-badge wc-badge-official">官方来源</span>
                            <?php endif; ?>
                            <?php if ($fixture->group_name !== null && $fixture->group_name !== ''): ?>
                                <span class="wc-badge">第 <?= Html::encode($fixture->group_name) ?> 组</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="wc-card-action">
                        <?= Html::a('详情', ['world-cup/view', 'id' => $fixture->id], ['class' => 'wc-button', 'aria-label' => $fixture->homeTeam->name . ' 对 ' . $fixture->awayTeam->name . ' 的详情']) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?= LinkPager::widget([
            'pagination' => $pagination,
            'options' => ['class' => 'wc-pager'],
            'linkContainerOptions' => ['class' => 'wc-pager-item'],
            'linkOptions' => ['class' => 'wc-pager-link'],
            'disabledListItemSubTagOptions' => ['class' => 'wc-pager-link is-disabled'],
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'maxButtonCount' => 5,
        ]) ?>
    <?php endif; ?>
</section>
