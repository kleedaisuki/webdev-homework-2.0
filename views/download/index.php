<?php
/**
 * Lists the coursework attachments currently available in each section.
 */

use yii\helpers\Html;

/** @var array<string, array{string, list<string>}> $sections */
$this->title = '作业下载';
$this->registerCssFile('@web/css/downloads.css');
?>
<div class="downloads-page">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php foreach ($sections as $category => [$label, $filenames]): ?>
        <section aria-labelledby="downloads-<?= Html::encode($category) ?>">
            <h2 id="downloads-<?= Html::encode($category) ?>"><?= Html::encode($label) ?></h2>
            <?php if ($filenames === []): ?>
                <p>暂无可下载的<?= Html::encode($label) ?>。</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($filenames as $filename): ?>
                        <li><?= Html::a(Html::encode($filename), [
                            '/download/file', 'category' => $category, 'filename' => $filename,
                        ]) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
