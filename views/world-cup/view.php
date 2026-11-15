<?php
/**
 * 用途：单场比赛详情页（W07），展示比分、开球时间、场馆与来源。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\Fixture $fixture */

$this->registerCssFile('@web/css/world-cup.css');

$datetime = $fixture->kickoffAtBeijing();
$isFinished = $fixture->isFinished();
?>
<section class="wc-page">
    <p class="wc-back">
        <?= Html::a('← 返回赛程赛果', Url::to(['world-cup/index'])) ?>
    </p>

    <header class="wc-head">
        <p class="wc-eyebrow">
            <?= Html::encode($fixture->tournament->edition) ?> ·
            <?= Html::encode($fixture->stage === 'group' ? '小组赛' : $fixture->stage) ?><?php if ($fixture->group_name !== null && $fixture->group_name !== ''): ?> ·
                <?= Html::encode($fixture->group_name) ?> 组<?php endif; ?>
        </p>
        <p class="wc-badges">
            <?php if ($fixture->isDemo()): ?>
                <span class="wc-badge wc-badge-demo">演示数据</span>
            <?php else: ?>
                <span class="wc-badge wc-badge-official">官方样本</span>
            <?php endif; ?>
            <span class="wc-badge"><?= $isFinished ? '已完赛' : '未开赛' ?></span>
        </p>
    </header>

    <div class="wc-scoreboard" role="group" aria-label="比赛比分">
        <p class="wc-side"><?= Html::encode($fixture->homeTeam->name) ?></p>
        <p class="wc-big-score <?= $isFinished ? 'is-finished' : 'is-scheduled' ?>">
            <?= Html::encode($fixture->displayScore() ?? '未开赛') ?>
        </p>
        <p class="wc-side"><?= Html::encode($fixture->awayTeam->name) ?></p>
    </div>

    <dl class="wc-facts">
        <div class="wc-fact">
            <dt>开球时间</dt>
            <dd><?= Html::encode($datetime->format('Y-m-d H:i')) ?> 北京时间 / UTC+08:00</dd>
        </div>
        <div class="wc-fact">
            <dt>场馆</dt>
            <dd><?= Html::encode($fixture->venue->name) ?> · <?= Html::encode($fixture->venue->city) ?></dd>
        </div>
        <div class="wc-fact">
            <dt>数据来源</dt>
            <dd>
                <?php if ($fixture->isDemo()): ?>
                    课堂演示，非官方比赛及赛果。
                <?php else: ?>
                    <?php if ($fixture->source_url !== null && $fixture->source_url !== ''): ?>
                        <?= Html::a('FIFA 官方来源', Html::encode($fixture->source_url), ['rel' => 'noopener noreferrer', 'target' => '_blank']) ?>
                    <?php else: ?>
                        官方来源
                    <?php endif; ?>
                    <?php if ($fixture->source_checked_at !== null && $fixture->source_checked_at !== ''): ?>
                        · 核对于 <?= Html::encode(substr((string) $fixture->source_checked_at, 0, 10)) ?>（UTC）
                    <?php endif; ?>
                <?php endif; ?>
            </dd>
        </div>
    </dl>

    <p class="wc-note">本页只读，编辑与发布在后台完成。</p>
</section>
