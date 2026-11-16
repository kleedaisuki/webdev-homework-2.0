<?php

/**
 * 用途：W11 赛事管理列表，正式样本与演示记录并列展示，演示行可以进入编辑。
 * 作者：孙锘梵（2410868，成员3）
 */

use app\models\Fixture;
use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '赛事管理';
$this->registerCssFile('@web/css/admin-fixture.css');
?>
<section class="admin-page fixture-admin">
    <div class="fx-head">
        <h1>赛事管理</h1>
        <?= Html::a('新建演示比赛', ['admin-fixture/create'], ['class' => 'button']) ?>
    </div>

    <?= $this->render('_notice') ?>

    <table class="fx-table">
        <thead>
        <tr>
            <th>对阵</th>
            <th>开球（北京时间）</th>
            <th>类型</th>
            <th>比赛状态</th>
            <th>发布状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fixtures as $fixture): ?>
            <tr>
                <td><?= Html::encode($fixture->homeTeam->name) ?> v <?= Html::encode($fixture->awayTeam->name) ?></td>
                <td><?= Html::encode($fixture->kickoffAtBeijing()->format('Y-m-d H:i')) ?></td>
                <td><?= $fixture->isDemo() ? '演示' : '官方' ?></td>
                <td><?= $fixture->isFinished() ? '已完赛' : '未开赛' ?></td>
                <td><?= $fixture->publication_status === Fixture::PUBLICATION_PUBLISHED ? '已发布' : '草稿' ?></td>
                <td>
                    <?php if ($fixture->isDemo()): ?>
                        <?= Html::a('编辑', ['admin-fixture/update', 'id' => $fixture->id], ['class' => 'button-link']) ?>
                    <?php else: ?>
                        <span class="fx-muted">只读</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?= LinkPager::widget(['pagination' => $pagination]) ?>
</section>
